# Manajemen Produk — Fix Bug Modal ×100 & Margin Persentase

**Tanggal:** 2026-05-22
**Halaman:** `manajemen-produk` (panel Pemasok)

## Masalah

1. **Bug ×100.** `produk_pemasoks.harga_modal` & `margin_pemasok` bertipe `decimal(15,2)`.
   Eloquent mengembalikan string `"20000.00"`. Mask Rupiah Alpine di
   `manajemen-produk.blade.php` memanggil `value.replace(/\D/g, '')` yang membuang
   titik desimal → `"2000000"`. Akibat: setiap kali produk dibuka di modal Edit,
   `harga_modal` & `margin_pemasok` membengkak 100×. Halaman index aman karena
   memakai `number_format`.
2. Margin pemasok kini berupa nominal rupiah tetap. Diinginkan: margin berupa
   **persentase dari modal**, dipilih sendiri oleh pemasok.
3. Form & tabel tidak memisahkan harga **per-unit** dan **total modal**.

## Tujuan

1. Hilangkan kelas bug ×100 secara permanen.
2. Ubah `margin_pemasok` (rupiah) → `margin_persen` (persen), input **dropdown preset**.
3. Form & tabel menampilkan harga satuan vs total modal secara terpisah.

## Skema DB — migration baru

Tabel `produk_pemasoks`:

- `harga_modal`: `decimal(15,2)` → `unsignedBigInteger`. Rupiah bulat; aplikasi tak
  pernah memakai sen (`number_format(..., 0, ...)` di seluruh kode). `.00` lenyap.
- Tambah kolom `margin_persen` `decimal(5,2)` default `0`.
- Backfill: `margin_persen = ROUND(margin_pemasok / NULLIF(harga_modal,0) * 100, 2)`.
- Drop kolom `margin_pemasok`.

Urutan `up()`: tambah `margin_persen` → backfill → drop `margin_pemasok` →
ubah tipe `harga_modal`. `down()`: tidak reversibel (catatan di migration).

## Rumus

```
margin_rupiah / unit = harga_modal × margin_persen / 100
harga_jual   / unit  = harga_modal + margin_rupiah
total_modal          = harga_modal × stok_sekarang     (tampilan saja, tidak disimpan)
```

## Perubahan per file

### `app/Models/ProdukPemasok.php`
- `$fillable`: `margin_pemasok` → `margin_persen`.
- `$casts`: `harga_modal => 'integer'`, `margin_persen => 'float'`.

### `app/Livewire/Pemasok/ManajemenProduk.php`
- Properti `$margin_pemasok` → `$margin_persen`.
- Sesuaikan `bukaModalTambah`, `resetForm`, `editProduk`, `simpanProduk`.
- Validasi: `harga_modal` → `required|integer|min:0`;
  `margin_persen` → `required|numeric|in:5,10,15,20,25,30`.

### `resources/views/livewire/pemasok/manajemen-produk.blade.php`
- Input `harga_modal`: tetap mask Alpine, tetapi di-harden — buang bagian desimal
  lebih dulu: `String(value).split(/[.,]/)[0].replace(/\D/g,'')`.
- Input margin: hapus blok mask Alpine; ganti `<select wire:model="margin_persen">`
  dengan opsi `5, 10, 15, 20, 25, 30` (%).
- Tambah ringkasan read-only live (Alpine, entangle `harga_modal` + `margin_persen`
  + `stok_sekarang`): **Total Modal** = `harga_modal × stok` dan **Harga Jual/unit**.
- Tabel kolom "Harga & Margin": Modal/unit · Margin `X%` · Harga jual/unit.
  Baris "Total PO" lama (salah label — itu harga per unit) → **Total Modal**
  = `harga_modal × stok_sekarang`.

### `resources/views/livewire/merchant/order-bahan.blade.php`
- `addToCart` (L62-63): cart simpan `harga_modal` + `margin_persen` (ganti
  `margin_pemasok`).
- `cartTotal` (L120) & `submitOrder` (L163): hitung
  `margin_rupiah = harga_modal × margin_persen / 100`,
  `harga_satuan = harga_modal + margin_rupiah`.
- Snapshot (L170-171): `harga_modal_snapshot = harga_modal`,
  `margin_pemasok_snapshot = margin_rupiah` (rupiah hasil hitung).
- Display katalog (L240) & cart (L277): `harga_modal + margin_rupiah`.

## Tidak berubah

`pesanan-masuk`, `pengiriman-logistik`, `lkbb/approval-po`, `dashboard/pemasok`,
`merchant/katalog` — semua membaca kolom `*_snapshot` di `supply_order_details`
yang tetap rupiah. Karena `margin_pemasok_snapshot` tetap diisi nilai rupiah,
file-file ini tidak tersentuh.

## Risiko

- `->change()` tipe kolom butuh Laravel 11+ native (proyek memakai Volt → L11, aman).
- Data lama yang `harga_modal`-nya sudah terlanjur korup ×100 → `margin_persen`
  hasil backfill ikut salah. Data lokal/dummy — bisa re-entry manual. Hasil
  backfill % mungkin tak cocok dengan preset dropdown → pemasok memilih ulang
  saat edit.

## Verifikasi manual

1. MySQL nyala → `php artisan migrate`.
2. Tambah produk: modal `20000`, margin `20%` → simpan → buka Edit lagi →
   modal tetap `20000` (bukan `2000000`).
3. Tabel: Total Modal = modal × stok.
4. `order-bahan`: harga katalog = `modal + modal × % / 100`; submit PO →
   cek `supply_order_details` — `harga_modal_snapshot` & `margin_pemasok_snapshot`
   berisi rupiah yang benar.
