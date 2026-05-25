# Desain: Kurir Internal — Pengiriman & Penerimaan Logistik

**Tanggal:** 2026-05-25
**Status:** Draft
**Scope:** Penyederhanaan flow kurir di modul pengiriman pemasok dan penerimaan merchant.

---

## 1. Latar Belakang

Saat ini di modal "Kirim Barang" (pemasok), user harus memilih jenis layanan kurir dari dropdown (Lalamove, Grab Express, GoSend, Kurir Internal). Info kurir + resi disimpan sebagai concatenated string ke kolom `catatan` dengan prefix `[UPDATE LOGISTIK]`, dan merchant membacanya pakai `str_contains`.

Pola ini punya dua masalah:

1. **Tidak relevan dengan operasional sebenarnya** — Pemasok di SCFS pakai kurir internal, bukan layanan pihak ketiga.
2. **Design smell** — Kolom `kurir`, `no_resi`, `tracking_history` sudah ada di `supply_orders` (migration `2026_03_30_102349_add_logistik_to_supply_orders_table.php`) tapi tidak dipakai. Data disimpan sebagai string di kolom `catatan` yang harus di-parse pakai `str_contains` / `str_replace`.

## 2. Tujuan

- Hilangkan pilihan jenis layanan kurir. Asumsi: semua pengiriman pakai kurir internal.
- Pemasok input **nama kurir** + **nomor HP kurir** secara manual.
- Merchant bisa melihat nama + no HP kurir di halaman penerimaan logistik.
- Simpan data kurir di kolom dedicated, bukan dalam string `catatan`.

Non-tujuan (untuk follow-up nanti):

- Master data kurir / pilih dari list. Saat ini manual entry.
- Tracking real-time / status update kurir.
- Notifikasi WhatsApp ke kurir.

## 3. Skema Database

### Migration baru

File: `database/migrations/YYYY_MM_DD_HHMMSS_add_kurir_info_to_supply_orders_table.php`

```php
Schema::table('supply_orders', function (Blueprint $table) {
    $table->string('nama_kurir')->nullable()->after('kurir');
    $table->string('no_hp_kurir', 15)->nullable()->after('nama_kurir');
});
```

Down:

```php
$table->dropColumn(['nama_kurir', 'no_hp_kurir']);
```

### Kolom lama `kurir`

Kolom `kurir` tetap di tabel tapi **berhenti ditulis**. Tidak di-drop di migration ini supaya rollback aman. Penghapusan jadi follow-up terpisah setelah memastikan tidak ada read path yang masih bergantung.

### Data existing

Order existing yang punya info logistik di kolom `catatan` (format `[UPDATE LOGISTIK]\nDikirim via: X | Resi: Y`) **tidak di-backfill**. UI baru fallback ke "Belum ada info kurir" jika `nama_kurir` null. Order baru pakai kolom baru.

## 4. Backend — Pemasok

File: `app/Livewire/Pemasok/PengirimanLogistik.php`

### Property changes

```php
// Hapus:
public $kurir = '';

// Tambah:
public $nama_kurir = '';
public $no_hp_kurir = '';

// Tetap:
public $no_resi = '';
```

### `bukaModalAtur($id)`

```php
public function bukaModalAtur($id)
{
    $this->selectedOrderId = $id;
    $this->no_resi = 'SCFS-' . strtoupper(substr(uniqid(), -6));
    $this->nama_kurir = '';
    $this->no_hp_kurir = '';
    $this->showModalAtur = true;
}
```

### `simpanPengiriman()`

```php
public function simpanPengiriman()
{
    $this->validate([
        'nama_kurir' => 'required|string|max:100',
        'no_hp_kurir' => 'required|digits_between:10,15',
        'no_resi' => 'required',
    ]);

    $order = SupplyOrder::where('pemasok_id', Auth::id())->find($this->selectedOrderId);

    if ($order && $order->status === 'diproses_pemasok') {
        $order->update([
            'status' => 'dikirim',
            'nama_kurir' => $this->nama_kurir,
            'no_hp_kurir' => $this->no_hp_kurir,
            'no_resi' => $this->no_resi,
        ]);

        session()->flash('message', 'Pengiriman berhasil diatur! Pesanan sekarang SEDANG DIKIRIM.');
    }

    $this->showModalAtur = false;
    $this->reset(['nama_kurir', 'no_hp_kurir', 'no_resi', 'selectedOrderId']);
}
```

**Catatan:** Logika lama yang nulis ke `catatan` dengan prefix `[UPDATE LOGISTIK]` dihapus. `catatan` kembali murni jadi catatan order dari merchant.

## 5. Frontend — Pemasok

File: `resources/views/livewire/pemasok/pengiriman-logistik.blade.php`

### Modal "Kirim Barang" (baris 216–254)

Ganti blok `<select wire:model="kurir">` (baris 231–241) dengan:

```blade
<div>
    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Kurir</label>
    <input type="text" wire:model="nama_kurir"
           placeholder="cth: Budi Santoso"
           class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500 font-bold bg-gray-50">
    @error('nama_kurir') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
</div>
<div>
    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">No HP Kurir</label>
    <input type="tel" wire:model="no_hp_kurir"
           placeholder="cth: 081234567890"
           inputmode="numeric"
           class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500 font-bold bg-gray-50">
    @error('no_hp_kurir') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
</div>
```

Field "Nomor Resi / Surat Jalan" (baris 242–246) tetap, mengikuti pola existing: input editable yang sudah diisi nilai auto-generated saat modal dibuka. User boleh override jika perlu.

### Modal Surat Jalan (baris 257–322)

Di area cetak label (baris 260–312), tambah baris kurir di blok "Kepada Merchant" atau di section sendiri sebelum tabel rincian:

```blade
<div class="border-t-2 border-black pt-4 mb-4">
    <p class="text-xs uppercase text-gray-500 mb-1">Diantar Oleh:</p>
    <p class="font-bold text-sm">{{ $this->selectedOrder->nama_kurir ?? '-' }}</p>
    <p class="text-xs">HP: {{ $this->selectedOrder->no_hp_kurir ?? '-' }}</p>
    <p class="text-xs">Resi: {{ $this->selectedOrder->no_resi ?? '-' }}</p>
</div>
```

## 6. Frontend — Merchant

File: `resources/views/livewire/merchant/penerimaan.blade.php`

### Section "Status Logistik Pemasok" (baris 161–171)

Ganti pemeriksaan `str_contains($order->catatan, '[UPDATE LOGISTIK]')` dengan check kolom langsung:

```blade
<div>
    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Info Kurir</p>
    @if($order->nama_kurir)
        <div class="text-xs font-bold text-blue-600 bg-blue-50 p-2 rounded-lg border border-blue-100 space-y-0.5">
            <p>👤 {{ $order->nama_kurir }}</p>
            <p>
                📞 <a href="tel:{{ $order->no_hp_kurir }}" class="underline">{{ $order->no_hp_kurir }}</a>
            </p>
            <p class="text-[10px] text-blue-500">Resi: {{ $order->no_resi }}</p>
        </div>
    @else
        <p class="text-xs font-bold text-gray-400 italic">Belum ada info kurir</p>
    @endif
</div>
```

Tidak ada perubahan lain di file ini. Logika `konfirmasiTerima()` tetap sama.

## 7. Validasi & Edge Case

| Skenario | Behavior |
|---|---|
| Pemasok submit dengan nama kurir kosong | Validasi gagal: "Nama kurir wajib diisi" |
| Pemasok submit dengan no HP 9 digit | Validasi gagal: "No HP harus 10–15 digit" |
| Pemasok submit dengan no HP mengandung huruf/spasi | Validasi gagal: `digits_between` reject non-digit |
| Merchant lihat order lama (sebelum migration) | Tampilkan "Belum ada info kurir" (kolom null) |
| Order existing dengan info di `catatan` lama | Info lama tidak terbaca. Acceptable — data lama udah selesai dikirim |

## 8. Testing

Manual test (golden path):

1. Pemasok login → halaman Pengiriman & Logistik → klik "Kirim Barang" pada order status `diproses_pemasok`.
2. Modal terbuka: cek tidak ada dropdown kurir, ada input "Nama Kurir" + "No HP Kurir".
3. Submit dengan nama kurir kosong → muncul error validasi.
4. Submit dengan no HP "abc123" → muncul error validasi.
5. Submit dengan nama "Budi" + HP "081234567890" → sukses, order pindah ke tab "Sedang Jalan".
6. Cek DB: `nama_kurir`, `no_hp_kurir`, `no_resi` terisi. `catatan` tidak tersentuh.
7. Login sebagai merchant → halaman Penerimaan Logistik → cek order tampil "👤 Budi" + "📞 081234567890" + resi.
8. Klik no HP → browser buka `tel:` link.
9. Cetak surat jalan dari sisi pemasok → cek nama + HP kurir muncul.

Edge case test:

- Order lama (sebelum migration) di tab "Sedang Jalan" merchant → tampil "Belum ada info kurir".

## 9. Files Terdampak

| File | Aksi |
|---|---|
| `database/migrations/YYYY_MM_DD_HHMMSS_add_kurir_info_to_supply_orders_table.php` | **Baru** |
| `app/Livewire/Pemasok/PengirimanLogistik.php` | Modify |
| `resources/views/livewire/pemasok/pengiriman-logistik.blade.php` | Modify |
| `resources/views/livewire/merchant/penerimaan.blade.php` | Modify |

## 10. Follow-Up (Out of Scope)

- Master data kurir per pemasok (CRUD daftar kurir tetap, pilih dari list di modal).
- Drop kolom lama `kurir` di migration terpisah setelah verifikasi tidak ada read path tersisa.
- Notifikasi WA otomatis ke kurir saat order siap diambil.
- Tracking status real-time (kurir update lokasi via mobile app).
