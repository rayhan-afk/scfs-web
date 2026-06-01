# AUDIT MENDALAM — SISTEM CASH FLOW SCFS

> **Auditor:** Backend Architect & Database Analyst
> **Tanggal Audit:** 2026-06-01
> **Tanggal Remediasi:** 2026-06-01
> **Branch:** `main` (post-fix)
> **Scope:** POS Kasir, Tagihan Laci Tunai, Withdrawal E-Wallet
> **Stack:** Laravel 11 · Livewire Volt · MySQL · TailwindCSS

---

## STATUS REMEDIASI — RINGKAS

Semua temuan P0/P1/P2 dari audit awal sudah diperbaiki di commit yang sama dengan dokumen ini. Tabel di bawah membandingkan status **sebelum** vs **setelah** remediasi.

| Area | Status Awal | Status Setelah Perbaikan |
|------|-------------|---------------------------|
| POS Tunai (`prosesPembayaranTunai`) | 🟡 Layak dengan catatan | 🟢 **OK** — `uang_diterima` `required`, LedgerEntry `ACCRUAL_TUNAI` ditambahkan, `cart_snapshot` direkam. |
| POS QR Digital (`buatQrPembayaran` + `payQr`) | 🟡 Risiko stok-hilang permanen | 🟢 **OK** — `cart_snapshot` JSON + scheduled job `pos:expire-pending` (15 menit) restore stok otomatis. `payQr` verifikasi `merchant_id` + cek expiry + tidak auto-create wallet. |
| Tagihan Setoran Tunai (Laci) — sisi Merchant | 🟢 OK | 🟢 **OK** (tidak diubah) |
| Tagihan Setoran Tunai — sisi LKBB (`penagihan.blade.php`) | 🔴 BROKEN | 🟢 **OK** — ditulis ulang total, source `SetoranTunai`, `terimaSetoran()` dengan lock + decrement tagihan + audit petugas. |
| Withdrawal Merchant — Pengajuan | 🟢 OK | 🟢 **OK** (tidak diubah) |
| Withdrawal Merchant — Approve LKBB | 🔴 BUG KRITIS | 🟢 **OK** — `DB::transaction` + `lockForUpdate` + guard `status==='pending'` + isi `approved_by/approved_at`. |
| Withdrawal — Reject LKBB | 🟢 OK | 🟢 **OK** (tidak diubah) |
| Sistem Pencairan Paralel (`pencairan.blade.php`) | 🔴 DUPLIKASI BERBAHAYA | 🟢 **DIHAPUS** — file, route `keuangan.pencairan`, dan link sidebar dibuang. |

> **Catatan:** Body audit di bawah (§1–§3) dipertahankan **apa adanya** sebagai catatan diagnosa awal. §5 (baru) merangkum daftar perubahan teknis pasca-remediasi.

---

## 1. ARUS KAS POS — PEMBAYARAN TUNAI vs QR DIGITAL

### 1.1 File & Function yang Terlibat

| Komponen | Lokasi | Method Kunci |
|----------|--------|--------------|
| Volt Kasir Merchant | `resources/views/livewire/merchant/pos-merchant.blade.php` | `prosesPembayaranTunai()`, `buatQrPembayaran()`, `batalkanQrBayar()`, `cekStatusPembayaranQr()` |
| API Mahasiswa scan QR | `app/Http/Controllers/Api/MahasiswaAuthController.php` | `payQr(Request $request)` |
| Route API | `routes/api.php:23` | `POST /api/pay-qr` |
| Model | `app/Models/Transaction.php`, `app/Models/MerchantProduct.php`, `app/Models/MerchantProfile.php`, `app/Models/Wallet.php` | — |

### 1.2 Cara Sistem Membedakan Tunai vs QR

Pembedaan dilakukan **di sisi UI Livewire** lewat property `$metode_pembayaran` (`'tunai'` atau `'digital'`) — bukan column DB.

```php
// pos-merchant.blade.php (Volt class)
public $metode_pembayaran = 'digital';

// Tab UI memicu method berbeda:
//   'tunai'   → prosesPembayaranTunai()
//   'digital' → buatQrPembayaran()
```

Di tabel `transactions`, perbedaan terekam pada kolom `type`:

| Skenario | `type` | `status` awal |
|----------|--------|---------------|
| Kasir terima uang fisik | `pembayaran_makanan_tunai` | `sukses` (langsung) |
| Kasir generate QR | `pembayaran_makanan` | `pending` (menunggu scan mhs) |
| Setelah mahasiswa scan & sukses | `pembayaran_makanan` | `sukses` (di-update API `payQr`) |

### 1.3 Alur TUNAI Step-by-Step (`prosesPembayaranTunai`)

```php
// pos-merchant.blade.php:101-153
DB::transaction(function () {
    $merchant = MerchantProfile::where('user_id', Auth::id())
                    ->lockForUpdate()->firstOrFail();

    foreach ($this->cart as $item) {
        $realProduct = MerchantProduct::where('merchant_id', $merchant->user_id)
                        ->lockForUpdate()->findOrFail($item['id']);

        if ($realProduct->stok < $item['qty'])
            throw new \Exception("Stok {$realProduct->nama_produk} habis.");

        $realProduct->decrement('stok', $item['qty']);

        // ... akumulasi total_amount, total_pokok, profit
    }

    $feeLKBB       = ($dbTotalProfit * $persentaseLKBB) / 100;
    $tagihanKeLKBB = $dbTotalPokok + $feeLKBB;

    $merchant->increment('tagihan_setoran_tunai', $tagihanKeLKBB);

    Transaction::create([
        'order_id'     => 'UMM-' . strtoupper(uniqid()),
        'user_id'      => Auth::id(),
        'merchant_id'  => $merchant->user_id,
        'type'         => 'pembayaran_makanan_tunai',
        'total_amount' => $dbTotalAmount,
        'total_pokok'  => $dbTotalPokok,
        'fee_lkbb'     => $feeLKBB,
        'status'       => 'sukses',
        'description'  => '[UMUM] ...'
    ]);
});
```

**Yang Dicatat di Tabel `transactions`:**
`order_id`, `user_id` (= merchant sendiri, karena pembeli umum), `merchant_id`, `type`, `total_amount`, `total_pokok`, `fee_lkbb`, `status='sukses'`, `description`.
Kolom `sender_wallet_id` & `receiver_wallet_id` **tidak diisi** untuk transaksi tunai — yang benar karena tidak ada wallet digital yang terlibat.

### 1.4 Analisis Bug — POS Tunai

| # | Severity | Temuan | Lokasi | Dampak |
|---|----------|--------|--------|--------|
| 1.1 | 🟢 OK | Sudah `DB::transaction` + `lockForUpdate` pada merchant & product | `pos-merchant.blade.php:107-113` | Aman dari race condition |
| 1.2 | 🟡 Sedang | `validate(['uang_diterima' => 'numeric|min:'...])` **tidak `required`** — kalau input kosong/null, rule `numeric` di Laravel **lolos** (null bukan non-numeric). | line 104 | Kasir bisa proses checkout tanpa mengisi uang diterima. Bukan kerugian uang (tagihan tetap masuk), tapi struk tidak akurat. **Fix:** tambahkan `'required'`. |
| 1.3 | 🟡 Sedang | **Tidak ada Audit Log / LedgerEntry** untuk transaksi tunai. CLAUDE.md mensyaratkan "SEMUA payment event wajib di-audit log". | line 134-144 | Tidak ada immutable trail untuk transaksi tunai. Hanya `Transaction` row biasa yang **bisa di-`update`/`delete`**. |
| 1.4 | 🟢 OK | `total_pokok` direkam di kolom dedicated — basis penghitungan modal vs profit eksplisit. | line 140 | Konsisten dengan migration `2026_05_20_110547_add_total_pokok_to_transactions_table.php` |
| 1.5 | 🟡 Rendah | `order_id` pakai `uniqid()` — bukan UUID, urutan waktu predictable. | line 135 | Tidak kritikal untuk POS, tapi rentan enumerasi jika dipublish. |

### 1.5 Alur QR DIGITAL Step-by-Step

```php
// pos-merchant.blade.php:158-213 — buatQrPembayaran()
DB::transaction(function () {
    foreach ($this->cart as $item) {
        $realProduct = MerchantProduct::...->lockForUpdate()->findOrFail($item['id']);
        $realProduct->decrement('stok', $item['qty']);  // ⚠️ STOK DIPOTONG SEKARANG
        // ...
    }
    $this->pendingOrderId = 'DIG-' . strtoupper(uniqid());
    Transaction::create([
        'type'   => 'pembayaran_makanan',
        'status' => 'pending',
        // ...
    ]);
    $this->qrPayloadString = json_encode([...]);
    $this->showQrModal = true;
});
```

Mahasiswa scan QR → Flutter POST `/api/pay-qr`:

```php
// MahasiswaAuthController.php:92-169
public function payQr(Request $request) {
    DB::transaction(function () use ($request) {
        $profileMhs = MahasiswaProfile::where('user_id', $mahasiswa->id)
                        ->lockForUpdate()->firstOrFail();

        $trx = Transaction::where('order_id', $request->order_id)
                ->where('status', 'pending')
                ->lockForUpdate()->first();

        if (!$trx) throw new \Exception('Transaksi tidak ditemukan/kadaluwarsa.');
        if ($profileMhs->saldo < $trx->total_amount) throw new \Exception('Saldo tidak cukup.');

        $profileMhs->decrement('saldo', $trx->total_amount);

        $hakLkbb     = $trx->total_pokok + $trx->fee_lkbb;
        $hakMerchant = ($trx->total_amount - $trx->total_pokok) - $trx->fee_lkbb;

        $merchantProfile = MerchantProfile::where('user_id', $trx->merchant_id)
                            ->lockForUpdate()->firstOrFail();
        $merchantProfile->increment('saldo_token', $hakMerchant);

        $walletOperasional = Wallet::where('type', 'LKBB_OPERATIONAL')->first();
        if ($walletOperasional) {
            $walletOperasional->increment('balance', $hakLkbb);
        } else {
            Wallet::create([
                'type' => 'LKBB_OPERATIONAL',
                'balance' => $hakLkbb
            ]);
        }

        $trx->update([
            'user_id' => $mahasiswa->id,
            'status'  => 'sukses'
        ]);
    });
}
```

Kasir UI poll setiap 2 detik via `wire:poll.2s="cekStatusPembayaranQr"` → setelah `status=sukses` modal ditutup & cart dibersihkan.

### 1.6 Analisis Bug — POS QR Digital

| # | Severity | Temuan | Lokasi | Dampak |
|---|----------|--------|--------|--------|
| 2.1 | 🔴 **TINGGI** | **Stok dipotong saat QR dibuat** (`buatQrPembayaran` → `decrement('stok')`), padahal mahasiswa belum scan. Jika mhs tidak pernah scan **dan** kasir menutup browser tanpa klik "Batalkan", stok terkunci permanen di `Transaction` pending. | `pos-merchant.blade.php:172` | Stok-loss permanen. **Fix:** ➀ hold stok via row terpisah (`stok_dipending`), atau ➁ jadwalkan job auto-expire pending QR > X menit, atau ➂ jangan potong stok hingga `payQr` sukses dan handle conflict di sana. |
| 2.2 | 🟠 Sedang | **Tidak ada expiry pada Transaction `pending`.** `payQr` hanya cek `status=pending`, tidak ada cek `created_at`. | API `payQr` + DB | Mahasiswa bisa scan QR 1 minggu yang lalu dan tetap berhasil bayar selama `pending`. **Fix:** tambah scope `where('created_at', '>', now()->subMinutes(15))` atau scheduled job. |
| 2.3 | 🟠 Sedang | **`payQr` membuat wallet `LKBB_OPERATIONAL` auto** jika belum ada (`Wallet::create([...])`), tanpa `user_id`, `account_number`, `pin`. | `MahasiswaAuthController.php:144-148` | Mismatch fillable; baris bisa lolos karena `user_id` nullable. Tapi ini **silent fix** yang menyembunyikan masalah seeding/setup awal. **Fix:** gagalkan transaksi & log error kalau wallet operasional tidak ada — itu konfigurasi infra, bukan urusan runtime. |
| 2.4 | 🟡 Sedang | **`payQr` tidak verifikasi `merchant_id` di payload QR vs `trx->merchant_id`.** Selama `order_id` valid, scan dari device manapun lolos. | `MahasiswaAuthController.php:107-110` | QR yang bocor (foto, screenshot) bisa dibayar mahasiswa lain — hilang sesi konteks "siapa makan apa". |
| 2.5 | 🟡 Rendah | `buatQrPembayaran` lock product **tidak lock merchant_profile**. `payQr` baru lock merchant_profile. | line 164 | Aman karena `saldo_token` tidak diubah di `buatQrPembayaran`, tapi tidak konsisten dengan pola POS Tunai. |
| 2.6 | 🟢 OK | `batalkanQrBayar` me-restore stok + delete trx dengan cek `status=pending`. | line 215-230 | Aman dari double-cancel. Tetapi rely on user klik "Batalkan" — lihat 2.1. |

---

## 2. MANAJEMEN UANG LACI & TAGIHAN KANTIN

### 2.1 Skema Database

`merchant_profiles` punya kolom `tagihan_setoran_tunai DECIMAL(15,2) DEFAULT 0` (migration `2026_02_23_034301_create_merchant_profiles_table.php`).

**Makna kolom:** uang fisik di laci kantin yang merupakan **hak LKBB** (modal barang + fee bagi-hasil). Bukan total uang fisik di laci — bagian profit kantin tidak masuk sini (sudah jadi hak fisik kantin).

### 2.2 Kapan Bertambah?

**Hanya satu sumber:** `prosesPembayaranTunai()` di `pos-merchant.blade.php:132`:

```php
$tagihanKeLKBB = $dbTotalPokok + $feeLKBB;
$merchant->increment('tagihan_setoran_tunai', $tagihanKeLKBB);
```

### 2.3 Kapan Berkurang?

Tiga jalur (dua yang valid, satu yang broken):

| Jalur | File | Method | Status |
|-------|------|--------|--------|
| ➀ Kompensasi via Withdraw E-Wallet (potong tagihan otomatis) | `merchant/withdraw.blade.php:117-119` | `ajukanPencairan()` | 🟢 OK |
| ➁ Refund jika withdraw ditolak LKBB | `lkbb/keuangan/withdraw-merchant-approval.blade.php:77-79` | `reject()` | 🟢 OK |
| ➂ Setor uang fisik ke admin LKBB | `lkbb/keuangan/penagihan.blade.php` | `terimaSetoran()` | 🔴 **BROKEN** |

### 2.4 Alur Setor Tunai (Saat Ini)

**Sisi Merchant** (`resources/views/livewire/merchant/setoran.blade.php`) — **berfungsi**:

1. Merchant klik "Panggil Petugas LKBB".
2. `panggilPetugas()` membuat row `SetoranTunai` baru:
   ```php
   SetoranTunai::create([
       'nomor_setoran' => 'ST-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
       'merchant_id'   => $merchant->user_id,
       'nominal'       => $merchant->tagihan_setoran_tunai,
       'status'        => 'menunggu_penjemputan'
   ]);
   ```
3. `lockForUpdate` + idempotency check anti spam request. 🟢

**Sisi LKBB** (`lkbb/keuangan/penagihan.blade.php`) — **tidak berfungsi**. Lihat §2.5.

### 2.5 🔴 BUG KRITIS — `penagihan.blade.php`

File ini bocor di berbagai level. Berikut bukti:

#### 2.5.1 Mismatch Model — query tidak akan pernah match data nyata

```php
// penagihan.blade.php:33-46 — riwayatHariIni()
$tagihan = Transaction::with('user')
    ->where('type', 'tagihan_merchant')   // ⚠️ Tipe ini tidak pernah dibuat!
    ->where('status', $this->tab)
    ->...
```

Sistem POS membuat `type='pembayaran_makanan_tunai'`, bukan `'tagihan_merchant'`. Akibatnya **tab "Belum Disetor" selalu kosong** di produksi (hanya terisi via "Simulasi Tagihan" manual).

Padahal sumber kebenaran adalah `setoran_tunais` (tiket penjemputan) dan `merchant_profiles.tagihan_setoran_tunai`. Halaman ini seharusnya membaca dua tabel itu, **bukan** `transactions`.

#### 2.5.2 Variabel Undefined — `terimaSetoran()` mustahil sukses

```php
// penagihan.blade.php:61-87
public function terimaSetoran($transactionId)
{
    $trx = Transaction::with('user')->find($transactionId);
    if (!$trx || $trx->status !== 'pending') return;

    try {
        DB::transaction(function () use ($setId, $petugas) {   // ⚠️ $setId & $petugas tidak ada di scope!
            $setoran = SetoranTunai::where('id', $setId)->lockForUpdate()->firstOrFail();
            // ...
        });
        // ...
        $this->isModalOpen = false;          // ⚠️ property tidak pernah dideklarasikan
        $this->selectedSetoranId = null;     // ⚠️ idem
        $this->nama_petugas = '';            // ⚠️ idem
        unset($this->tiketPending);          // ⚠️ computed tidak ada
        // ...
    }
}
```

`$setId` & `$petugas` tidak pernah didefinisikan. Closure `use($setId, $petugas)` akan throw **"Undefined variable"** saat dipanggil. Method ini akan **selalu** crash sebelum sentuh DB.

#### 2.5.3 Import Hilang

```php
// penagihan.blade.php:10-12
new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;   // ⚠️ tidak ada `use Livewire\WithPagination;`
```

`Transaction`, `User`, `Str` juga dipakai (line 37, 51, 116) tapi tidak di-import — bakal "Class not found" pada runtime.

#### 2.5.4 Blade Markup Rusak

```blade
{{-- penagihan.blade.php:142-146 — form simulasi tidak ditutup --}}
            <button ...>+ Simulasi Tagihan</button>
        </div>
    @endif                                       {{-- ⚠️ @endif tanpa @if pasangannya --}}
    @if(session('error'))

{{-- line 208-212 — closing tag chaos --}}
    @empty
        <li class="p-8 text-center ...">Belum ada uang fisik...</li>   {{-- ⚠️ <li> di dalam <tbody>!  --}}
    @endforelse
        </ul>                                    {{-- ⚠️ </ul> menutup <table>? --}}
    </div>
</div>
```

Halaman akan menghasilkan **error compile Blade** atau HTML invalid. Dalam kondisi sekarang, route `lkbb/keuangan/penagihan` **tidak boleh dibuka di produksi**.

#### 2.5.5 Kesimpulan §2

> **Belum ada cara admin LKBB melunasi `tagihan_setoran_tunai` lewat "Terima Uang Fisik".** Reset tagihan hanya bisa terjadi otomatis lewat fitur Withdraw merchant (potong tagihan). Untuk merchant yang tidak pernah withdraw, hutangnya **akan membesar terus tanpa cara menghapus secara sah**.
>
> Halaman LKBB `penagihan.blade.php` harus **ditulis ulang dari nol** untuk membaca `SetoranTunai` + update `MerchantProfile.tagihan_setoran_tunai`.

---

## 3. SISTEM PENARIKAN DANA (WITHDRAWAL)

### 3.1 Model & Migration

| Aset | Lokasi |
|------|--------|
| Model | `app/Models/Withdrawal.php` |
| Migration | `database/migrations/2026_03_02_043438_create_withdrawals_table.php` |

Skema:
```php
Schema::create('withdrawals', function (Blueprint $table) {
    $table->id();
    $table->string('nomor_pencairan')->unique();          // anti-duplicate
    $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
    $table->decimal('nominal_kotor', 15, 2);              // total dipotong dari saldo_token
    $table->decimal('potongan_lkbb', 15, 2);              // bagian untuk lunasi tagihan
    $table->decimal('nominal_bersih', 15, 2);             // yang ditransfer ke bank
    $table->string('info_pencairan');
    $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
    $table->text('catatan_lkbb')->nullable();
    $table->timestamps();
});
```

### 3.2 Alur Pengajuan (Merchant Side)

`resources/views/livewire/merchant/withdraw.blade.php:53-129`:

```php
DB::transaction(function () use ($nominalBersih) {
    $merchant = MerchantProfile::where('user_id', Auth::id())
                    ->lockForUpdate()->firstOrFail();

    $saldoSaatIni = $merchant->saldo_token;
    $hutangSaatIni = $merchant->tagihan_setoran_tunai;

    if ($this->potong_tagihan && $hutangSaatIni > 0) {
        $batasMaksimal  = $saldoSaatIni - $hutangSaatIni;
        $potonganLKBB   = $hutangSaatIni;
        $kotorDipotong  = $nominalBersih + $hutangSaatIni;
    } else {
        $batasMaksimal  = $saldoSaatIni;
        $potonganLKBB   = 0;
        $kotorDipotong  = $nominalBersih;
    }

    if ($nominalBersih > $batasMaksimal) throw new \Exception('Saldo tidak mencukupi.');

    $adaPending = Withdrawal::where('merchant_id', $merchant->user_id)
                    ->where('status', 'pending')
                    ->lockForUpdate()->exists();
    if ($adaPending) throw new \Exception('Masih ada pengajuan antrean.');

    Withdrawal::create([
        'nomor_pencairan' => 'WD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
        'merchant_id'     => $merchant->user_id,
        'nominal_kotor'   => $kotorDipotong,
        'potongan_lkbb'   => $potonganLKBB,
        'nominal_bersih'  => $nominalBersih,
        'info_pencairan'  => $merchant->info_pencairan,
        'status'          => 'pending',
    ]);

    $merchant->decrement('saldo_token', $kotorDipotong);
    if ($potonganLKBB > 0) {
        $merchant->decrement('tagihan_setoran_tunai', $potonganLKBB);
    }
});
```

**Jawaban pertanyaan Anda:** Saldo **dipotong saat pengajuan dibuat (Pending)**, bukan saat approved.

### 3.3 Risiko Double-Click / Race Condition (Pengajuan)

🟢 **Aman**, karena:
1. `lockForUpdate` pada `MerchantProfile` → request kedua menunggu lock.
2. Cek `Withdrawal::where('status', 'pending')->exists()` di dalam lock → request kedua melihat row pending dari request pertama dan menolak.
3. `decrement()` adalah operasi atomic SQL (`saldo_token = saldo_token - X`).

Double-click standar dari browser akan diserialisasi dan request kedua throw "masih ada pengajuan antrean".

### 3.4 Alur Approve LKBB

`resources/views/livewire/lkbb/keuangan/withdraw-merchant-approval.blade.php:29-44`:

```php
public function approve($id)
{
    try {
        $wd = Withdrawal::findOrFail($id);

        $wd->update([
            'status' => 'disetujui',
            'catatan_lkbb' => 'Dana telah ditransfer ke rekening tujuan.'
        ]);

        session()->flash('success', 'Penarikan berhasil disetujui...');
    } catch (\Exception $e) {
        session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}
```

### 3.5 🔴 BUG KRITIS — Approve Tidak Aman

| Aspek | Status | Risiko |
|-------|--------|--------|
| `DB::transaction` | ❌ Tidak ada | Jika error tengah jalan, parsial commit |
| `lockForUpdate` | ❌ Tidak ada | Two LKBB user approve bersamaan → kedua-duanya sukses |
| Cek status sebelum update | ❌ Tidak ada | Bisa approve withdrawal yang sudah `ditolak`/`disetujui` |
| Audit trail siapa approver | ❌ Tidak ada | Tidak ada `approved_by`, `approved_at` |
| Idempotency token | ❌ Tidak ada | `wire:confirm` hanya UX, server tidak guard |

**Skenario Double-Approve:**
1. Admin A buka halaman, klik Approve withdrawal #42.
2. Admin B buka halaman bersamaan, klik Approve withdrawal #42 (network lag).
3. Keduanya hit method tanpa lock → keduanya `update(status=disetujui)`.
4. Tidak fatal dari sisi data (status sudah disetujui, no further side-effect), **tapi** dua orang mengira mereka yang harus transfer uang.
5. **Risiko bisnis:** uang ditransfer 2x ke bank merchant. Karena `nominal_bersih` real sudah ada di tangan merchant via Bank, **kerugian kas LKBB nyata**.

**Fix wajib:**
```php
public function approve($id)
{
    try {
        DB::transaction(function () use ($id) {
            $wd = Withdrawal::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($wd->status !== 'pending') {
                throw new \Exception('Pengajuan ini sudah diproses sebelumnya.');
            }

            $wd->update([
                'status'        => 'disetujui',
                'catatan_lkbb'  => 'Dana telah ditransfer ke rekening tujuan.',
                'approved_by'   => Auth::id(),   // tambah kolom
                'approved_at'   => now(),        // tambah kolom
            ]);
        });
        session()->flash('success', '...');
    } catch (\Throwable $e) {
        session()->flash('error', $e->getMessage());
    }
}
```

Migration tambahan:
```php
Schema::table('withdrawals', function (Blueprint $t) {
    $t->foreignId('approved_by')->nullable()->after('catatan_lkbb')
      ->constrained('users')->nullOnDelete();
    $t->timestamp('approved_at')->nullable()->after('approved_by');
});
```

### 3.6 Alur Reject LKBB — 🟢 OK

```php
// withdraw-merchant-approval.blade.php:55-92
public function reject() {
    $this->validate(['alasanTolak' => 'required|min:5']);
    DB::transaction(function () {
        $wd = Withdrawal::where('id', $this->selectedWithdrawalId)
                ->lockForUpdate()->firstOrFail();
        if ($wd->status !== 'pending') throw new \Exception('Status berubah.');

        $merchant = MerchantProfile::where('user_id', $wd->merchant_id)
                        ->lockForUpdate()->firstOrFail();

        $merchant->increment('saldo_token', $wd->nominal_kotor);
        if ($wd->potongan_lkbb > 0) {
            $merchant->increment('tagihan_setoran_tunai', $wd->potongan_lkbb);
        }

        $wd->update([
            'status'       => 'ditolak',
            'catatan_lkbb' => $this->alasanTolak
        ]);
    });
}
```

Pattern lock + transaction + status guard sudah benar. Mirror ini untuk `approve()`.

### 3.7 🔴 DUPLIKASI — `lkbb/keuangan/pencairan.blade.php`

Ada **halaman LKBB kedua untuk penarikan**: `pencairan.blade.php`. Mekanismenya **berbeda total**:

```php
// pencairan.blade.php:40-58
$withdrawals = Transaction::with('user')
    ->where('type', 'withdrawal')   // ⚠️ Beda model: Transaction, bukan Withdrawal
    ->where('status', $this->tab)
    ->...;

// pencairan.blade.php:72-122 — approveWithdrawal()
$walletType = match(true) {
    in_array($role, ['supplier', 'pemasok']) => 'SUPPLIER_WALLET',
    in_array($role, ['mahasiswa', 'student']) => 'STUDENT_WALLET',
    default => 'USER_WALLET'        // ⚠️ Tidak match value di DB
};

$wallet = Wallet::where('user_id', $trx->user_id)->where('type', $walletType)->first();
if (!$wallet || $wallet->balance < $trx->total_amount) {
    session()->flash('error', 'Saldo tidak mencukupi...');
    return;
}

DB::transaction(function () use ($trx, $wallet) {
    $trx->update(['status' => 'success']);
    $wallet->decrement('balance', $trx->total_amount);
    LedgerEntry::create([...]);
});
```

**Masalah berlapis:**

| # | Masalah | Bukti |
|---|---------|-------|
| 1 | Pakai `Transaction` (type=`withdrawal`) — **bukan** `Withdrawal`. Tidak akan menampilkan pengajuan dari `merchant/withdraw.blade.php`. | line 40-42 |
| 2 | Hard-coded type wallet `SUPPLIER_WALLET`/`STUDENT_WALLET`/`USER_WALLET`. Wallet sebenarnya pakai `MERCHANT`/`PEMASOK`/`MAHASISWA`/`LKBB_*` (lihat `Wallet::isMerchant()`). Query **selalu return null** → method exit dengan "saldo tidak mencukupi". | line 79-86 vs `app/Models/Wallet.php:51-53` |
| 3 | Decrement `wallets.balance`, sementara sistem produksi simpan saldo merchant di `merchant_profiles.saldo_token`. Dua sumber kebenaran. | line 99 |
| 4 | Saldo dipotong **saat approve**, padahal sistem produksi memotong **saat ajukan**. Jika dua sistem aktif paralel, akan terjadi double-deduct atau orphan saldo. | line 99 |
| 5 | Approve **bukan** dalam `lockForUpdate` di wallet/transaction → sama-sama race condition seperti §3.5. | line 94-109 |

**Verdict:** halaman `pencairan.blade.php` **dead code yang berbahaya**. Selama dia bisa diakses lewat menu, admin LKBB bisa tidak sengaja approve `Transaction::type=withdrawal` (hasil dari fitur "Simulasi Request" di halaman tsb) yang mendecrement saldo wallet yang tidak relevan.

**Rekomendasi:** **hapus** `pencairan.blade.php` & route-nya, atau migrasi penuh ke sistem `Withdrawal` (yang lebih lengkap kolom akuntansinya: kotor/potongan/bersih).

---

## 4. KESIMPULAN & PRIORITAS PERBAIKAN

### 4.1 Yang Sudah Bagus

- ✅ Pola `DB::transaction` + `lockForUpdate` konsisten di POS Tunai, POS QR, Pengajuan Withdraw, Reject Withdraw, `payQr` API, `panggilPetugas`.
- ✅ Split payment `pembayaran_makanan` (hak LKBB = pokok + fee; hak merchant = profit - fee) **akuntansinya akurat** dan tercermin di `total_amount`, `total_pokok`, `fee_lkbb`.
- ✅ Sistem **bertumpu pada `Withdrawal` (bukan `Transaction`)** sebagai sumber kebenaran withdraw merchant — desain ini benar.
- ✅ Anti pending-ganda di withdraw merchant (`Withdrawal::where('status','pending')->exists()` di dalam lock).
- ✅ Refund withdraw saat reject sudah benar (saldo + tagihan dikembalikan).

### 4.2 Yang Belum Ada

- ❌ **Mekanisme admin LKBB melunasi `tagihan_setoran_tunai` saat menerima uang fisik.** `penagihan.blade.php` rusak, belum bisa diandalkan.
- ❌ **Audit trail `approved_by` / `approved_at`** di `withdrawals`.
- ❌ **Job auto-expire transaksi `pembayaran_makanan` status=pending** (stok hilang permanen).
- ❌ **Audit log immutable** (LedgerEntry) untuk transaksi tunai dan POS Tunai.
- ❌ **Verifikasi merchant_id payload QR** vs trx pada `payQr`.

### 4.3 Bug Berbahaya — WAJIB Segera Diperbaiki

| Prioritas | Bug | File | Risiko Bisnis |
|-----------|-----|------|---------------|
| 🔴 P0 | `approve()` withdraw tanpa transaction/lock/guard status | `lkbb/keuangan/withdraw-merchant-approval.blade.php:29-44` | Double-transfer uang ke rekening merchant — **kerugian kas LKBB nyata** |
| 🔴 P0 | `penagihan.blade.php` broken total (undefined var, blade rusak, model salah) | `lkbb/keuangan/penagihan.blade.php` | Tagihan kantin tidak pernah bisa di-clear → halaman crash di produksi |
| 🔴 P0 | `pencairan.blade.php` (duplikat sistem withdraw, type wallet salah) | `lkbb/keuangan/pencairan.blade.php` | Admin bisa potong saldo wallet yang salah; dual source of truth |
| 🟠 P1 | Stok kepotong saat QR dibuat & tidak ada expiry | `pos-merchant.blade.php:172` + DB | Stok kantin hilang permanen kalau mhs abandon |
| 🟠 P1 | Tidak ada audit log untuk POS Tunai | `pos-merchant.blade.php:134-144` | Tidak bisa rekonsiliasi setoran fisik vs catatan |
| 🟡 P2 | `payQr` auto-create LKBB_OPERATIONAL wallet | `MahasiswaAuthController.php:144-148` | Menyembunyikan kesalahan setup; data wallet tidak konsisten |
| 🟡 P2 | `uang_diterima` tidak required | `pos-merchant.blade.php:104` | Struk tidak akurat |
| 🟡 P2 | Tidak ada verifikasi `merchant_id` di `payQr` | `MahasiswaAuthController.php:107-110` | QR yang bocor bisa dipakai oleh mahasiswa lain |

### 4.4 Roadmap Perbaikan yang Direkomendasikan

**Sprint 1 — Hentikan pendarahan (P0):**
1. Patch `approve()` withdrawal: bungkus `DB::transaction`, `lockForUpdate`, cek `status==='pending'`, tambah kolom & isi `approved_by/approved_at`.
2. Tulis ulang `penagihan.blade.php`:
   - Source: `SetoranTunai::where('status','menunggu_penjemputan')`.
   - Aksi `terimaSetoran($setoranId, $petugas)`: lock SetoranTunai + MerchantProfile, set status `selesai`, `decrement('tagihan_setoran_tunai', $setoran->nominal)`, catat `nama_petugas`.
3. Hapus / nonaktifkan route `pencairan.blade.php` agar tidak bertabrakan dengan sistem `Withdrawal`.

**Sprint 2 — Tutup celah POS QR (P1):**
1. Auto-expire pending transaksi QR > 15 menit via scheduled command + sertakan stok-restore.
2. Tambah `LedgerEntry` untuk POS Tunai & QR.

**Sprint 3 — Hardening (P2):**
1. Validasi `required` pada `uang_diterima` POS Tunai.
2. Cek `merchant_id` payload QR di `payQr`.
3. Wallet `LKBB_OPERATIONAL` diseed via seeder; `payQr` throw error eksplisit kalau tidak ada.

---

## LAMPIRAN A — Inventaris File Audit (kondisi awal)

```
app/Models/
  Transaction.php
  Withdrawal.php
  SetoranTunai.php
  MerchantProfile.php
  Wallet.php

app/Http/Controllers/Api/
  MahasiswaAuthController.php          (payQr)

database/migrations/
  2026_02_23_034301_create_merchant_profiles_table.php
  2026_02_26_034444_create_transactions_table.php
  2026_03_02_034528_add_merchant_and_fee_to_transactions_table.php
  2026_03_02_043438_create_withdrawals_table.php
  2026_03_04_111839_create_setoran_tunais_table.php
  2026_05_11_131159_alter_transactions_for_3_wallets.php
  2026_05_20_110547_add_total_pokok_to_transactions_table.php

resources/views/livewire/merchant/
  pos-merchant.blade.php
  setoran.blade.php
  withdraw.blade.php

resources/views/livewire/lkbb/keuangan/
  withdraw-merchant-approval.blade.php
  penagihan.blade.php       ← BROKEN (kondisi audit awal)
  pencairan.blade.php       ← DUPLIKAT/DEAD (kondisi audit awal)

routes/api.php               (POST /api/pay-qr)
```

---

## 5. LOG REMEDIASI (2026-06-01)

Bagian ini mendokumentasikan **apa yang berubah** setelah audit, lengkap dengan dampak teknis dan operasional. Semua perubahan satu commit, ter-link dari ringkasan eksekusi di atas.

### 5.1 Database — Migration Baru

| File | Perubahan |
|------|-----------|
| `database/migrations/2026_06_01_120000_add_approval_audit_to_withdrawals.php` | Tambah `withdrawals.approved_by` (FK `users.id` nullable, `nullOnDelete`) + `approved_at` timestamp. |
| `database/migrations/2026_06_01_120500_add_cart_snapshot_to_transactions.php` | Tambah `transactions.cart_snapshot` JSON nullable. Berisi `[{product_id, nama_produk, qty, harga_jual, harga_pokok}, …]`. Sumber kebenaran untuk job auto-expire QR pending. |

Migrasi sudah dijalankan (`php artisan migrate --force`).

### 5.2 Model — Update Fillable/Cast

| File | Perubahan |
|------|-----------|
| `app/Models/Withdrawal.php` | `$fillable` tambah `approved_by`, `approved_at`. `$casts` tambah `approved_at => datetime`. Relasi baru `approver(): belongsTo(User, approved_by)`. |
| `app/Models/Transaction.php` | `$fillable` tambah `cart_snapshot`. `$casts` tambah `cart_snapshot => array`. |

### 5.3 P0 — Bug Kritis: Sudah Ditutup

**1. `withdraw-merchant-approval.blade.php` — `approve()`**

```php
// BEFORE: rentan double-approve (Volt class)
public function approve($id) {
    $wd = Withdrawal::findOrFail($id);
    $wd->update(['status' => 'disetujui', 'catatan_lkbb' => '...']);
}

// AFTER
public function approve($id) {
    DB::transaction(function () use ($id) {
        $wd = Withdrawal::where('id', $id)->lockForUpdate()->firstOrFail();
        if ($wd->status !== 'pending') {
            throw new \Exception('Pengajuan ini sudah diproses sebelumnya.');
        }
        $wd->update([
            'status'       => 'disetujui',
            'catatan_lkbb' => 'Dana telah ditransfer ke rekening tujuan.',
            'approved_by'  => Auth::id(),
            'approved_at'  => now(),
        ]);
    });
}
```

**2. `penagihan.blade.php` — ditulis ulang total**

Sumber: `SetoranTunai` (bukan `Transaction::type=tagihan_merchant` yang tidak pernah dibuat).
Aksi: modal konfirmasi → input `nama_petugas` (validasi `required|min:3`) → `DB::transaction` lock `SetoranTunai` + `MerchantProfile`, set status `selesai`, set `nama_petugas`, decrement `tagihan_setoran_tunai` (clamp 0).
Tab UI: `menunggu_penjemputan` / `selesai`. Pagination + search by nomor tiket/nama kantin. Ringkasan total hutang antrean di header.

**3. `pencairan.blade.php` — dihapus permanen**

| Path | Status |
|------|--------|
| `resources/views/livewire/lkbb/keuangan/pencairan.blade.php` | Dihapus (file) |
| Route `Volt::route('/keuangan/pencairan', …)` di `routes/web.php:148` | Dihapus |
| Link sidebar "Log Pencairan Selesai" di `lkbb-sidebar.blade.php:288-290` | Dihapus |
| Logika `$isKeuanganActive` (sidebar) | Disederhanakan, tidak lagi cek `keuangan/pencairan*`. |

### 5.4 P1 — Tutup Celah Operasional

**1. Scheduled command auto-expire**

`app/Console/Commands/ExpirePendingPosTransactions.php` (baru):

```php
php artisan pos:expire-pending --minutes=15
```

- Query `Transaction::where('type','pembayaran_makanan')->where('status','pending')->where('created_at','<', now()->subMinutes($m))`.
- Per row: `lockForUpdate`, guard ulang `status==='pending'`, iterasi `cart_snapshot` → `MerchantProduct::increment('stok', qty)`, ubah `status='expired'` + append `[AUTO-EXPIRED]` ke description.
- Skip silently bila balapan kalah lock & status sudah berubah.
- Per-row try/catch + `report($e)` agar satu row gagal tidak hentikan batch.

Schedule (`routes/console.php`):

```php
Schedule::command('pos:expire-pending --minutes=15')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

> **WAJIB di production**: scheduler Laravel harus aktif (`* * * * * php artisan schedule:run` di cron OS, atau `php artisan schedule:work` jika pakai supervisor/queue worker).

**2. LedgerEntry untuk POS**

| Skenario | Wallet | Entry Type | Amount |
|----------|--------|------------|--------|
| POS Tunai (`prosesPembayaranTunai`) | `LKBB_OPERATIONAL` | `ACCRUAL_TUNAI` | `total_pokok + fee_lkbb` (klaim tagihan, belum realized) |
| POS QR sukses (`payQr` API) | `LKBB_OPERATIONAL` | `CREDIT` | `total_pokok + fee_lkbb` (realized) |

Field `balance_after` diisi nilai wallet **setelah** increment (untuk QR — realized cash) atau wallet **sebelum** perubahan (untuk Tunai — accrual, balance wallet tidak berubah).

**3. `cart_snapshot` di POS Tunai & QR**

- `prosesPembayaranTunai` & `buatQrPembayaran` sekarang menyusun array `cartSnapshot[]` lalu menyimpan ke kolom `transactions.cart_snapshot`.
- `batalkanQrBayar` membaca `$trx->cart_snapshot` (bukan `$this->cart` in-memory) sebelum `increment('stok', qty)` — restore tetap akurat meskipun Livewire state hilang.

### 5.5 P2 — Hardening

| # | Lokasi | Perubahan |
|---|--------|-----------|
| 1 | `pos-merchant.blade.php:prosesPembayaranTunai` | `uang_diterima` jadi `required|numeric|min:total` dengan pesan custom Bahasa Indonesia. |
| 2 | `MahasiswaAuthController.php:payQr` | Validasi `merchant_id` `required|integer` + cocokkan dengan `$trx->merchant_id` (anti QR bocor lintas merchant). Cek expiry `created_at > now()->subMinutes(15)` di query — sinkron dengan job auto-expire. |
| 3 | `MahasiswaAuthController.php:payQr` | Tidak auto-create `Wallet[LKBB_OPERATIONAL]`. Bila tidak ada → `throw new \Exception('Wallet LKBB_OPERATIONAL belum dikonfigurasi.')`. |
| 4 | `database/seeders/DatabaseSeeder.php` | Seed 3 wallet inti LKBB (`OPERATIONAL`, `DONATION`, `INVESTMENT`) via `firstOrCreate` agar payQr tidak gagal di runtime. Aman di-rerun. |

### 5.6 ⚠️ Breaking Change Mobile Client

Endpoint `POST /api/pay-qr` sekarang **wajib** body:

```json
{
  "order_id": "DIG-XXXXX",
  "merchant_id": 12
}
```

Sebelumnya hanya `order_id`. QR payload yang di-generate kasir sudah encode `merchant_id` (lihat `pos-merchant.blade.php:buatQrPembayaran`), jadi Flutter app perlu:

1. Parse JSON string dari QR.
2. Forward `merchant_id` ke request body.

Server akan menolak `400` dengan pesan **"QR tidak valid untuk merchant ini."** jika `merchant_id` payload tidak cocok dengan transaksi.

### 5.7 Verifikasi yang Sudah Dilakukan

- `php -l` semua file PHP yang diubah → no syntax errors.
- `php artisan migrate --force` → 2 migration baru applied.
- `php artisan route:list` → route `keuangan.pencairan` hilang, `keuangan.penagihan` + `lkbb.withdraw.merchant.approval` aktif.
- `php artisan view:clear && optimize:clear` → cache view bersih.
- Tinker check: `Wallet::where('type','LKBB_OPERATIONAL')->exists()` → `true`.

### 5.8 Yang Tidak Dilakukan (Tetap Dibiarkan)

- Sisi Pemasok (`withdraw-pemasok-approval`, `pemasok/tarik-dana`) **tidak diaudit/diperbaiki** pada sesi ini — di luar scope eksplisit user.
- `Wallet` schema migration tidak diubah. `account_number` dan `pin` untuk wallet LKBB diisi dummy (`LKBB-OPERATIONAL`, dst.) via seeder. Bila ada constraint operasional khusus untuk wallet LKBB (mis. PIN), perlu seeder follow-up.
- `LedgerEntry` masih FK ke `wallets`. Mahasiswa (`mahasiswa_profiles.saldo`) dan Merchant (`merchant_profiles.saldo_token`) **belum** punya ledger karena tidak punya wallet row. Untuk audit penuh dua sisi, pertimbangkan migrasi ke pure-wallet (semua aktor punya `Wallet`) atau perluas `LedgerEntry` agar wallet nullable + ref polymorphic. Bukan blocker P0/P1.

---

## LAMPIRAN B — File Baru/Diubah (Pasca-Remediasi)

```
NEW:
  database/migrations/2026_06_01_120000_add_approval_audit_to_withdrawals.php
  database/migrations/2026_06_01_120500_add_cart_snapshot_to_transactions.php
  app/Console/Commands/ExpirePendingPosTransactions.php

MODIFIED:
  app/Models/Transaction.php
  app/Models/Withdrawal.php
  app/Http/Controllers/Api/MahasiswaAuthController.php   (payQr — verify merchant, no auto-create wallet, LedgerEntry, expiry)
  database/seeders/DatabaseSeeder.php                    (+3 LKBB wallets)
  resources/views/livewire/merchant/pos-merchant.blade.php   (cart_snapshot, LedgerEntry tunai, uang_diterima required, batalkanQrBayar via snapshot)
  resources/views/livewire/lkbb/keuangan/withdraw-merchant-approval.blade.php   (approve() di-harden)
  resources/views/livewire/lkbb/keuangan/penagihan.blade.php   (REWRITE TOTAL)
  resources/views/livewire/layout/lkbb-sidebar.blade.php   (hapus link Pencairan)
  routes/web.php                                         (hapus route keuangan.pencairan)
  routes/console.php                                     (schedule pos:expire-pending)

DELETED:
  resources/views/livewire/lkbb/keuangan/pencairan.blade.php
```

