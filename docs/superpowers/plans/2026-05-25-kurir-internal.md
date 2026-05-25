# Kurir Internal — Pengiriman & Penerimaan Logistik Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sederhanakan flow kurir di modul pengiriman pemasok dan penerimaan merchant. Ganti dropdown jenis layanan kurir dengan input manual nama + no HP kurir. Pindahkan data kurir dari string `catatan` ke kolom dedicated.

**Architecture:** Tambah dua kolom `nama_kurir` + `no_hp_kurir` ke tabel `supply_orders`. Update Livewire component pemasok (`PengirimanLogistik`) untuk validasi dan persistensi ke kolom baru. Update view pemasok (form pengiriman + surat jalan) dan view merchant (info kurir) untuk pakai kolom langsung, bukan parsing string.

**Tech Stack:** Laravel 11 + Livewire 3 + Pest (untuk component test) + Tailwind. MySQL.

**Reference:** `docs/superpowers/specs/2026-05-25-kurir-internal-design.md`

---

## File Structure

| File | Status | Tanggung Jawab |
|---|---|---|
| `database/migrations/YYYY_MM_DD_HHMMSS_add_kurir_info_to_supply_orders_table.php` | Create | Tambah kolom `nama_kurir` + `no_hp_kurir` |
| `app/Livewire/Pemasok/PengirimanLogistik.php` | Modify | Property + validasi + persistensi ke kolom baru |
| `resources/views/livewire/pemasok/pengiriman-logistik.blade.php` | Modify | Modal form: ganti dropdown → input nama+HP. Surat jalan: tampil info kurir. |
| `resources/views/livewire/merchant/penerimaan.blade.php` | Modify | Tampil info kurir dari kolom, bukan dari `catatan` |
| `tests/Feature/Pemasok/PengirimanLogistikTest.php` | Create | Livewire component test untuk validasi + persistensi |

---

## Task 1: Migration — Tambah kolom `nama_kurir` + `no_hp_kurir`

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_kurir_info_to_supply_orders_table.php`

- [ ] **Step 1: Generate migration file**

Run:
```bash
php artisan make:migration add_kurir_info_to_supply_orders_table --table=supply_orders
```

Expected: New file created in `database/migrations/` dengan timestamp `YYYY_MM_DD_HHMMSS`.

- [ ] **Step 2: Isi migration body**

Replace seluruh isi file migration baru:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->string('nama_kurir')->nullable()->after('kurir');
            $table->string('no_hp_kurir', 15)->nullable()->after('nama_kurir');
        });
    }

    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->dropColumn(['nama_kurir', 'no_hp_kurir']);
        });
    }
};
```

- [ ] **Step 3: Jalankan migration**

Run:
```bash
php artisan migrate
```

Expected output:
```
INFO  Running migrations.
  YYYY_MM_DD_HHMMSS_add_kurir_info_to_supply_orders_table ......... DONE
```

- [ ] **Step 4: Verifikasi skema**

Run:
```bash
php artisan tinker --execute="dd(Schema::getColumnListing('supply_orders'));"
```

Expected: array hasil mengandung `'nama_kurir'` dan `'no_hp_kurir'`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat(db): tambah kolom nama_kurir & no_hp_kurir di supply_orders"
```

---

## Task 2: Backend — Update Livewire `PengirimanLogistik`

**Files:**
- Modify: `app/Livewire/Pemasok/PengirimanLogistik.php`

- [ ] **Step 1: Ganti property `$kurir` jadi `$nama_kurir` + `$no_hp_kurir`**

Di `app/Livewire/Pemasok/PengirimanLogistik.php` baris 24–26, ganti:

```php
    // Form Atur Pengiriman
    public $kurir = '';
    public $no_resi = '';
```

Menjadi:

```php
    // Form Atur Pengiriman
    public $nama_kurir = '';
    public $no_hp_kurir = '';
    public $no_resi = '';
```

- [ ] **Step 2: Update `bukaModalAtur()`**

Ganti method `bukaModalAtur` (baris 40–46) menjadi:

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

- [ ] **Step 3: Update `simpanPengiriman()` — validasi & persistensi kolom baru**

Ganti seluruh method `simpanPengiriman` (baris 54–76) menjadi:

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

Catatan: blok `$infoPengiriman = "Dikirim via: ..."` dan update ke `catatan` dihapus.

- [ ] **Step 4: Cek SupplyOrder model — pastikan kolom baru fillable**

Buka `app/Models/SupplyOrder.php`. Jika `$fillable` array eksplisit, tambahkan `'nama_kurir'` dan `'no_hp_kurir'` di sana. Jika model pakai `$guarded = []` atau equivalent yang allow mass-assign semua, skip step ini.

Cara cek: cari `protected $fillable` atau `protected $guarded` di file model. Jika `$fillable` ada, edit array-nya:

```php
protected $fillable = [
    // ... existing fields ...
    'nama_kurir',
    'no_hp_kurir',
];
```

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Pemasok/PengirimanLogistik.php app/Models/SupplyOrder.php
git commit -m "feat(pemasok): simpan nama & no HP kurir ke kolom dedicated"
```

---

## Task 3: Test — Livewire component test untuk validasi + persistensi

**Files:**
- Create: `tests/Feature/Pemasok/PengirimanLogistikTest.php`

- [ ] **Step 1: Cek factory yang tersedia**

Run:
```bash
ls database/factories/
```

Verifikasi `UserFactory.php` dan `SupplyOrderFactory.php` ada. Jika `SupplyOrderFactory.php` belum ada:

```bash
php artisan make:factory SupplyOrderFactory --model=SupplyOrder
```

Lalu isi factory minimal:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_order' => 'PO-' . strtoupper(substr(uniqid(), -6)),
            'pemasok_id' => User::factory(),
            'merchant_id' => User::factory(),
            'status' => 'diproses_pemasok',
            'tanggal_kebutuhan' => now()->addDays(3),
            'total_estimasi' => 100000,
        ];
    }
}
```

Adjust field names sesuai schema `supply_orders` aktual jika berbeda.

- [ ] **Step 2: Tulis failing test**

Create `tests/Feature/Pemasok/PengirimanLogistikTest.php`:

```php
<?php

use App\Livewire\Pemasok\PengirimanLogistik;
use App\Models\SupplyOrder;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('validates nama_kurir is required', function () {
    $pemasok = User::factory()->create();
    $order = SupplyOrder::factory()->create([
        'pemasok_id' => $pemasok->id,
        'status' => 'diproses_pemasok',
    ]);

    actingAs($pemasok);

    Livewire::test(PengirimanLogistik::class)
        ->call('bukaModalAtur', $order->id)
        ->set('nama_kurir', '')
        ->set('no_hp_kurir', '081234567890')
        ->call('simpanPengiriman')
        ->assertHasErrors(['nama_kurir' => 'required']);
});

it('validates no_hp_kurir must be 10-15 digits', function () {
    $pemasok = User::factory()->create();
    $order = SupplyOrder::factory()->create([
        'pemasok_id' => $pemasok->id,
        'status' => 'diproses_pemasok',
    ]);

    actingAs($pemasok);

    Livewire::test(PengirimanLogistik::class)
        ->call('bukaModalAtur', $order->id)
        ->set('nama_kurir', 'Budi')
        ->set('no_hp_kurir', '123')
        ->call('simpanPengiriman')
        ->assertHasErrors(['no_hp_kurir']);
});

it('persists kurir info to dedicated columns and changes status to dikirim', function () {
    $pemasok = User::factory()->create();
    $order = SupplyOrder::factory()->create([
        'pemasok_id' => $pemasok->id,
        'status' => 'diproses_pemasok',
        'catatan' => 'Catatan asli dari merchant',
    ]);

    actingAs($pemasok);

    Livewire::test(PengirimanLogistik::class)
        ->call('bukaModalAtur', $order->id)
        ->set('nama_kurir', 'Budi Santoso')
        ->set('no_hp_kurir', '081234567890')
        ->call('simpanPengiriman')
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('dikirim');
    expect($order->nama_kurir)->toBe('Budi Santoso');
    expect($order->no_hp_kurir)->toBe('081234567890');
    expect($order->no_resi)->toStartWith('SCFS-');
    expect($order->catatan)->toBe('Catatan asli dari merchant');
});
```

- [ ] **Step 3: Jalankan test, pastikan PASS**

Run:
```bash
php artisan test --filter=PengirimanLogistikTest
```

Expected: 3 passed (karena Task 2 sudah mengimplementasikan logika; test ini berfungsi sebagai regresi guard).

Jika ada FAIL terkait factory atau schema (mis. kolom tidak ditemukan, foreign key error), perbaiki factory definisi di Step 1 supaya cocok dengan schema aktual lalu re-run.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Pemasok/PengirimanLogistikTest.php database/factories/SupplyOrderFactory.php
git commit -m "test(pemasok): regression test validasi & persistensi kurir info"
```

---

## Task 4: Frontend Pemasok — Modal "Kirim Barang"

**Files:**
- Modify: `resources/views/livewire/pemasok/pengiriman-logistik.blade.php` (baris 231–241)

- [ ] **Step 1: Ganti dropdown kurir dengan dua input nama + HP**

Buka file, cari blok berikut di sekitar baris 231–241:

```blade
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Armada / Kurir</label>
                    <select wire:model="kurir" class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500 bg-white">
                        <option value="">-- Pilih Kurir --</option>
                        <option value="Kurir Internal Pemasok">Kurir Internal (Pribadi)</option>
                        <option value="Lalamove">Lalamove</option>
                        <option value="Grab Express">Grab Express</option>
                        <option value="GoSend">GoSend</option>
                    </select>
                    @error('kurir') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
```

Ganti dengan:

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

- [ ] **Step 2: Manual verifikasi UI**

Jalankan server lokal (Laragon biasanya sudah running). Buka browser → login sebagai pemasok → halaman Pengiriman & Logistik → klik "Kirim Barang" pada order status `diproses_pemasok`.

Cek:
- Tidak ada dropdown kurir.
- Ada dua input baru: "Nama Kurir" (text) dan "No HP Kurir" (tel, keyboard numeric di mobile).
- Field "Nomor Resi" tetap, sudah terisi `SCFS-XXXXXX`.

Test submit:
- Kosong → muncul error "Nama Kurir wajib diisi".
- Nama "Budi", HP "abc" → muncul error HP.
- Nama "Budi", HP "081234567890" → sukses, order pindah ke tab "Sedang Jalan".

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/pemasok/pengiriman-logistik.blade.php
git commit -m "feat(pemasok): ganti dropdown kurir dengan input manual nama & HP"
```

---

## Task 5: Frontend Pemasok — Surat Jalan tampilkan info kurir

**Files:**
- Modify: `resources/views/livewire/pemasok/pengiriman-logistik.blade.php` (modal cetak, sekitar baris 281)

- [ ] **Step 1: Tambah blok info kurir di area cetak surat jalan**

Cari di sekitar baris 281–299 blok berikut:

```blade
                    <div class="border-t-2 border-black pt-4">
                        <p class="text-xs uppercase text-gray-500 mb-2">Rincian Barang:</p>
                        <table class="w-full text-xs text-left mb-4">
```

Tambahkan blok baru SEBELUM blok "Rincian Barang" di atas:

```blade
                    <div class="border-t-2 border-black pt-4 mb-4">
                        <p class="text-xs uppercase text-gray-500 mb-1">Diantar Oleh:</p>
                        <p class="font-bold text-sm">{{ $this->selectedOrder->nama_kurir ?? '-' }}</p>
                        <p class="text-xs">HP: {{ $this->selectedOrder->no_hp_kurir ?? '-' }}</p>
                        <p class="text-xs">Resi: {{ $this->selectedOrder->no_resi ?? '-' }}</p>
                    </div>
```

Sehingga urutan jadi: header → dari/kepada → **diantar oleh (baru)** → rincian barang → ttd.

- [ ] **Step 2: Manual verifikasi cetak**

Buka order yang sudah berstatus `dikirim` (hasil dari Task 4 Step 2) → tab "Sedang Jalan". Sebenarnya tombol Cetak Label cuma muncul di tab `diproses_pemasok`. Test alternatif: 

- Kembali ke tab "Perlu Dikirim" → ambil order lain status `diproses_pemasok` yang punya `nama_kurir` (atau set manual via tinker untuk testing).
- Atau test dengan order yang sudah dikirim: temporary, tampilkan tombol "Cetak Label" juga di tab `dikirim` untuk verifikasi, lalu revert.

Pragmatic: lakukan flow normal:
1. Tab "Perlu Dikirim" → klik "Cetak Label" pada order belum dikirim.
2. Cek surat jalan tampil "Diantar Oleh: -", "HP: -", "Resi: -" (karena belum dikirim, kolom masih null). Acceptable.
3. Tutup, klik "Kirim Barang", submit form dengan kurir Budi/081234567890.
4. Order pindah ke "Sedang Jalan". Cek di DB / penerimaan merchant.

Note: tombol Cetak Label di-design untuk pre-shipping. Info kurir baru terisi setelah `Kirim Barang`. Ini acceptable — pemasok bisa tulis manual di surat jalan ttd pengirim, atau cetak setelah update kurir kalau workflow-nya begitu.

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/pemasok/pengiriman-logistik.blade.php
git commit -m "feat(pemasok): tampilkan info kurir di surat jalan"
```

---

## Task 6: Frontend Merchant — Info kurir dari kolom

**Files:**
- Modify: `resources/views/livewire/merchant/penerimaan.blade.php` (baris 161–171)

- [ ] **Step 1: Ganti parsing `catatan` dengan baca kolom langsung**

Cari blok berikut di sekitar baris 161–171:

```blade
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Status Logistik Pemasok</p>
                            @if($order->catatan && str_contains($order->catatan, '[UPDATE LOGISTIK]'))
                                <p class="text-xs font-bold text-blue-600 bg-blue-50 p-2 rounded-lg border border-blue-100 inline-block">
                                    {{ str_replace('[UPDATE LOGISTIK]', '', $order->catatan) }}
                                </p>
                            @else
                                <p class="text-xs font-bold text-gray-400 italic">Belum ada info kurir/resi</p>
                            @endif
                        </div>
```

Ganti dengan:

```blade
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Info Kurir</p>
                            @if($order->nama_kurir)
                                <div class="text-xs font-bold text-blue-600 bg-blue-50 p-2 rounded-lg border border-blue-100 space-y-0.5">
                                    <p>👤 {{ $order->nama_kurir }}</p>
                                    <p>📞 <a href="tel:{{ $order->no_hp_kurir }}" class="underline">{{ $order->no_hp_kurir }}</a></p>
                                    <p class="text-[10px] text-blue-500">Resi: {{ $order->no_resi }}</p>
                                </div>
                            @else
                                <p class="text-xs font-bold text-gray-400 italic">Belum ada info kurir</p>
                            @endif
                        </div>
```

- [ ] **Step 2: Manual verifikasi sisi merchant**

Login sebagai merchant yang menerima order dari pemasok yang sudah dikirim di Task 4 Step 2.

Buka halaman Penerimaan Logistik → tab "Sedang Proses". Cek pada order yang baru dikirim:
- Section "Info Kurir" tampil dengan icon 👤 nama, 📞 HP (clickable `tel:` link), dan Resi.
- Order lama (sebelum migration / null kolom kurir) tampil "Belum ada info kurir".

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/merchant/penerimaan.blade.php
git commit -m "feat(merchant): tampilkan info kurir dari kolom dedicated"
```

---

## Task 7: End-to-end verifikasi manual

- [ ] **Step 1: Test golden path lengkap**

Dengan dua akun (1 pemasok + 1 merchant), buat order baru dari sisi merchant, approve sampai status `diproses_pemasok`. Lalu:

1. **Pemasok:** Halaman Pengiriman → klik "Kirim Barang" → modal terbuka tanpa dropdown kurir, dua input baru tampil.
2. **Pemasok:** Submit nama="Budi Santoso", HP="081234567890" → order pindah ke "Sedang Jalan".
3. **DB cek:**
   ```bash
   php artisan tinker --execute="dd(\App\Models\SupplyOrder::latest()->first()->only(['status','nama_kurir','no_hp_kurir','no_resi','catatan']));"
   ```
   Expected: `status=dikirim`, `nama_kurir='Budi Santoso'`, `no_hp_kurir='081234567890'`, `no_resi` ada nilainya, `catatan` tidak berubah (atau null kalau memang null).
4. **Merchant:** Halaman Penerimaan → tab "Sedang Proses" → cek section "Info Kurir" tampil 👤 Budi Santoso, 📞 081234567890 (link tel:), Resi SCFS-XXXXXX.
5. **Pemasok:** Klik Cetak Surat Jalan dari tab "Perlu Dikirim" untuk order lain. Jika belum dikirim, info kurir kosong. Acceptable.

- [ ] **Step 2: Test validasi**

Buat order baru di status `diproses_pemasok`. Buka modal Kirim Barang. Test:

| Input nama_kurir | Input no_hp_kurir | Expected |
|---|---|---|
| (kosong) | 081234567890 | Error nama wajib |
| Budi | (kosong) | Error HP wajib |
| Budi | 12345 | Error HP harus 10-15 digit |
| Budi | abc123def4 | Error HP harus digit |
| Budi | 081234567890 | Sukses |

- [ ] **Step 3: Test data lama (backward compat)**

Cari order existing yang sudah berstatus `dikirim` sebelum migration (kalau ada). Di view merchant, harus tampil "Belum ada info kurir" karena `nama_kurir` null. Old info di `catatan` (`[UPDATE LOGISTIK]\nDikirim via: ...`) tetap di DB tapi tidak ditampilkan. Acceptable per spec section 7.

Jika tidak ada order lama untuk dites, skip. Migration cuma menambah nullable column, jadi order lama otomatis punya `nama_kurir=null`.

- [ ] **Step 4: Tidak ada commit di task ini** (manual verification only)

---

## Self-Review Notes

- **Spec coverage:** Section 3 (Skema) → Task 1. Section 4 (Backend pemasok) → Task 2. Section 5 (Frontend pemasok modal) → Task 4. Section 5 (Surat Jalan) → Task 5. Section 6 (Frontend merchant) → Task 6. Section 7 (Validasi & Edge Case) → Task 3 + Task 7. Section 8 (Testing) → Task 3 + Task 7.
- **Placeholder scan:** Migration filename pakai `YYYY_MM_DD_HHMMSS` karena Laravel generate timestamp otomatis via `make:migration` — bukan placeholder yang user harus isi.
- **Type consistency:** Property `$nama_kurir`, `$no_hp_kurir`, kolom DB `nama_kurir`, `no_hp_kurir` konsisten di semua task.
