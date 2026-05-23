# Manajemen Produk — Fix Bug Modal ×100 & Margin Persentase — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hilangkan bug pembengkakan `harga_modal` ×100 saat edit produk, ubah margin pemasok dari rupiah tetap menjadi persentase pilihan, dan pisahkan tampilan harga satuan vs total modal.

**Architecture:** Satu migration mengubah skema `produk_pemasoks` (`harga_modal` jadi integer, `margin_pemasok` rupiah → `margin_persen` persen). Konversi rupiah→persen terjadi sekali di migration; konversi persen→rupiah terjadi saat order di `order-bahan` dan disimpan sebagai snapshot rupiah, sehingga seluruh halaman hilir (`pesanan-masuk`, `pengiriman-logistik`, `approval-po`, `dashboard/pemasok`, `katalog`) tidak tersentuh.

**Tech Stack:** Laravel 11, Livewire 3 (class component + Volt), Alpine.js, Tailwind, MySQL.

**Spec:** `docs/superpowers/specs/2026-05-22-manajemen-produk-margin-persen-design.md`

**Prasyarat:** MySQL Laragon harus menyala sebelum menjalankan migration/verifikasi.

---

### Task 1: Branch + Migration skema

**Files:**
- Create: `database/migrations/2026_05_22_000002_revisi_modal_margin_produk_pemasok.php`

- [ ] **Step 1: Buat branch kerja**

Run:
```
git checkout -b fix/manajemen-produk-margin-persen
```
Expected: `Switched to a new branch 'fix/manajemen-produk-margin-persen'`

- [ ] **Step 2: Buat file migration**

Create `database/migrations/2026_05_22_000002_revisi_modal_margin_produk_pemasok.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * - harga_modal: decimal(15,2) -> unsignedBigInteger (rupiah bulat, buang sufiks .00
     *   yang memicu bug mask Rupiah ×100).
     * - margin_pemasok (rupiah) -> margin_persen (persen dari modal).
     */
    public function up(): void
    {
        // 1. Tambah kolom margin_persen
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->decimal('margin_persen', 5, 2)->default(0)->after('harga_modal');
        });

        // 2. Backfill: konversi margin rupiah lama -> persen dari modal
        DB::statement('
            UPDATE produk_pemasoks
            SET margin_persen = ROUND(margin_pemasok / NULLIF(harga_modal, 0) * 100, 2)
            WHERE harga_modal > 0
        ');

        // 3. Drop kolom margin_pemasok lama (rupiah)
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->dropColumn('margin_pemasok');
        });

        // 4. harga_modal jadi integer rupiah bulat
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->unsignedBigInteger('harga_modal')->default(0)->change();
        });
    }

    /**
     * Tidak sepenuhnya reversibel: konversi rupiah->persen menghilangkan nilai
     * rupiah margin asli. down() hanya mengembalikan bentuk kolom.
     */
    public function down(): void
    {
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->decimal('harga_modal', 15, 2)->default(0)->change();
        });
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->decimal('margin_pemasok', 15, 2)->default(0)->after('harga_modal');
        });
        Schema::table('produk_pemasoks', function (Blueprint $table) {
            $table->dropColumn('margin_persen');
        });
    }
};
```

- [ ] **Step 3: Commit**

```
git add database/migrations/2026_05_22_000002_revisi_modal_margin_produk_pemasok.php
git commit -m "feat: migration ubah harga_modal jadi integer & margin jadi persen"
```

---

### Task 2: Model ProdukPemasok

**Files:**
- Modify: `app/Models/ProdukPemasok.php:15-27`

- [ ] **Step 1: Ganti `$fillable` dan tambah `$casts`**

Ganti blok `protected $fillable = [...]` (baris 15-27) menjadi:

```php
    protected $fillable = [
        'user_id',
        'sku',
        'nama_produk',
        'deskripsi',
        'harga_modal',      // rupiah bulat per unit (didanai LKBB)
        'margin_persen',    // persentase keuntungan pemasok dari harga_modal
        'stok_sekarang',
        'batas_minimum_stok',
        'foto_produk',
        'status',
        'satuan',
    ];

    protected $casts = [
        'harga_modal'   => 'integer',
        'margin_persen' => 'float',
    ];
```

- [ ] **Step 2: Commit**

```
git add app/Models/ProdukPemasok.php
git commit -m "refactor: ProdukPemasok pakai margin_persen + cast harga_modal integer"
```

---

### Task 3: Component ManajemenProduk

**Files:**
- Modify: `app/Livewire/Pemasok/ManajemenProduk.php`

- [ ] **Step 1: Ganti deklarasi properti (baris 27)**

Dari:
```php
    public $produk_id, $sku, $nama_produk, $harga_modal, $margin_pemasok, $stok_sekarang, $batas_minimum_stok, $deskripsi, $foto_produk, $foto_produk_lama;
```
Jadi:
```php
    public $produk_id, $sku, $nama_produk, $harga_modal, $margin_persen, $stok_sekarang, $batas_minimum_stok, $deskripsi, $foto_produk, $foto_produk_lama;
```

- [ ] **Step 2: Ganti reset list di `bukaModalTambah` (baris 81-85)**

Dari:
```php
        $this->reset([
            'nama_produk', 'harga_modal', 'margin_pemasok', 'stok_sekarang', 
            'batas_minimum_stok', 'deskripsi', 'foto_produk', 
            'foto_produk_lama', 'satuan'
        ]);
```
Jadi:
```php
        $this->reset([
            'nama_produk', 'harga_modal', 'margin_persen', 'stok_sekarang',
            'batas_minimum_stok', 'deskripsi', 'foto_produk',
            'foto_produk_lama', 'satuan'
        ]);
```

- [ ] **Step 3: Ganti validasi di `simpanProduk` (baris 96-105)**

Dari:
```php
        $this->validate([
            'sku' => 'required|unique:produk_pemasoks,sku,' . $this->produk_id,
            'nama_produk' => 'required|min:3',
            'harga_modal' => 'required|numeric|min:0',
            'margin_pemasok' => 'required|numeric|min:0',
            'stok_sekarang' => 'required|integer',
            'batas_minimum_stok' => 'required|integer',
            'satuan' => 'required|string|max:20',
            'foto_produk' => $this->isEdit ? 'nullable|image|max:1024' : 'required|image|max:1024',
        ]);
```
Jadi:
```php
        $this->validate([
            'sku' => 'required|unique:produk_pemasoks,sku,' . $this->produk_id,
            'nama_produk' => 'required|min:3',
            'harga_modal' => 'required|integer|min:0',
            'margin_persen' => 'required|numeric|in:5,10,15,20,25,30',
            'stok_sekarang' => 'required|integer',
            'batas_minimum_stok' => 'required|integer',
            'satuan' => 'required|string|max:20',
            'foto_produk' => $this->isEdit ? 'nullable|image|max:1024' : 'required|image|max:1024',
        ], [], [
            'harga_modal' => 'harga modal',
            'margin_persen' => 'margin',
        ]);
```

- [ ] **Step 4: Ganti array `$data` di `simpanProduk` (baris 111-112)**

Dari:
```php
            'harga_modal' => $this->harga_modal,
            'margin_pemasok' => $this->margin_pemasok,
```
Jadi:
```php
            'harga_modal' => $this->harga_modal,
            'margin_persen' => $this->margin_persen,
```

- [ ] **Step 5: Ganti pengisian form di `editProduk` (baris 179-180)**

Dari:
```php
        $this->harga_modal = $produk->harga_modal;
        $this->margin_pemasok = $produk->margin_pemasok;
```
Jadi:
```php
        $this->harga_modal = (int) $produk->harga_modal;
        $this->margin_persen = (int) $produk->margin_persen;
```

- [ ] **Step 6: Ganti reset list di `resetForm` (baris 193-196)**

Dari:
```php
        $this->reset([
            'produk_id', 'sku', 'nama_produk', 'harga_modal', 'margin_pemasok', 
            'stok_sekarang', 'batas_minimum_stok', 'deskripsi', 'foto_produk', 'isEdit'
        ]);
```
Jadi:
```php
        $this->reset([
            'produk_id', 'sku', 'nama_produk', 'harga_modal', 'margin_persen',
            'stok_sekarang', 'batas_minimum_stok', 'deskripsi', 'foto_produk', 'isEdit'
        ]);
```

- [ ] **Step 7: Commit**

```
git add app/Livewire/Pemasok/ManajemenProduk.php
git commit -m "refactor: ManajemenProduk pakai margin_persen + validasi preset"
```

---

### Task 4: Form blade — input margin, perbaikan mask, ringkasan harga

**Files:**
- Modify: `resources/views/livewire/pemasok/manajemen-produk.blade.php:177-247`

- [ ] **Step 1: Ganti seluruh blok harga modal + margin (baris 176-219)**

Ganti blok yang dimulai dari komentar `{{-- PERUBAHAN DI SINI... --}}` (baris 176) sampai penutup `</div>` blok orange (baris 219) dengan:

```blade
                    {{-- Harga Modal (mask Rupiah) & Margin (persen) --}}
                    <div class="grid grid-cols-2 gap-4 bg-orange-50 p-4 rounded-xl border border-orange-100">

                        {{-- Input Harga Modal dengan Alpine (mask Rupiah, di-harden) --}}
                        <div class="col-span-1" x-data="{
                                uang: @entangle('harga_modal').live,
                                formatRupiah(value) {
                                    if(!value) return '';
                                    return value.toString().split(/[.,]/)[0].replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                }
                            }">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">Harga Modal / Unit</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">Rp</span>
                                <input type="text"
                                       x-bind:value="formatRupiah(uang)"
                                       x-on:input="uang = $event.target.value.replace(/\D/g, '')"
                                       class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm pl-9 font-bold" placeholder="0">
                            </div>
                            @error('harga_modal') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                            <p class="text-[9px] text-gray-500 mt-1">Biaya produksi per unit, didanai LKBB.</p>
                        </div>

                        {{-- Input Margin Pemasok (dropdown persen) --}}
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-green-600 uppercase tracking-widest mb-1.5">Margin / Untung</label>
                            <select wire:model.live="margin_persen"
                                    class="w-full rounded-xl border-green-200 focus:ring-green-500 text-sm font-bold bg-white">
                                <option value="">Pilih margin…</option>
                                <option value="5">5%</option>
                                <option value="10">10%</option>
                                <option value="15">15%</option>
                                <option value="20">20%</option>
                                <option value="25">25%</option>
                                <option value="30">30%</option>
                            </select>
                            @error('margin_persen') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                            <p class="text-[9px] text-green-600 mt-1">Persentase keuntungan dari harga modal.</p>
                        </div>

                    </div>

                    {{-- Ringkasan harga: harga jual per unit & total modal --}}
                    @php
                        $m_modal = (int) ($harga_modal ?: 0);
                        $m_persen = (float) ($margin_persen ?: 0);
                        $m_stok = (int) ($stok_sekarang ?: 0);
                        $m_marginRp = (int) round($m_modal * $m_persen / 100);
                        $m_jualUnit = $m_modal + $m_marginRp;
                        $m_totalModal = $m_modal * $m_stok;
                    @endphp
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-3">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Harga Jual / Unit</p>
                            <p class="text-sm font-black text-gray-800 mt-0.5">Rp {{ number_format($m_jualUnit, 0, ',', '.') }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Modal + margin {{ rtrim(rtrim(number_format($m_persen, 2, '.', ''), '0'), '.') }}%</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-3">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Modal</p>
                            <p class="text-sm font-black text-gray-800 mt-0.5">Rp {{ number_format($m_totalModal, 0, ',', '.') }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Modal × {{ $m_stok }} stok</p>
                        </div>
                    </div>
```

- [ ] **Step 2: Buat input Stok Awal jadi `.live` (baris 224)**

Dari:
```blade
                            <input type="number" wire:model="stok_sekarang" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" placeholder="0">
```
Jadi:
```blade
                            <input type="number" wire:model.live="stok_sekarang" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm" placeholder="0">
```

- [ ] **Step 3: Verifikasi manual render form**

Run: `php artisan view:clear`
Lalu buka panel Pemasok → Manajemen Produk → klik "Tambah Produk".
Expected: kolom "Harga Modal / Unit" (mask Rp), "Margin / Untung" (dropdown 5–30%), dan dua kartu ringkasan "Harga Jual / Unit" + "Total Modal" yang ikut berubah saat modal/margin/stok diisi.

- [ ] **Step 4: Commit**

```
git add resources/views/livewire/pemasok/manajemen-produk.blade.php
git commit -m "feat: form produk pakai margin persen + ringkasan harga jual & total modal"
```

---

### Task 5: Tabel blade — kolom Harga & Margin

**Files:**
- Modify: `resources/views/livewire/pemasok/manajemen-produk.blade.php:79-94`

- [ ] **Step 1: Ganti isi `<td>` kolom "Harga & Margin" (baris 79-94)**

Ganti seluruh `<td class="px-6 py-4">...</td>` kedua (blok harga, baris 79-94) dengan:

```blade
                        <td class="px-6 py-4">
                            @php
                                $t_modal = (int) ($p->harga_modal ?? 0);
                                $t_persen = (float) ($p->margin_persen ?? 0);
                                $t_marginRp = (int) round($t_modal * $t_persen / 100);
                                $t_jualUnit = $t_modal + $t_marginRp;
                                $t_totalModal = $t_modal * (int) ($p->stok_sekarang ?? 0);
                            @endphp
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 text-xs">Modal/unit:</span>
                                    <span class="font-bold text-gray-700">Rp {{ number_format($t_modal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 text-xs">Margin:</span>
                                    <span class="font-bold text-green-600">{{ rtrim(rtrim(number_format($t_persen, 2, '.', ''), '0'), '.') }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 text-xs">Jual/unit:</span>
                                    <span class="font-bold text-gray-700">Rp {{ number_format($t_jualUnit, 0, ',', '.') }}</span>
                                </div>
                                <div class="border-t border-gray-100 mt-1 pt-1 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Total Modal</span>
                                    <span class="font-black text-gray-900 text-sm">Rp {{ number_format($t_totalModal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </td>
```

- [ ] **Step 2: Verifikasi manual tabel**

Buka Manajemen Produk. Expected: kolom "Harga & Margin" menampilkan Modal/unit, Margin `X%`, Jual/unit, dan baris "Total Modal" = modal × stok.

- [ ] **Step 3: Commit**

```
git add resources/views/livewire/pemasok/manajemen-produk.blade.php
git commit -m "feat: tabel produk tampilkan margin persen & total modal"
```

---

### Task 6: order-bahan — konversi margin persen → rupiah

**Files:**
- Modify: `resources/views/livewire/merchant/order-bahan.blade.php`

- [ ] **Step 1: Ganti field cart di `addToCart` (baris 62-63)**

Dari:
```php
                'harga_modal'    => (float)$produk->harga_modal,
                'margin_pemasok' => (float)$produk->margin_pemasok,
```
Jadi:
```php
                'harga_modal'    => (int)$produk->harga_modal,
                'margin_persen'  => (float)$produk->margin_persen,
```

- [ ] **Step 2: Ganti perhitungan di `cartTotal` (baris 117-122)**

Dari:
```php
        return array_reduce($this->cart, function ($carry, $item) {
            // Tangani jika qty sedang dikosongkan sementara oleh user
            $qty = (int) ($item['qty'] === '' ? 0 : $item['qty']);
            $harga_total_per_item = $item['harga_modal'] + $item['margin_pemasok'];
            return $carry + ($harga_total_per_item * $qty);
        }, 0);
```
Jadi:
```php
        return array_reduce($this->cart, function ($carry, $item) {
            // Tangani jika qty sedang dikosongkan sementara oleh user
            $qty = (int) ($item['qty'] === '' ? 0 : $item['qty']);
            $margin_rupiah = round($item['harga_modal'] * $item['margin_persen'] / 100);
            $harga_total_per_item = $item['harga_modal'] + $margin_rupiah;
            return $carry + ($harga_total_per_item * $qty);
        }, 0);
```

- [ ] **Step 3: Ganti loop pembuatan detail di `submitOrder` (baris 162-174)**

Dari:
```php
                    foreach ($items as $item) {
                        $harga_satuan = $item['harga_modal'] + $item['margin_pemasok'];
                        $subtotal = $harga_satuan * (int)$item['qty'];
                        
                        SupplyOrderDetail::create([
                            'supply_order_id'         => $order->id,
                            'produk_pemasok_id'       => $item['id'], 
                            'nama_produk_snapshot'    => $item['nama'],
                            'harga_modal_snapshot'    => $item['harga_modal'],
                            'margin_pemasok_snapshot' => $item['margin_pemasok'],
                            'qty'                     => (int)$item['qty'],
                            'subtotal'                => $subtotal
                        ]);

                        $realTotal += $subtotal;
                    }
```
Jadi:
```php
                    foreach ($items as $item) {
                        $margin_rupiah = (int) round($item['harga_modal'] * $item['margin_persen'] / 100);
                        $harga_satuan = $item['harga_modal'] + $margin_rupiah;
                        $subtotal = $harga_satuan * (int)$item['qty'];

                        SupplyOrderDetail::create([
                            'supply_order_id'         => $order->id,
                            'produk_pemasok_id'       => $item['id'],
                            'nama_produk_snapshot'    => $item['nama'],
                            'harga_modal_snapshot'    => $item['harga_modal'],
                            'margin_pemasok_snapshot' => $margin_rupiah,
                            'qty'                     => (int)$item['qty'],
                            'subtotal'                => $subtotal
                        ]);

                        $realTotal += $subtotal;
                    }
```

- [ ] **Step 4: Ganti harga di kartu katalog (baris 240)**

Dari:
```blade
                            <p class="text-sm font-extrabold text-emerald-600">Rp{{ number_format($item->harga_modal + $item->margin_pemasok, 0, ',', '.') }}</p>
```
Jadi:
```blade
                            <p class="text-sm font-extrabold text-emerald-600">Rp{{ number_format($item->harga_modal + round($item->harga_modal * $item->margin_persen / 100), 0, ',', '.') }}</p>
```

- [ ] **Step 5: Ganti harga di item keranjang (baris 277)**

Dari:
```blade
                                <p class="text-[10px] font-bold text-emerald-600 mt-0.5">Rp{{ number_format($item['harga_modal'] + $item['margin_pemasok'], 0, ',', '.') }}</p>
```
Jadi:
```blade
                                <p class="text-[10px] font-bold text-emerald-600 mt-0.5">Rp{{ number_format($item['harga_modal'] + round($item['harga_modal'] * $item['margin_persen'] / 100), 0, ',', '.') }}</p>
```

- [ ] **Step 6: Commit**

```
git add resources/views/livewire/merchant/order-bahan.blade.php
git commit -m "refactor: order-bahan hitung margin rupiah dari persen, snapshot tetap rupiah"
```

---

### Task 7: Migrate & verifikasi end-to-end

**Files:** —

- [ ] **Step 1: Pastikan MySQL Laragon menyala, lalu jalankan migration**

Run: `php artisan migrate`
Expected: migration `2026_05_22_000002_revisi_modal_margin_produk_pemasok` `DONE`.

- [ ] **Step 2: Verifikasi skema kolom**

Run: `php artisan tinker --execute="echo json_encode(DB::select('DESCRIBE produk_pemasoks'));"`
Expected: ada kolom `margin_persen` (decimal), `harga_modal` bertipe `bigint`, dan TIDAK ada `margin_pemasok`.

- [ ] **Step 3: Verifikasi bug ×100 hilang**

Buka panel Pemasok → Manajemen Produk → Tambah Produk: nama "Beras", Harga Modal `20.000`, Margin `20%`, Stok `1`, satuan `kg`, upload foto → Terbitkan Produk.
Lalu klik tombol Edit pada produk itu.
Expected: field Harga Modal tetap menampilkan `20.000` (BUKAN `2.000.000`). Dropdown margin terpilih `20%`.

- [ ] **Step 4: Verifikasi ringkasan & tabel**

Pada form Edit produk "Beras" di atas: kartu "Harga Jual / Unit" = `Rp 24.000` (20.000 + 20%), "Total Modal" = `Rp 20.000` (20.000 × 1 stok).
Pada tabel: kolom Harga & Margin menampilkan Modal/unit `Rp 20.000`, Margin `20%`, Jual/unit `Rp 24.000`, Total Modal `Rp 20.000`.

- [ ] **Step 5: Verifikasi alur order (snapshot rupiah)**

Login sebagai Merchant terverifikasi → Order Bahan → tambah "Beras" ke keranjang qty 2 → kirim pesanan.
Run: `php artisan tinker --execute="echo \App\Models\SupplyOrderDetail::latest()->first();"`
Expected: `harga_modal_snapshot` = `20000`, `margin_pemasok_snapshot` = `4000` (20% × 20.000), `subtotal` = `48000`.

- [ ] **Step 6: Verifikasi halaman hilir tidak rusak**

Buka `pesanan-masuk` (Pemasok), `pengiriman-logistik` (Pemasok), `approval-scf` (LKBB), dashboard Pemasok.
Expected: semua render tanpa error; nilai harga PO konsisten dengan snapshot.

- [ ] **Step 7: Commit penutup (jika ada perubahan tersisa) & ringkasan**

```
git status
```
Jika bersih, plan selesai. Jika ada file belum ter-commit, commit dengan pesan deskriptif.

---

## Catatan eksekusi

- Data lama yang `harga_modal`-nya sudah terlanjur korup ×100 (pernah disimpan lewat Edit sebelum fix) → `margin_persen` hasil backfill ikut salah. Data lokal/dummy: perbaiki dengan re-entry manual lewat form Edit.
- Jika `margin_persen` hasil backfill bukan angka preset (mis. 13.7%), dropdown akan tampil kosong saat Edit — pemasok cukup memilih ulang nilai preset.
- Belum dibuat test otomatis: proyek tidak punya harness test untuk halaman Livewire ini. Verifikasi memakai langkah manual di Task 7.
