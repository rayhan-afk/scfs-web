# 📚 DOKUMENTASI ULTRA LENGKAP — SCFS WEB

> **Supply Chain Finance Service (SCFS)** — Ekosistem Keuangan Kantin Kampus
> Dibangun oleh PT LAPI ITB · Laravel 12 + Livewire 4 / Volt
> Dokumen ini menjelaskan aplikasi **sampai ke akar-akarnya**: setiap folder, file, fungsi, alur data, state, database, dan API.

---

## 📑 DAFTAR ISI

1. [Apa Itu Aplikasi Ini](#1-apa-itu-aplikasi-ini)
2. [Teknologi yang Digunakan](#2-teknologi-yang-digunakan)
3. [Struktur Folder](#3-struktur-folder)
4. [Konsep Inti & Alur Kerja Lengkap](#4-konsep-inti--alur-kerja-lengkap)
5. [Database — Schema & Relasi](#5-database--schema--relasi)
6. [Analisis Kode File-by-File](#6-analisis-kode-file-by-file)
7. [Data Flow](#7-data-flow)
8. [State Management](#8-state-management)
9. [API Integration](#9-api-integration)
10. [Configuration](#10-configuration)
11. [Common Flows (Skenario Umum)](#11-common-flows-skenario-umum)
12. [Error Handling](#12-error-handling)
13. [Performance](#13-performance)
14. [Troubleshooting](#14-troubleshooting)
15. [Testing](#15-testing)
16. [Deployment](#16-deployment)
17. [Development Guidelines](#17-development-guidelines)
18. [Lampiran — Daftar Bug & Inkonsistensi Diketahui](#18-lampiran--daftar-bug--inkonsistensi-diketahui)

---

# 1. APA ITU APLIKASI INI

## 1.1 Penjelasan Singkat

**SCFS (Supply Chain Finance Service)** adalah aplikasi web yang menjalankan **ekosistem keuangan kantin kampus**. Inti idenya: sebuah lembaga keuangan (disebut **LKBB** — *Lembaga Keuangan Bukan Bank*) bertindak sebagai **bank/treasury pusat** yang membiayai seluruh rantai pasok kantin kampus, sehingga:

- **Kantin (Merchant)** tidak perlu modal sendiri untuk membeli bahan baku — LKBB yang membiayai (program *"Zero Risk"*).
- **Pemasok (Supplier)** dijamin pembayaran oleh LKBB.
- **Mahasiswa** bisa makan di kantin memakai **saldo beasiswa/bantuan digital** (bukan uang tunai pribadi).
- **Donatur** menyumbang dana beasiswa; **Investor** menyuntik modal kerja.
- LKBB mengambil **fee/bagi hasil** dari setiap transaksi sebagai pendapatan operasional.

Singkatnya: SCFS adalah **gabungan dari e-wallet kampus + sistem POS kantin + platform supply-chain-financing + panel admin yayasan**.

## 1.2 Apa yang Bisa Dilakukan

| Kategori | Kemampuan |
|----------|-----------|
| **Pembayaran** | Mahasiswa bayar makanan via QR (saldo beasiswa) atau kantin terima tunai |
| **POS Kantin** | Mesin kasir digital: keranjang, kalkulasi kembalian, generate QR |
| **Pesanan Online** | "Dapur" — mahasiswa pesan online, kantin proses seperti ShopeeFood |
| **Supply Chain** | Kantin pesan bahan ke pemasok → LKBB approve & danai → pemasok produksi & kirim |
| **Pembiayaan** | LKBB mencairkan dana dari "Brankas Investasi" untuk membiayai PO |
| **Beasiswa** | Admin ajukan bantuan mahasiswa → LKBB approve → saldo cair dari "Brankas Donasi" |
| **Penarikan Dana** | Merchant & Pemasok tarik saldo digital ke rekening bank |
| **Setoran Tunai** | Kantin setor uang fisik (hasil penjualan tunai) ke LKBB |
| **Manajemen** | Admin kelola semua aktor (CRUD), monitoring transaksi global |
| **Mobile** | Aplikasi Flutter untuk mahasiswa (login, profil, scan QR, riwayat) |

## 1.3 Target User (6 Peran / Role)

```
┌─────────────────────────────────────────────────────────────────┐
│                     EKOSISTEM SCFS — 6 PERAN                     │
├──────────────┬────────────────────────────────────────────────── ┤
│ admin        │ Administrator yayasan/kampus. Kelola semua data    │
│              │ aktor, verifikasi, monitoring transaksi.           │
├──────────────┼─────────────────────────────────────────────────  ┤
│ lkbb         │ Lembaga Keuangan Bukan Bank. "Bank" pusat:         │
│              │ approve pendanaan, kelola 3 brankas, settlement.   │
├──────────────┼─────────────────────────────────────────────────  ┤
│ merchant     │ Pemilik kantin/warung. Jualan via POS, pesan       │
│              │ bahan, tarik saldo, setor tunai.                   │
├──────────────┼─────────────────────────────────────────────────  ┤
│ pemasok      │ Supplier bahan baku. Terima PO, produksi, kirim.   │
├──────────────┼─────────────────────────────────────────────────  ┤
│ mahasiswa    │ Penerima beasiswa. Bayar makan via QR (mobile).    │
├──────────────┼─────────────────────────────────────────────────  ┤
│ investor     │ Pemberi modal kerja. (registrasi bisa, dashboard   │
│ donatur      │ Pemberi dana beasiswa.  belum dibuat — lihat §18)  │
└──────────────┴─────────────────────────────────────────────────  ┘
```

> ⚠️ **Catatan penting:** Peran `investor` dan `donatur` **bisa registrasi** dan **dikelola admin** (ada tabel & halaman CRUD), tetapi **belum punya dashboard/panel sendiri**. Mereka adalah entitas data, bukan user aktif.

---

# 2. TEKNOLOGI YANG DIGUNAKAN

## 2.1 Tech Stack Lengkap

| Layer | Teknologi | Versi | Alasan Penggunaan |
|-------|-----------|-------|-------------------|
| **Bahasa** | PHP | ^8.2 | Wajib untuk Laravel 12 |
| **Framework** | Laravel | ^12.0 | Framework MVC PHP utama; routing, ORM, migrations, auth |
| **UI Reactivity** | Livewire | ^4.1 | Bikin komponen interaktif tanpa nulis JavaScript — server-rendered |
| **Single-File Component** | Livewire Volt | ^1.7 | Tulis logika PHP + template Blade dalam 1 file `.blade.php` |
| **API Auth** | Laravel Sanctum | ^4.3 | Token-based auth untuk aplikasi mobile Flutter |
| **Auth Scaffold** | Laravel Breeze | ^2.3 (dev) | Generator halaman login/register/profil bawaan |
| **REPL** | Laravel Tinker | ^2.10 | Console interaktif untuk debugging |
| **CSS** | Tailwind CSS | ^3.1 | Utility-first CSS framework |
| **CSS Plugin** | @tailwindcss/forms | ^0.5 | Styling default elemen form |
| **Build Tool** | Vite | ^7.0 | Bundler aset (CSS/JS), hot-reload saat dev |
| **HTTP Client (JS)** | Axios | ^1.11 | Request HTTP dari browser |
| **Database** | MySQL / MariaDB | — | Database produksi (lihat `.env`); SQLite jadi default config |
| **Testing** | Pest | ^4.3 | Framework testing (di atas PHPUnit) |
| **Mocking** | Mockery | ^1.6 | Mock object untuk test |
| **Code Style** | Laravel Pint | ^1.24 | Auto-formatter kode PHP |
| **Log Viewer** | Laravel Pail | ^1.2 | Tail log real-time di terminal |
| **Faker** | fakerphp/faker | ^1.23 | Generate data dummy untuk seeder/factory |
| **Charting** | ApexCharts + Chart.js | CDN | Grafik di dashboard (di-load via CDN, bukan npm) |
| **Alert UI** | SweetAlert2 | v11 CDN | Popup konfirmasi cantik |
| **Reactivity (JS)** | Alpine.js | bundled | Interaktivitas kecil di sisi client (dibawa Livewire) |
| **Mobile** | Flutter | (eksternal) | Aplikasi mobile mahasiswa yang konsumsi API |

## 2.2 Mengapa Memilih Stack Ini?

- **Laravel + Livewire + Volt** → Tim bisa membangun aplikasi web interaktif **tanpa SPA framework** (React/Vue). Semua logika di PHP, satu bahasa, satu file per halaman. Cepat untuk tim kecil.
- **Volt single-file** → Setiap halaman = 1 file `.blade.php` berisi class PHP anonim + template. Mengurangi jumlah file (tidak perlu pasangan Controller + View terpisah).
- **Sanctum** → API token sederhana untuk mobile, tanpa kompleksitas OAuth.
- **Tailwind** → Styling cepat langsung di markup, konsisten.
- **SQLite (default) / MySQL (produksi)** → SQLite memudahkan setup awal; MySQL untuk produksi.

## 2.3 Arsitektur Tingkat Tinggi

```
                         ┌──────────────────────┐
                         │   BROWSER (Web App)  │
                         │  Admin/LKBB/Merchant │
                         │      /Pemasok        │
                         └──────────┬───────────┘
                                    │ HTTP + Livewire AJAX
                                    │
        ┌───────────────────────────▼────────────────────────────┐
        │                  LARAVEL 12 (Server)                   │
        │  ┌────────────┐  ┌──────────────┐  ┌────────────────┐   │
        │  │  Routes    │─▶│  Livewire /  │─▶│  Eloquent ORM  │   │
        │  │ web/api    │  │  Volt        │  │  (Models)      │   │
        │  └────────────┘  └──────────────┘  └───────┬────────┘   │
        │  ┌──────────────────────────────┐          │            │
        │  │  FinanceService (transfer/   │          │            │
        │  │  deposit double-entry)       │          │            │
        │  └──────────────────────────────┘          │            │
        └─────────────────────────────────────────────┼───────────┘
                                    ▲                  │
              REST API (Sanctum)    │                  ▼
        ┌───────────────────────────┴──┐      ┌─────────────────┐
        │   FLUTTER (Mobile - Mahasiswa)│      │  MySQL Database │
        │   login, scan QR, riwayat     │      └─────────────────┘
        └───────────────────────────────┘
```

---

# 3. STRUKTUR FOLDER

## 3.1 Pohon Direktori Root

```
scfs-web/
├── app/                    → Kode aplikasi (PHP)
├── bootstrap/              → Bootstrap framework (app.php, providers.php, cache/)
├── config/                → File konfigurasi (auth, database, dll)
├── database/              → Migrations, seeders, factories, database.sqlite
├── node_modules/          → Dependensi JS (hasil npm install)
├── public/                → Document root web server (index.php, aset build)
├── resources/             → View Blade, CSS, JS sumber
├── routes/                → Definisi route (web, api, auth, console)
├── storage/               → File upload, log, cache, session
├── tests/                 → File test (Pest)
├── vendor/                → Dependensi PHP (hasil composer install)
├── .editorconfig          → Aturan format editor
├── .env                   → Variabel lingkungan AKTIF (rahasia, tidak di-git)
├── .env.example           → Template .env
├── .gitattributes         → Aturan Git
├── .gitignore             → File yang diabaikan Git
├── artisan                → CLI Laravel (php artisan ...)
├── composer.json          → Manifest dependensi PHP
├── composer.lock          → Lock versi dependensi PHP
├── package.json           → Manifest dependensi JS
├── package-lock.json      → Lock versi dependensi JS
├── phpunit.xml            → Konfigurasi testing
├── postcss.config.js      → Konfigurasi PostCSS (Tailwind + autoprefixer)
├── README.md              → Readme default Laravel (belum diubah)
├── scfs_web               → File dump database SQL (~100KB, bukan kode)
├── tailwind.config.js     → Konfigurasi Tailwind (warna brand, font)
└── vite.config.js         → Konfigurasi Vite (entry CSS/JS)
```

## 3.2 Isi Folder `app/` — Jantung Aplikasi

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── MahasiswaAuthController.php  → 7 endpoint API untuk mobile Flutter
│   │   ├── Auth/
│   │   │   └── VerifyEmailController.php    → Handle klik link verifikasi email
│   │   └── Controller.php                   → Base controller abstract (kosong)
│   ├── Requests/
│   │   └── LoginMahasiswaRequest.php        → Validasi request login API
│   └── Resources/
│       └── MahasiswaResource.php            → Transformer JSON data mahasiswa untuk API
│
├── Livewire/                                → Komponen Livewire BERBASIS CLASS
│   ├── Actions/
│   │   └── Logout.php                       → Action logout (invokable)
│   ├── Forms/
│   │   └── LoginForm.php                    → Form object login Breeze (rate-limit)
│   ├── Lkbb/
│   │   └── ApprovalPo.php                   → ⭐ Approval pendanaan PO (transaksi uang nyata)
│   └── Pemasok/                             → 8 komponen halaman pemasok
│       ├── LaporanAnalitik.php
│       ├── ManajemenProduk.php
│       ├── PengajuanDanaLkbb.php
│       ├── PengirimanLogistik.php
│       ├── PesananMasuk.php
│       ├── ProfilePemasok.php
│       ├── RiwayatProduksi.php
│       └── TarikDana.php
│
├── Models/                                  → 27 model Eloquent (tabel database)
│   ├── User.php                BahanBaku.php           DonaturProfile.php
│   ├── InvestorProfile.php     LedgerEntry.php         LoginLog.php
│   ├── MahasiswaProfile.php    MerchantProduct.php     MerchantProfile.php
│   ├── OnlineOrder.php         OnlineOrderItem.php     PemasokProfile.php
│   ├── PengajuanBantuan.php    Product.php             ProductPriceHistory.php
│   ├── ProdukPemasok.php       ProduksiPemasok.php     RiwayatOpnamePemasok.php
│   ├── SetoranTunai.php        SupplierProfile.php     SupplyChain.php
│   ├── SupplyOrder.php         SupplyOrderDetail.php   Transaction.php
│   ├── Wallet.php              Withdrawal.php
│
├── Providers/
│   ├── AppServiceProvider.php               → Listener event Login → catat LoginLog
│   └── VoltServiceProvider.php              → Daftarkan path Volt (views/livewire, views/pages)
│
├── Services/
│   └── Finance/
│       └── FinanceService.php               → ⭐ Service transfer/deposit double-entry
│
└── View/
    └── Components/
        ├── AppLayout.php                    → Komponen <x-app-layout>
        └── GuestLayout.php                  → Komponen <x-guest-layout>
```

> **Catatan arsitektur:** Aplikasi ini punya **DUA gaya komponen Livewire**:
> 1. **Class-based** (`app/Livewire/...`) — class PHP terpisah, template di `resources/views/livewire/...`.
> 2. **Volt single-file** (`resources/views/livewire/...blade.php` & `resources/views/pages/...`) — class PHP anonim + template dalam satu file.
> Mayoritas halaman memakai gaya **Volt single-file**. Hanya modul Pemasok & `ApprovalPo` yang class-based.

## 3.3 Isi Folder `resources/views/`

```
resources/views/
├── components/                  → Komponen Blade reusable (UI kit Breeze)
│   ├── action-message.blade.php       → Pesan "Saved." yang auto-hilang
│   ├── application-logo.blade.php     → Logo SVG
│   ├── auth-session-status.blade.php  → Banner status (mis. "link reset terkirim")
│   ├── danger-button.blade.php        → Tombol merah
│   ├── dropdown.blade.php             → Dropdown Alpine
│   ├── dropdown-link.blade.php        → Item dalam dropdown
│   ├── input-error.blade.php          → Tampilkan error validasi field
│   ├── input-label.blade.php          → Label form
│   ├── modal.blade.php                → Modal Alpine reusable
│   ├── nav-link.blade.php             → Link navigasi (dengan state aktif)
│   ├── primary-button.blade.php       → Tombol utama
│   ├── responsive-nav-link.blade.php  → Link nav versi mobile
│   ├── secondary-button.blade.php     → Tombol sekunder
│   ├── text-input.blade.php           → Input teks
│   └── layouts/
│       └── landing.blade.php          → Layout minimal halaman login/register custom
│
├── layouts/
│   ├── app.blade.php            → Layout utama (admin/merchant/pemasok pakai sidebar)
│   ├── guest.blade.php          → Layout tamu (halaman auth Breeze)
│   └── lkbb.blade.php           → Layout khusus panel LKBB (sidebar indigo)
│
├── livewire/
│   ├── admin/                   → 13 halaman panel Admin
│   ├── dashboard/               → 4 dashboard (admin, lkbb, merchant, pemasok)
│   ├── layout/                  → Sidebar & navigasi (admin/lkbb/merchant/pemasok-sidebar,
│   │                              navigation, wallet-card, product-list, transaction-history)
│   ├── lkbb/                    → Halaman panel LKBB (brankas, keuangan, approval, supply-chain)
│   ├── merchant/                → 9 halaman panel Merchant
│   ├── pemasok/                 → 8 template halaman Pemasok (pasangan class app/Livewire/Pemasok)
│   ├── pages/auth/              → 6 halaman auth Breeze (login, register, dll)
│   ├── profile/                 → 3 form profil (update info, password, hapus akun)
│   ├── welcome/                 → navigation.blade.php (welcome page)
│   ├── login.blade.php          → ⭐ Halaman login CUSTOM SCFS (yang aktif dipakai)
│   └── register.blade.php       → ⭐ Halaman register CUSTOM SCFS (yang aktif dipakai)
│
├── dashboard.blade.php          → Router dashboard berbasis role
└── profile.blade.php            → Halaman profil (gabungan 3 form)
```

## 3.4 Folder Lainnya

| Folder/File | Fungsi |
|-------------|--------|
| `bootstrap/app.php` | Konfigurasi inti: daftar route, middleware, exception handler |
| `bootstrap/providers.php` | Daftar Service Provider (`AppServiceProvider`, `VoltServiceProvider`) |
| `config/*.php` | 11 file konfigurasi (app, auth, cache, database, dll) |
| `database/migrations/` | 52 file migrasi — definisi & evolusi skema database |
| `database/seeders/` | `DatabaseSeeder` (akun awal), `MerchantSeeder` (kantin dummy) |
| `database/factories/` | `UserFactory` — generator user dummy untuk test |
| `database/database.sqlite` | File database SQLite (dipakai jika `DB_CONNECTION=sqlite`) |
| `public/index.php` | Entry point semua request HTTP |
| `public/build/` | Aset hasil `npm run build` (CSS/JS terkompilasi) |
| `public/images/` | Gambar statis (logo, dll) |
| `public/storage/` | Symlink ke `storage/app/public` (file upload) |
| `routes/web.php` | Route halaman web (butuh login) |
| `routes/api.php` | Route API mobile |
| `routes/auth.php` | Route autentikasi (login, register, reset password) |
| `routes/console.php` | Perintah artisan custom |
| `storage/app/` | File upload (foto KTP, produk, dll) |
| `storage/logs/laravel.log` | Log aplikasi |
| `tests/` | File test Pest |

---

# 4. KONSEP INTI & ALUR KERJA LENGKAP

## 4.1 Model Keuangan — Dompet (Wallet) & Brankas

LKBB memiliki **3 "Brankas" (treasury wallet)** dengan tujuan berbeda:

```
┌──────────────────────────────────────────────────────────────────┐
│                      BRANKAS LKBB (3 Dompet)                      │
├────────────────────┬─────────────────────────────────────────────┤
│ LKBB_INVESTMENT    │ "Brankas Investasi" — modal kerja dari       │
│                    │ Investor. Dipakai mendanai PO pemasok.       │
├────────────────────┼─────────────────────────────────────────────┤
│ LKBB_DONATION      │ "Brankas Donasi" — dana beasiswa dari        │
│                    │ Donatur. Dipakai cairkan bantuan mahasiswa.  │
├────────────────────┼─────────────────────────────────────────────┤
│ LKBB_OPERATIONAL   │ "Brankas Operasional" — menampung modal      │
│                    │ kembali (HPP) + fee LKBB dari transaksi.     │
└────────────────────┴─────────────────────────────────────────────┘
```

> ✅ **SUDAH DIPERBAIKI (2026-05-22):** Sebelumnya kode memakai dua skema penamaan dompet yang tidak kompatibel untuk 3 brankas yang sama. Skema lama (`LKBB_MASTER` / `DONATION_POOL` / `LKBB_PROFIT`) sudah **dihapus total** — file `wallet-index`, komponen `wallet-card`, dan `supply-chain/bills` dibuang; baris dompet skema lama dihapus lewat migrasi `2026_05_22_000001_cleanup_skema_b_lkbb_wallets`. Sistem sekarang memakai **satu skema tunggal**: `LKBB_INVESTMENT` / `LKBB_DONATION` / `LKBB_OPERATIONAL`.

Selain brankas, ada dompet per-user: `USER_WALLET` / `MERCHANT`, `SUPPLIER_WALLET` / `PEMASOK`, `STUDENT_WALLET` / `MAHASISWA`.

Sebagian saldo juga **tidak disimpan di tabel `wallets`**, melainkan di kolom tabel profil:
- `merchant_profiles.saldo_token` → e-wallet kantin (hasil penjualan digital).
- `merchant_profiles.tagihan_setoran_tunai` → utang kantin ke LKBB (dari penjualan tunai).
- `mahasiswa_profiles.saldo` → saldo beasiswa mahasiswa.
- `pemasok_profiles.tagihan_berjalan` → piutang pemasok ke LKBB.

## 4.2 Konsep "Bagi Hasil" (Profit Sharing)

Setiap menu kantin punya 2 harga:
- **`harga_pokok`** = modal/HPP (dibiayai LKBB).
- **`harga_jual`** = harga ke pembeli.
- **Profit** = `harga_jual − harga_pokok`.

Saat transaksi POS, profit dibagi:

```
  feeLKBB     = (profit × persentase_fee_merchant) / 100
  Hak LKBB    = total_pokok + feeLKBB        ← modal kembali + bagi hasil
  Hak Merchant = total_amount − total_pokok − feeLKBB   ← laba bersih kantin
```

> ⚠️ Nama variabel `persentase_fee_merchant` menyesatkan: nilainya dipakai sebagai **persentase bagian LKBB** dari profit (lihat `pos-merchant.blade.php`).

## 4.3 Alur Kerja Lengkap — Dari Membuka App Sampai Hasil

### 4.3.1 Alur Umum (Semua Role Web)

```
 [1] User buka URL  →  redirect "/" ke route('login')
        │
 [2] Halaman LOGIN (resources/views/livewire/login.blade.php)
        │  isi email + password → method login()
        │  Auth::attempt() → cek kredensial
        ▼
 [3] BERHASIL → session()->regenerate() → redirect "/dashboard"
        │  (event Login → AppServiceProvider catat ke login_logs)
        ▼
 [4] Route "/dashboard" = TRAFFIC CONTROLLER (routes/web.php)
        │  cek $user->role:
        │   admin     → redirect route('admin.dashboard')
        │   lkbb      → redirect route('lkbb.dashboard')
        │   merchant  → redirect route('merchant.dashboard')
        │   pemasok   → redirect route('pemasok.dashboard')
        │   lainnya   → redirect route('profile')
        ▼
 [5] Layout dipilih otomatis (resources/views/layouts/app.blade.php):
        │   admin/merchant/pemasok → layout SIDEBAR
        │   lainnya                → layout NAVIGASI ATAS
        │   (LKBB pakai layout terpisah: layouts/lkbb.blade.php)
        ▼
 [6] Komponen dashboard role me-render → tampil data via #[Computed] / with()
        │
 [7] User klik menu sidebar → Volt::route / Route::get → komponen halaman
        │
 [8] Interaksi (wire:click / wire:submit) → method PHP jalan di server →
        re-render parsial HTML → browser update tanpa reload penuh.
```

### 4.3.2 Alur Bisnis Inti — Supply Chain Financing

```
┌──────────┐  1. Buat PO   ┌──────────┐  2. Approve  ┌──────────┐
│ MERCHANT │ ────────────▶ │ PEMASOK  │ ───────────▶ │   LKBB   │
│ (Kantin) │  order-bahan  │ pesanan- │  teruskan ke │ approval │
└──────────┘               │ masuk    │  LKBB        │ -po      │
                           └──────────┘              └────┬─────┘
                                                          │ 3. Cairkan dana
                                                          │  (debit Brankas
                                                          │   Investasi)
                                                          ▼
┌──────────┐  6. Konfirmasi ┌──────────┐  5. Kirim  ┌──────────┐
│ MERCHANT │ ◀───────────── │ PEMASOK  │ ◀──────────│ PEMASOK  │
│penerimaan│   barang       │pengiriman│  4. Produksi│ (terima  │
│  → katalog (jadikan menu) │ -logistik│            │  dana)   │
└──────────┘                └──────────┘            └──────────┘

Status SupplyOrder:
menunggu_pemasok → menunggu_lkbb → diproses_pemasok → dikirim → selesai
                                                              (atau: ditolak)
```

### 4.3.3 Alur Pembayaran Mahasiswa (POS + QR)

```
┌─────────────────────┐         ┌──────────────────────┐
│  KANTIN (Web POS)   │         │  MAHASISWA (Flutter) │
│  pos-merchant       │         │                      │
└─────────┬───────────┘         └──────────┬───────────┘
          │ 1. Susun keranjang             │
          │ 2. buatQrPembayaran()          │
          │    → Transaction status=pending│
          │    → stok di-reserve (decrement)│
          │    → tampil QR di layar        │
          │                                │ 3. Scan QR
          │                                │ 4. POST /api/pay-qr
          │                                │    {order_id}
          │         ┌──────────────────────▼──────────┐
          │         │  MahasiswaAuthController::payQr  │
          │         │  - potong mahasiswa_profiles.saldo│
          │         │  - +hakMerchant ke saldo_token   │
          │         │  - +hakLkbb ke LKBB_OPERATIONAL  │
          │         │  - Transaction status=sukses     │
          │         └──────────────────────┬───────────┘
          │ 5. wire:poll.2s cekStatus...    │
          │    → status sukses → clearCart  │
          ▼                                 ▼
     Transaksi selesai                Saldo terpotong
```

---

# 5. DATABASE — SCHEMA & RELASI

## 5.1 Konfigurasi Database

- **Default (`config/database.php`)**: `sqlite` → file `database/database.sqlite`.
- **Aktif (`.env`)**: `mysql` → host `127.0.0.1:3306`, database `scfs_web`, user `root`, password kosong.
- **Testing (`phpunit.xml`)**: `sqlite` in-memory (`:memory:`).

## 5.2 Daftar Lengkap Tabel (33 Tabel)

| Tabel | Fungsi | Dibuat oleh Migrasi |
|-------|--------|---------------------|
| `users` | Akun login semua peran | `0001_01_01_000000` (+5 migrasi ALTER) |
| `password_reset_tokens` | Token reset password | `0001_01_01_000000` |
| `sessions` | Sesi (jika driver=database) | `0001_01_01_000000` |
| `cache`, `cache_locks` | Cache (jika driver=database) | `0001_01_01_000001` |
| `jobs`, `job_batches`, `failed_jobs` | Antrian job | `0001_01_01_000002` |
| `wallets` | Dompet digital (brankas LKBB + user) | `2026_02_11_042600` |
| `products` | Produk generik (modul lama mahasiswa) | `2026_02_11_042634` |
| `supply_chains` | ⚠️ Pembiayaan rantai pasok — **DIBUAT lalu DIHAPUS** | `2026_02_20` → drop `2026_05_12` |
| `mahasiswa_profiles` | Profil mahasiswa | `2026_02_23_032214` (+2 ALTER) |
| `merchant_profiles` | Profil kantin | `2026_02_23_034301` (+4 ALTER) |
| `pengajuan_bantuans` | Pengajuan dana beasiswa | `2026_02_23_060729` |
| `pemasok_profiles` | Profil pemasok (skema lama) | `2026_02_24_032741` |
| `investor_profiles` | Profil investor | `2026_02_24_042441` |
| `donatur_profiles` | Profil donatur | `2026_02_24_063342` (+1 ALTER) |
| `login_logs` | Riwayat login | `2026_02_26_021551` |
| `transactions` | Transaksi keuangan | `2026_02_26_034444` (+3 ALTER) |
| `withdrawals` | Penarikan dana merchant | `2026_03_02_043438` |
| `supplier_profiles` | Profil pemasok (skema baru) | `2026_03_02_070340` |
| `produk_pemasoks` | Katalog produk pemasok | `2026_03_03_072920` (+2 ALTER) |
| `riwayat_opname_pemasoks` | Riwayat stok-opname pemasok | `2026_03_03_072929` |
| `merchant_products` | Menu jualan kantin (POS) | `2026_03_03_100917` (+2 ALTER) |
| `bahan_bakus` | Master bahan baku (modul lama) | `2026_03_03_143950` |
| `supply_orders` | Purchase Order kantin→pemasok | `2026_03_03_143951` (+3 ALTER) |
| `supply_order_details` | Item dalam PO | `2026_03_03_143952` (+3 ALTER) |
| `setoran_tunais` | Setoran tunai kantin ke LKBB | `2026_03_04_111839` |
| `product_price_histories` | Riwayat perubahan harga menu | `2026_03_04_115122` |
| `personal_access_tokens` | Token API Sanctum | `2026_03_05_093637` |
| `produksi_pemasoks` | Batch produksi pemasok | `2026_03_30_105731` |
| `ledger_entries` | Buku besar (double-entry) | `2026_05_11_134951` |
| `online_orders` | Pesanan online mahasiswa | `2026_05_19_141727` |
| `online_order_items` | Item pesanan online | `2026_05_19_141736` |

## 5.3 Skema Kolom Tabel Penting

### Tabel `users`

```
id              bigint PK
name            string
email           string UNIQUE
email_verified_at  timestamp NULL
password        string (hashed)
role            string DEFAULT 'mahasiswa'   → admin|lkbb|merchant|pemasok|mahasiswa|investor|donatur
identity_code   string UNIQUE NULL           → NIM / Kode Toko / NIP
phone_number    string NULL
remember_token  string
created_at, updated_at
```

> Catatan: kolom `nim, jurusan, ktm_image, status_verifikasi, status_bantuan, saldo` **sempat ditambahkan** ke `users` lalu **dihapus** (migrasi `2026_02_23_032243`) — dipindah ke `mahasiswa_profiles`.

### Tabel `wallets`

```
id              bigint PK
user_id         FK → users (cascade delete)
account_number  string UNIQUE
pin             string NULL
balance         decimal(15,2) DEFAULT 0      → saldo utama
type            string DEFAULT 'REGULAR'     → LKBB_INVESTMENT|LKBB_DONATION|LKBB_OPERATIONAL|
                                               USER_WALLET|SUPPLIER_WALLET|STUDENT_WALLET
is_active       boolean DEFAULT true
created_at, updated_at
```

### Tabel `transactions`

```
id                 bigint PK
order_id           string NULL              → mis. TRX-xxx, DEP-xxx, DIG-xxx, UMM-xxx, INJ-xxx
user_id            FK → users NULL          → inisiator/pemilik
sender_wallet_id   FK → wallets NULL        → dompet pengirim
receiver_wallet_id FK → wallets NULL        → dompet penerima
merchant_id        FK → users NULL          → kantin terkait
type               string                  → pembayaran_makanan|pembayaran_makanan_tunai|
                                              TOPUP|topup|PEMBIAYAAN_PO|INJEKSI_MANUAL|
                                              penerimaan_bantuan|withdrawal|payment| dst.
status             string DEFAULT 'pending' → pending|success|sukses|lunas|failed
total_amount       decimal(15,2) DEFAULT 0
total_pokok        decimal(15,2) DEFAULT 0  → HPP / modal
fee_lkbb           decimal(15,2) DEFAULT 0  → bagi hasil LKBB
description        text NULL
created_at, updated_at
```

### Tabel `ledger_entries` (Buku Besar Double-Entry)

```
id              bigint PK
transaction_id  FK → transactions (cascade)
wallet_id       FK → wallets (cascade)
entry_type      string                  → DEBIT (masuk) | CREDIT (keluar)
amount          decimal(15,2)
balance_after   decimal(15,2)            → saldo dompet SETELAH mutasi (audit trail)
created_at, updated_at
```

### Tabel `supply_orders` (PO Kantin)

```
id                 bigint PK
nomor_order        string UNIQUE          → PO-YYYYMMDD-xxxxx
merchant_id        FK → users (cascade)
pemasok_id         FK → users NULL (cascade)
total_estimasi     decimal(15,2)
tanggal_kebutuhan  date
catatan            text NULL
status             enum                   → menunggu_lkbb|diproses_pemasok|dikirim|selesai|ditolak
                                            (+ 'menunggu_pemasok' dipakai di kode)
status_pembiayaan  string DEFAULT 'siap_diajukan'
id_pengajuan       string NULL
kurir              string NULL
no_resi            string NULL
tracking_history   json NULL
created_at, updated_at
```

### Tabel `supply_order_details`

```
id                       bigint PK
supply_order_id          FK → supply_orders (cascade)
produk_pemasok_id        FK → produk_pemasoks NULL  (dulu bahan_baku_id)
nama_produk_snapshot     string
harga_modal_snapshot     decimal(15,2)
margin_pemasok_snapshot  decimal(15,2)
qty                      integer
subtotal                 decimal(15,2)
is_added_to_pos          boolean DEFAULT false      → sudah dijadikan menu POS?
created_at, updated_at
```

### Tabel `merchant_profiles`

```
id                      bigint PK
user_id                 FK → users (cascade)
nama_kantin             string NULL
nama_pemilik            string
nik                     string(20) NULL
status_verifikasi       string DEFAULT 'belum_melengkapi' → belum_melengkapi|menunggu_review|
                                                            disetujui|ditolak|pending
foto_ktp                string NULL
foto_kantin             string NULL
catatan_penolakan       text NULL
lokasi_blok             string NULL
info_pencairan          string NULL
no_hp                   string NULL
persentase_bagi_hasil   integer DEFAULT 10   → di-rename jadi persentase_fee_merchant (migrasi 05_12)
tagihan_setoran_tunai   decimal(15,2) DEFAULT 0  → utang tunai ke LKBB
saldo_token             decimal(15,2) DEFAULT 0  → e-wallet kantin
status_toko             enum('buka','tutup') DEFAULT 'tutup'
created_at, updated_at
```

## 5.4 Diagram Relasi Antar Tabel (ERD Sederhana)

```
                            ┌──────────┐
                            │  users   │ (role: 6 macam)
                            └────┬─────┘
       ┌─────────────────────────┼──────────────────────────────┐
       │ hasOne                  │ hasMany                       │ hasOne (per role)
       ▼                         ▼                               ▼
  ┌─────────┐            ┌──────────────┐          ┌───────────────────────────┐
  │ wallets │            │ transactions │          │ mahasiswa_profiles        │
  └────┬────┘            └──────┬───────┘          │ merchant_profiles         │
       │ hasMany                │ hasMany          │ pemasok_profiles          │
       ▼                        ▼                  │ supplier_profiles         │
  ┌──────────────┐      ┌──────────────┐           │ investor_profiles         │
  │ ledger_entries│◀────┤ (transaction)│           │ donatur_profiles          │
  └──────────────┘      └──────────────┘           └─────────────┬─────────────┘
                                                                  │ hasMany
  ┌───────────────────────────────────┐                          ▼
  │  supply_orders                    │              ┌─────────────────────┐
  │   merchant_id → users             │              │ pengajuan_bantuans  │
  │   pemasok_id  → users             │              │ (mahasiswa_profile) │
  └────────────┬──────────────────────┘              └─────────────────────┘
               │ hasMany
               ▼
  ┌───────────────────────────┐       ┌──────────────────────────┐
  │ supply_order_details      │──────▶│ produk_pemasoks          │
  │   produk_pemasok_id       │       │   (user_id → users)      │
  └───────────────────────────┘       │   hasMany riwayat_opname │
                                      └──────────────────────────┘
  ┌──────────────────┐  hasMany  ┌────────────────────────┐
  │ merchant_products│──────────▶│ product_price_histories│
  │  (merchant_id)   │           └────────────────────────┘
  └──────────────────┘
  ┌───────────────┐  hasMany  ┌────────────────────┐
  │ online_orders │──────────▶│ online_order_items │
  │ (mahasiswa_id,│           └────────────────────┘
  │  merchant_id) │
  └───────────────┘
  ┌──────────────┐    ┌─────────────────┐    ┌────────────────────┐
  │ withdrawals  │    │ setoran_tunais  │    │ produksi_pemasoks  │
  │ (merchant_id)│    │ (merchant_id)   │    │ (user_id=pemasok)  │
  └──────────────┘    └─────────────────┘    └────────────────────┘
```

## 5.5 Relasi Eloquent Terdefinisi (di Model)

| Model | Relasi |
|-------|--------|
| `User` | `hasOne` wallet, merchantProfile, mahasiswaProfile, pemasokProfile, supplierProfile, investorProfile, donaturProfile; `hasMany` wallets, products, transactions, merchantProducts; `hasOne` latestLogin (latestOfMany) |
| `Wallet` | `belongsTo` user; `hasMany` ledgerEntries (latest). Helper: `isLkbb()`, `isMerchant()`, dll |
| `Transaction` | `belongsTo` user, relatedUser, merchant, senderWallet, receiverWallet |
| `LedgerEntry` | `belongsTo` wallet, transaction |
| `MahasiswaProfile` | `belongsTo` user; `hasMany` pengajuans |
| `PengajuanBantuan` | `belongsTo` mahasiswaProfile |
| `SupplyChain` | `belongsTo` merchant, supplier, profilPemasok; auto-generate `invoice_number` di `boot()` |
| `SupplyOrder` | `hasMany` details; `belongsTo` merchant, pemasok |
| `SupplyOrderDetail` | `belongsTo` supplyOrder, produkPemasok |
| `ProdukPemasok` | `belongsTo` user; `hasMany` riwayatOpnames; SoftDeletes |
| `MerchantProduct` | `belongsTo` merchant; `hasMany` priceHistories |
| `Withdrawal` | `belongsTo` merchant, merchantProfile |
| `OnlineOrder` | `hasMany` items; `belongsTo` mahasiswa |
| `SetoranTunai` | `belongsTo` merchant |
| `ProduksiPemasok` | `belongsTo` pemasok |
| `PemasokProfile` | `belongsTo` user; `hasMany` supplyChains, riwayatPesanan |

---

# 6. ANALISIS KODE FILE-BY-FILE

## 6.1 ROUTING

### `routes/web.php` — Route Halaman Web

Semua route (kecuali `/`) dibungkus `middleware(['auth'])`.

| Route | Nama | Komponen | Role |
|-------|------|----------|------|
| `GET /` | — | Redirect ke `login` | Publik |
| `GET /dashboard` | `dashboard` | **Traffic controller** — redirect per role | Semua |
| `GET /profile` | `profile` | View `profile` | Semua |
| `/admin/dashboard` | `admin.dashboard` | `dashboard.admin` | Admin |
| `/admin/users` | `admin.users.index` | `admin.user-management` | Admin |
| `/admin/verifikasi-mahasiswa` | `admin.verification` | `admin.mahasiswa-verification` | Admin |
| `/admin/data-mahasiswa` | `admin.mahasiswa.index` | `admin.mahasiswa-data` | Admin |
| `/admin/data-mahasiswa/{id}` | `admin.mahasiswa.detail` | `admin.mahasiswa-detail` | Admin |
| `/admin/data-merchant{/id}` | `admin.merchant.*` | `admin.merchant-data/detail` | Admin |
| `/admin/data-pemasok{/id}` | `admin.pemasok.*` | `admin.pemasok-data/detail` | Admin |
| `/admin/data-investor{/id}` | `admin.investor.*` | `admin.investor-data/detail` | Admin |
| `/admin/data-donatur{/id}` | `admin.donatur.*` | `admin.donatur-data/detail` | Admin |
| `/admin/monitoring-transaksi` | `admin.monitoring.index` | `admin.monitoring-transaksi` | Admin |
| `/lkbb/dashboard` | `lkbb.dashboard` | `dashboard.lkbb` | LKBB |
| `/lkbb/brankas/{investasi,donasi,operasional,perputaran}` | `lkbb.brankas.*` | `lkbb.brankas.*` | LKBB |
| `/lkbb/injeksi-saldo` | `lkbb.injeksi-saldo` | `lkbb.keuangan.injeksi-saldo` | LKBB |
| `/lkbb/riwayat-injeksi` | `lkbb.riwayat-injeksi` | `lkbb.keuangan.riwayat-injeksi` | LKBB |
| `/lkbb/approval-scf` | `lkbb.scf.approval` | `App\Livewire\Lkbb\ApprovalPo` | LKBB |
| `/lkbb/scf/riwayat` | `lkbb.scf.riwayat` | `livewire.lkbb.riwayat-po` | LKBB |
| `/approval/{merchant,mahasiswa,pemasok}` | `approval.*` | `lkbb.approval.*` | LKBB |
| `/keuangan/{merchant,pemasok,mahasiswa,pencairan,penagihan,riwayat-fee}` | `keuangan.*` / `saldo.bantuan` | `lkbb.keuangan.*` | LKBB |
| `/keuangan/approval-withdraw-{merchant,pemasok}` | `lkbb.withdraw.*.approval` | `lkbb.keuangan.withdraw-*-approval` | LKBB |
| `/merchant/dashboard` | `merchant.dashboard` | `dashboard.merchant` | Merchant |
| `/merchant/pos` | `merchant.pos` | `merchant.pos-merchant` | Merchant |
| `/merchant/pesanan-online` | `merchant.pesanan-online` | `merchant.pesanan-online` | Merchant |
| `/merchant/{withdraw,katalog,profile,order,riwayat,penerimaan,setoran}` | `merchant.*` | `merchant.*` | Merchant |
| `/pemasok/dashboard` | `pemasok.dashboard` | `dashboard.pemasok` | Pemasok |
| `/pemasok/{inventaris,profil,laporan,riwayat-produksi,pesanan-masuk,tarik-dana,pengiriman}` | `pemasok.*` | `App\Livewire\Pemasok\*` | Pemasok |

> ⚠️ **Tidak ada middleware role.** Semua route hanya pakai `auth`. Pemisahan role hanya lewat *traffic controller* di `/dashboard` dan cek `Auth::user()->role` di layout. Secara teknis, user merchant yang tahu URL `/admin/users` masih bisa membukanya (kecuali komponennya punya guard sendiri — hanya `dashboard.admin` yang punya `abort(403)`).

### `routes/api.php` — Route API Mobile

```
POST /api/login                          → publik (MahasiswaAuthController::login)
── middleware('auth:sanctum') ──
GET  /api/profile                        → profil mahasiswa
POST /api/logout                         → hapus token
POST /api/update-avatar                  → ganti foto
POST /api/update-profile                 → update no_hp & alamat
GET  /api/transactions                   → riwayat transaksi (paginate 15)
POST /api/pay-qr                         → bayar QR kantin
```

### `routes/auth.php` — Route Autentikasi

```
── middleware('guest') ──
GET register          → Volt 'register'  (halaman register CUSTOM SCFS)
GET login             → Volt 'login'     (halaman login CUSTOM SCFS)
GET forgot-password   → Volt 'pages.auth.forgot-password'
GET reset-password/{token} → Volt 'pages.auth.reset-password'
── middleware('auth') ──
GET  verify-email          → Volt 'pages.auth.verify-email'
GET  verify-email/{id}/{hash} → VerifyEmailController (signed + throttle:6,1)
GET  confirm-password      → Volt 'pages.auth.confirm-password'
```

> **Penting:** `route('login')`/`route('register')` mengarah ke **halaman CUSTOM** (`livewire/login.blade.php`, `livewire/register.blade.php`), bukan versi Breeze (`pages/auth/login.blade.php`). Versi Breeze masih ada & dipakai oleh test, tapi tidak terhubung route utama.

### `routes/console.php`

Hanya 1 perintah bawaan: `php artisan inspire` (tampilkan kutipan inspiratif).

## 6.2 MODELS (27 File)

Semua di `app/Models/`. Berikut ringkasan per model:

| Model | Tabel | Mass Assignment | Catatan Khusus |
|-------|-------|-----------------|----------------|
| `User` | users | `$fillable`: name,email,password,role | `HasApiTokens`, `Notifiable`, cast password→hashed |
| `Wallet` | wallets | `$fillable` lengkap | Helper `isLkbb()`, `isMerchant()`, dll |
| `Transaction` | transactions | `$fillable` 11 kolom | Relasi sender/receiver/merchant wallet |
| `LedgerEntry` | ledger_entries | `$fillable` 5 kolom | cast amount & balance_after → decimal:2 |
| `MahasiswaProfile` | mahasiswa_profiles | `$guarded = ['id']` | hasMany pengajuans |
| `MerchantProfile` | merchant_profiles | `$fillable` 14 kolom | — |
| `PemasokProfile` | pemasok_profiles | `$fillable` 9 kolom | Relasi supplyChains pakai `supplier_id` |
| `SupplierProfile` | supplier_profiles | `$fillable` 11 kolom | **Profil pemasok skema baru** (lihat §18) |
| `InvestorProfile` | investor_profiles | `$guarded = []` | — |
| `DonaturProfile` | donatur_profiles | `$guarded = []` | — |
| `PengajuanBantuan` | pengajuan_bantuans | `$fillable` 4 kolom | — |
| `Product` | products | `$fillable` 6 kolom | Modul lama (mahasiswa beli produk) |
| `ProdukPemasok` | produk_pemasoks | `$fillable` 11 kolom | `SoftDeletes`, hasMany riwayatOpnames |
| `RiwayatOpnamePemasok` | riwayat_opname_pemasoks | `$fillable` 5 kolom | — |
| `MerchantProduct` | merchant_products | `$fillable` 8 kolom | hasMany priceHistories |
| `ProductPriceHistory` | product_price_histories | `$fillable` 5 kolom | — |
| `BahanBaku` | bahan_bakus | `$fillable` 6 kolom | Modul lama, tidak aktif dipakai |
| `SupplyChain` | supply_chains | `$guarded = ['id']` | ⚠️ Tabel DI-DROP migrasi 05_12 |
| `SupplyOrder` | supply_orders | `$guarded = ['id']` | hasMany details |
| `SupplyOrderDetail` | supply_order_details | `$guarded = ['id']` | — |
| `ProduksiPemasok` | produksi_pemasoks | `$guarded = ['id']` | cast waktu_produksi→datetime |
| `Withdrawal` | withdrawals | `$fillable` 8 kolom | — |
| `SetoranTunai` | setoran_tunais | `$fillable` 5 kolom | — |
| `OnlineOrder` | online_orders | `$fillable` 6 kolom | hasMany items |
| `OnlineOrderItem` | online_order_items | `$fillable` 7 kolom | — |
| `LoginLog` | login_logs | `$guarded = []` | cast login_at→datetime |

**Contoh logika model penting — `SupplyChain::boot()`:**

```php
public static function boot() {
    parent::boot();
    static::creating(function ($model) {
        if (empty($model->invoice_number)) {
            $model->invoice_number = 'INV-SC-' . date('Ymd') . '-' . strtoupper(uniqid());
        }
    });
}
```
→ Auto-generate nomor invoice setiap record `SupplyChain` baru dibuat.

## 6.3 SERVICE — `FinanceService.php` ⭐

`app/Services/Finance/FinanceService.php` — service inti pergerakan uang dengan **double-entry bookkeeping**. Punya 2 method:

### `transfer(Wallet $from, Wallet $to, float $amount, string $type, string $description, array $meta = []): Transaction`

Transfer saldo antar dompet. Langkah:
1. **Validasi awal:** `amount > 0`, `from->id !== to->id` → kalau gagal lempar `Exception`.
2. Bungkus dalam `DB::transaction()` (ACID — rollback otomatis jika error).
3. **Pessimistic locking:** `Wallet::where('id', ...)->lockForUpdate()->first()` untuk sender & receiver — kunci baris agar tidak race condition.
4. Cek saldo sender mencukupi (setelah dikunci).
5. Buat `Transaction` (status `success`, order_id `TRX-xxx`).
6. **Mutasi sender:** `balance -= amount`, save → buat `LedgerEntry` `entry_type='CREDIT'` (uang keluar) dengan `balance_after`.
7. **Mutasi receiver:** `balance += amount`, save → buat `LedgerEntry` `entry_type='DEBIT'` (uang masuk).
8. Return `Transaction`.

### `deposit(Wallet $wallet, float $amount, string $source, string $desc): Transaction`

Uang masuk dari luar (top-up). Hanya 1 `LedgerEntry` (DEBIT). Bungkus `DB::transaction`, lock wallet, buat `Transaction` (type `TOPUP`, order_id `DEP-xxx`), tambah balance, catat ledger.

> ⚠️ **Catatan:** `FinanceService` ditulis rapi tapi **hampir tidak dipakai** komponen lain. Sebagian besar halaman LKBB melakukan mutasi saldo manual (`increment`/`decrement`) langsung tanpa lewat service ini.

## 6.4 CONTROLLER API — `MahasiswaAuthController.php`

`app/Http/Controllers/Api/MahasiswaAuthController.php` — 7 endpoint untuk aplikasi Flutter mahasiswa. Detail di [§9](#9-api-integration).

## 6.5 KOMPONEN AUTH

### `app/Livewire/Forms/LoginForm.php`
Form object Breeze. Properti: `email`, `password`, `remember`. Method `authenticate()`:
- `ensureIsNotRateLimited()` — cek max 5 percobaan via `RateLimiter`; jika lewat → event `Lockout` + lempar `ValidationException`.
- `Auth::attempt()` — jika gagal, `RateLimiter::hit()` + error.
- `throttleKey()` = `lowercase(email) . '|' . ip`.

### `app/Livewire/Actions/Logout.php`
Class invokable. `Auth::guard('web')->logout()` → `Session::invalidate()` → `Session::regenerateToken()`.

### `app/Http/Controllers/Auth/VerifyEmailController.php`
Invokable. Tandai email terverifikasi (`markEmailAsVerified()` + event `Verified`) → redirect `/dashboard?verified=1`.

### `resources/views/livewire/login.blade.php` (Volt — Login Custom)
- Layout `components.layouts.landing`. State: `$email` (validate email), `$password` (validate required), `$remember` (bool).
- `login()`: validate → `Auth::attempt()` → gagal lempar `ValidationException(auth.failed)` → sukses `session()->regenerate()` + `redirect()->intended('/dashboard')`.
- UI: 2-kolom branded, logo "Trevora", toggle show/hide password (Alpine), link ke register & forgot-password.

### `resources/views/livewire/register.blade.php` (Volt — Register Custom) ⭐
- State + validasi: `name` (required|max:255), `email` (required|email|unique:users), `role` (required|in:mahasiswa,merchant,pemasok,investor,donatur — default mahasiswa), `password` (required|min:8|confirmed).
- `register()`:
  1. Validate.
  2. `User::create()` dengan role dari dropdown (admin tidak bisa dipilih).
  3. **Buat profil kosong sesuai role:** mahasiswa→`MahasiswaProfile`, merchant→`MerchantProfile` (nama_kantin = "{name} (Baru)"), pemasok→`PemasokProfile`, investor→`InvestorProfile`, donatur→`DonaturProfile`.
  4. `event(new Registered)` → `Auth::login()` → redirect `/dashboard`.

### `resources/views/livewire/pages/auth/*.blade.php` (6 file Volt — Breeze)
`login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password` — halaman auth standar Breeze pada layout `guest`. Versi `login`/`register` di sini **tidak terhubung route utama** tapi masih diuji test.

### `resources/views/livewire/profile/*.blade.php` (3 form Volt)
- `update-profile-information-form` — ubah name/email; jika email berubah → `email_verified_at=null`; dispatch event `profile-updated`.
- `update-password-form` — ganti password (validasi `current_password` + `Password::defaults()` + confirmed).
- `delete-user-form` — hapus akun (butuh konfirmasi password); logout lalu `delete()`.

## 6.6 DASHBOARD (4 File)

### `dashboard/admin.blade.php` (Volt)
- Guard: `mount()` → kalau bukan admin `abort(403)`.
- `#[Computed] stats()` — hitung jumlah mahasiswa/merchant/pemasok aktif, total perputaran, transaksi hari ini, saldo Brankas Investasi & Donasi.
- `#[Computed] recentActivities()` — 6 transaksi terakhir, dipetakan ke label UI.
- `getChartData()` + `setFilter()` — data grafik ApexCharts (today/month/year), dispatch event `update-admin-chart`.

### `dashboard/lkbb.blade.php` (Volt)
- `with()`: baca 3 saldo brankas, GMV bulan ini (`SUM total_amount`), laba bulan ini (`SUM fee_lkbb`), data cashflow 6 bulan, volume harian 7 hari, 5 transaksi terakhir.
- `#[Computed] pendingAlerts()` — jumlah pengajuan bantuan `diajukan`, withdrawal `pending`, merchant belum verifikasi email.
- Grafik via Chart.js.

### `dashboard/merchant.blade.php` (Volt) — **Gerbang Onboarding 4-Fase**
- `WithFileUploads`. `#[Computed] profile()` = `MerchantProfile::firstOrCreate`.
- Render bercabang berdasarkan `profile->status_verifikasi`:
  - `belum_melengkapi` → form onboarding (upload KTP + foto kantin).
  - `menunggu_review` → layar "Sedang Ditinjau".
  - `ditolak` → layar penolakan + tombol `perbaikiData()`.
  - `disetujui` → dashboard penuh (4 kartu statistik + grafik ApexCharts + aktivitas).
- `submitOnboarding()` — validasi (nik digits 15-17), simpan foto ke `merchants/ktp` & `merchants/kantin`, set status `menunggu_review`.
- `#[Computed]`: `statHariIni()`, `totalModalLKBB()` (= `SUM(stok × harga_pokok)`), `riwayatTransaksi()`, `riwayatPO()`.

### `dashboard/pemasok.blade.php` (Volt)
- Read-only. `#[Computed] stats()` — total modal & margin (dari `supply_order_details` berdasarkan status order), jumlah pesanan per status, total produk aktif.
- `#[Computed] pesananTerbaru()` — 5 PO terakhir.

## 6.7 MODUL ADMIN (13 File Volt)

Semua di `resources/views/livewire/admin/`. Semua pakai `#[Layout('layouts.app')]`.

| File | Fungsi | Method Kunci | Model |
|------|--------|--------------|-------|
| `user-management` | CRUD akun semua user | `editUser`, `updateUser`, `confirmDelete`, `deleteUser` (ada guard self-delete) | User |
| `mahasiswa-verification` | Approve/tolak verifikasi mahasiswa | `approve`, `reject` → dispatch `swal:success` | User, MahasiswaProfile |
| `mahasiswa-data` | Buku induk mahasiswa + ajukan beasiswa | `submitAjukan` (buat `PengajuanBantuan`), `saveMahasiswa` | User, MahasiswaProfile, PengajuanBantuan |
| `mahasiswa-detail` | Detail 1 mahasiswa | `mount($id)`, `updateData`, `#[Computed] riwayatTransaksi` | User, Transaction |
| `merchant-data` | Manajemen kantin (tab verifikasi) | `setTab`, `saveMerchant` (DB::transaction), `#[Computed] merchants/stats` | User, MerchantProfile |
| `merchant-detail` | Detail kantin (katalog/penjualan/pencairan) | `mount($id)`, `updateMerchant`, `viewPriceHistory` | User, Transaction, Withdrawal |
| `pemasok-data` | Manajemen pemasok | `savePemasok` | User, PemasokProfile |
| `pemasok-detail` | Detail pemasok | `getDummyPoProperty` (⚠️ data dummy), `updatePemasok` | User, PemasokProfile |
| `investor-data` | Manajemen investor | `saveInvestor` | User, InvestorProfile |
| `investor-detail` | Detail investor | `updateInvestor`, riwayat deposit/profit | User, Transaction |
| `donatur-data` | Manajemen donatur | `saveDonatur` (DB::beginTransaction) → dispatch `swal:*` | User, DonaturProfile |
| `donatur-detail` | Detail donatur | `simulasiTambahDonasi` (⚠️ helper test), data dummy | User, DonaturProfile |
| `monitoring-transaksi` | Monitor transaksi global | `buatTransaksiDummy` (⚠️ helper test), `#[Computed] transactions/stats` | Transaction, User |

**Pola umum komponen Admin:**
- Halaman `*-data` → list + search + filter + modal "Tambah".
- Halaman `*-detail` → `mount($id)` load `public User $user` dengan eager-load relasi, tab, modal edit.
- Notifikasi campur: ada yang `session()->flash`, ada yang `dispatch('swal:*')`.

> ⚠️ Komponen `monitoring-transaksi` memakai nama kolom `Transaction` yang **berbeda** dari komponen lain (`amount`, `reference_number`, `category` vs `total_amount`, `order_id`) — sisa skema lama. Lihat §18.

## 6.8 MODUL LKBB (Panel Keuangan)

Semua di `resources/views/livewire/lkbb/`, layout `layouts.lkbb`.

### Dashboard & Brankas (Read-only)
| File | Fungsi |
|------|--------|
| `dashboard.lkbb` | Command center: 3 saldo brankas, GMV, laba, grafik |
| `brankas/investasi` | Log aliran modal keluar ke pemasok (dari `SupplyOrder`) |
| `brankas/donasi` | Log penyaluran beasiswa ke mahasiswa (dari `Transaction`) |
| `brankas/operasional` | Log aliran masuk (HPP kembali + fee LKBB) |
| `brankas/perputaran` | Audit GMV makro — **gabung `Transaction` + `SupplyOrder`** dengan paginator manual |

### Manajemen Token / Wallet
| File | Fungsi | Mutasi Uang |
|------|--------|-------------|
| `keuangan/injeksi-saldo` | "Minting Token" — cetak saldo ke brankas | CREDIT `LKBB_DONATION`/`LKBB_INVESTMENT` (atomik) |
| `keuangan/riwayat-injeksi` | Riwayat injeksi | Read-only |

### Keuangan / Settlement
| File | Fungsi | Mutasi Uang |
|------|--------|-------------|
| `keuangan/merchant` | Kelola saldo merchant + top-up | +`saldo_token` & `USER_WALLET` (⚠️ non-atomik, tanpa ledger) |
| `keuangan/pemasok` | Suntik saldo pemasok | CREDIT `SUPPLIER_WALLET` (atomik) |
| `keuangan/mahasiswa` | Salurkan bantuan mahasiswa | CREDIT `STUDENT_WALLET` (atomik, tanpa debit brankas) |
| `keuangan/pencairan` | Approve/tolak penarikan (`Transaction` type=withdrawal) | DEBIT dompet user saat approve |
| `keuangan/penagihan` | ⚠️ **FILE RUSAK** — variabel undefined, import hilang, Blade malformed |
| `keuangan/riwayat-fee` | Laporan pendapatan fee LKBB | Read-only |
| `keuangan/withdraw-merchant-approval` | Approve/tolak WD merchant (`Withdrawal`) | Refund `saldo_token` saat tolak |

### Approval Master Data
| File | Fungsi | Mutasi Uang |
|------|--------|-------------|
| `approval/merchant` | Approve/tolak registrasi kantin + set % fee | — |
| `approval/mahasiswa` | Verifikasi/cairkan pengajuan beasiswa | DEBIT `LKBB_DONATION` → CREDIT `mahasiswa_profiles.saldo` |
| `approval/pemasok` | Approve/tolak registrasi pemasok | — |

### Supply Chain
| File | Fungsi | Catatan |
|------|--------|---------|
| `approval-po.blade.php` | ⚠️ **Template Blade saja, tanpa blok PHP/Volt** — referensi `$orders` dll yang tidak terdefinisi. Logika sebenarnya ada di class `App\Livewire\Lkbb\ApprovalPo` |
| `riwayat-po.blade.php` | Riwayat pendanaan PO (Volt functional API) |
| `supply-chain/approval` | Approve `SupplyChain` (⚠️ tabel sudah di-drop) |
| `supply-chain/create` | Buat pengajuan SCF sebagai `Transaction` |

### `app/Livewire/Lkbb/ApprovalPo.php` ⭐ — Transaksi Uang Paling Kritis

Class-based, template `lkbb/approval-po.blade.php`. Method `setujuiPendanaan()`:

```
DB::transaction(function() {
  1. SupplyOrder::lockForUpdate()->findOrFail()         ← kunci baris
  2. GUARD IDEMPOTEN: if status != 'menunggu_lkbb' → throw
                       (cegah double-click / double-cairkan)
  3. Wallet LKBB_INVESTMENT lockForUpdate()
     if balance < total_estimasi → throw "saldo tidak cukup"
  4. brankasLKBB->decrement('balance', total_estimasi)  ← DEBIT brankas
  5. Transaction::create(type='PEMBIAYAAN_PO', status='success', ...)
  6. Loop details → produkPemasok->decrement('stok_sekarang', qty)
  7. order->update(status='diproses_pemasok',
                    status_pembiayaan='didanai')
});
```

Ini **satu-satunya** komponen dengan mutasi uang durable + atomik + idempotent yang benar.

## 6.9 MODUL MERCHANT (9 File Volt)

Semua di `resources/views/livewire/merchant/`, layout `layouts.app`.

### `pos-merchant.blade.php` ⭐ — Mesin Kasir POS

State: `$cart[]`, `$metode_pembayaran` (digital/tunai), `$uang_diterima`, `$showQrModal`, `$pendingOrderId`, `$qrPayloadString`.

**Method keranjang:** `addToCart` (cek stok), `decreaseQty`, `clearCart`.

**ALUR 1 — `prosesPembayaranTunai()`** (bayar tunai):
1. Validasi `uang_diterima >= total`.
2. `DB::transaction`: lock `MerchantProfile`, loop cart → lock & `decrement('stok')`.
3. Hitung: `feeLKBB = (profit × persentase_fee_merchant)/100`; `tagihanKeLKBB = total_pokok + feeLKBB`.
4. `merchant->increment('tagihan_setoran_tunai', tagihanKeLKBB)` — kantin pegang uang fisik, jadi berutang ke LKBB.
5. Buat `Transaction` type `pembayaran_makanan_tunai`, status `sukses`.

**ALUR 2 — `buatQrPembayaran()`** (generate QR):
1. `DB::transaction`: loop cart → lock & `decrement('stok')` (stok di-*reserve* langsung).
2. Buat `Transaction` type `pembayaran_makanan`, status **`pending`**.
3. Bangun `qrPayloadString` JSON, set `showQrModal=true`.

**`batalkanQrBayar()`** — kembalikan stok + `delete()` transaksi pending.
**`cekStatusPembayaranQr()`** — di-poll `wire:poll.2s`; kalau status `sukses` → `clearCart()`.

QR digambar via API eksternal `api.qrserver.com`.

### File Merchant Lainnya
| File | Fungsi | Method Kunci |
|------|--------|--------------|
| `pesanan-online` | "Dapur" pesanan online | `terimaPesanan`, `makananSiap`, `serahkanMakanan`, `tolakPesanan` (state machine) |
| `withdraw` | Tarik `saldo_token` ke bank | `ajukanPencairan` (opsi `potong_tagihan` untuk lunasi utang sekaligus) |
| `katalog` | Kelola menu POS + ubah PO jadi menu | `jadikanMenu` (PO→menu), `save`, `toggleStatus`, `delete` |
| `profile` | Edit profil + ganti password | `simpanProfil` (gated password), `updatePassword` |
| `order-bahan` | Buat PO ke pemasok | `submitOrder` (group per pemasok → 1 `SupplyOrder`/pemasok); guard `abort(403)` jika belum verifikasi |
| `riwayat` | Laporan penjualan + bagi hasil | `#[Computed] summary` (laba kantin vs hak LKBB) |
| `penerimaan` | Konfirmasi terima barang PO | `konfirmasiTerima` (status `dikirim`→`selesai`) |
| `setoran` | Setor tunai ke LKBB | `panggilPetugas` (buat `SetoranTunai`; tidak mengubah tagihan) |

> Tidak ada `merchant/top-up.blade.php` (file disebut di route lama tapi tidak ada). Saldo merchant hanya bertambah dari penjualan digital.

## 6.10 MODUL PEMASOK (8 Class + 8 Template)

Class di `app/Livewire/Pemasok/`, template di `resources/views/livewire/pemasok/`.

| Komponen | Fungsi | Status Data |
|----------|--------|-------------|
| `ManajemenProduk` | CRUD produk pemasok + stok opname | ✅ Data nyata (`ProdukPemasok`, `RiwayatOpnamePemasok`) |
| `PesananMasuk` | Inbox PO dari kantin | ✅ Data nyata (`SupplyOrder`). `setujuiPesanan`/`tolakPesanan` |
| `PengirimanLogistik` | Atur pengiriman + cetak surat jalan | ✅ Data nyata. `simpanPengiriman` (status→`dikirim`) |
| `RiwayatProduksi` | Riwayat batch produksi | ✅ Sebagian (bahan baku detail = dummy). ⚠️ Bug: `$riwayat` tidak di-scope `user_id` |
| `ProfilePemasok` | Profil + dokumen + keamanan | ✅ Data nyata (`SupplierProfile`). `ubahRekening` gated password |
| `PengajuanDanaLkbb` | Ajukan pendanaan ke LKBB | ⚠️ Plafon hardcoded; buat `SupplyChain` (tabel di-drop!) |
| `TarikDana` | Tarik dana pemasok | ⚠️ **Sepenuhnya simulasi** — data hardcoded, tidak persist |
| `LaporanAnalitik` | Laporan & analitik | ⚠️ **Sepenuhnya dummy** — angka hardcoded |

**`ApprovalPo`** (`app/Livewire/Lkbb/`) — sudah dibahas §6.8.

## 6.11 LAYOUT & SIDEBAR

### `layouts/app.blade.php`
Layout utama. Bercabang berdasar role:
- `admin/merchant/pemasok` → layout sidebar (`x-data="{ sidebarOpen: true }"`), render `<livewire:layout.{role}-sidebar />`.
- Lainnya → layout navigasi atas (`<livewire:layout.navigation />`).
- Load CSRF, font, `@vite`, SweetAlert2.

> ⚠️ Bug: SweetAlert2 script & listener `swal:success` didaftarkan **dua kali** → popup bisa dobel.

### `layouts/lkbb.blade.php`
Layout panel LKBB — sidebar indigo, drawer mobile dengan transisi Alpine.

### `layouts/guest.blade.php`
Layout Breeze untuk halaman auth.

### `components/layouts/landing.blade.php`
Layout minimal untuk login/register custom SCFS.

### Sidebar (4 file Volt di `livewire/layout/`)
| Sidebar | Warna Tema | Menu Utama |
|---------|-----------|------------|
| `admin-sidebar` | Biru `#1D6FD8` | Dashboard, Verifikasi Mahasiswa (badge count), Master Data (6 menu), Operasional, Keuangan |
| `lkbb-sidebar` | Indigo `#4338CA` | Dashboard, Brankas Inti (4), Manajemen Token (2), Approval (3), Rantai Pasok (2), Setoran (2), Withdraw (4) |
| `merchant-sidebar` | Emerald `#059669` | Beranda, Rantai Pasok (2), Penjualan (3), Keuangan (2), Pengaturan (2) |
| `pemasok-sidebar` | Oranye `#EA580C` | Dashboard, Operasional (3), Keuangan (2), Inventory, Laporan, Pengaturan |

Setiap sidebar punya `mount()` opsional (admin: hitung mahasiswa pending) & `logout()`.

> ⚠️ Beberapa link admin masih `href="#"` (placeholder belum dirouting): "Distribusi Saldo", "PO & Pendanaan", "Setoran Tunai", "Riwayat Bagi Hasil"; pemasok: "Informasi Saldo".

### Komponen `livewire/layout/` Lainnya
- `navigation.blade.php` — nav atas untuk mahasiswa; method `logout`.
- `wallet-card.blade.php` — kartu dompet; `#[On('transaction-success')] refreshBalance()`.
- `product-list.blade.php` — daftar produk + `buyProduct()` (transaksi mahasiswa lama); dispatch `transaction-success`.
- `transaction-history.blade.php` — riwayat; `#[On('transaction-success')] loadTransactions()`.

## 6.12 PROVIDERS

### `AppServiceProvider.php`
`boot()`: daftarkan listener event `Login` → otomatis buat `LoginLog` (user_id, ip, user_agent, login_at) setiap user login.

### `VoltServiceProvider.php`
`boot()`: `Volt::mount([resource_path('views/livewire'), resource_path('views/pages')])` — daftarkan 2 folder tempat komponen Volt dicari.

## 6.13 SEEDERS & FACTORY

### `DatabaseSeeder.php`
Buat akun awal:
- `admin@gmail.com` (role admin) — password `password`
- `kantin@gmail.com` (role merchant)
- `lkbb@gmail.com` (role lkbb)
- `pemasok@gmail.com` (role pemasok)
- 10 mahasiswa `mhs1..10@gmail.com` + `MahasiswaProfile` (NIM random, status verifikasi acak).

### `MerchantSeeder.php`
3 merchant dummy (Budi/Siti/Asep) dengan `MerchantProfile` status verifikasi berbeda (pending/disetujui/ditolak).

### `UserFactory.php`
Generator user dummy untuk test (faker name/email, password `password`, email terverifikasi). State `unverified()`.

---

# 7. DATA FLOW

## 7.1 Siklus Request Livewire/Volt

```
 [Browser]                          [Server Laravel]
    │                                     │
    │  1. Load awal: GET /merchant/pos     │
    ├────────────────────────────────────▶│
    │                                     │ Route → Volt komponen
    │                                     │ mount() → #[Computed]/with()
    │  2. HTML penuh + state ter-embed     │ render() template Blade
    │◀────────────────────────────────────┤
    │                                     │
    │  3. User klik tombol (wire:click)    │
    │     → AJAX POST /livewire/update     │
    │       {snapshot state, method call}  │
    ├────────────────────────────────────▶│
    │                                     │ Hydrate komponen dari snapshot
    │                                     │ Jalankan method PHP
    │                                     │ Mutasi state / DB
    │  4. JSON: HTML parsial + state baru  │ re-render() → diff
    │◀────────────────────────────────────┤
    │  5. Livewire patch DOM (morphdom)    │
    │                                     │
```

## 7.2 Aliran Data Transaksi POS (A→Z)

```
A. Merchant pilih produk
   → addToCart($id) → MerchantProduct::find() → $cart[] terisi
B. $cart berubah → #[Computed] cartSummary() recompute → total tampil
C. Merchant klik "Buat QR" → buatQrPembayaran()
   → DB::transaction:
       MerchantProduct::lockForUpdate()->decrement('stok')   [DB write]
       Transaction::create(status=pending)                   [DB write]
   → $qrPayloadString = JSON → $showQrModal = true
D. Modal QR render → <img src="api.qrserver.com?data={payload}">
E. wire:poll.2s → cekStatusPembayaranQr() tiap 2 detik
F. Mahasiswa scan (Flutter) → POST /api/pay-qr
   → MahasiswaAuthController::payQr() → DB::transaction:
       MahasiswaProfile saldo -= total                       [DB write]
       MerchantProfile saldo_token += hakMerchant            [DB write]
       Wallet LKBB_OPERATIONAL balance += hakLkbb            [DB write]
       Transaction status = 'sukses'                         [DB write]
G. Polling berikutnya → status 'sukses' → clearCart() → modal tutup
H. Selesai. Data permanen di DB.
```

## 7.3 Snapshot Aliran Data Antar Komponen (Event)

```
product-list.buyProduct()
        │ dispatch('transaction-success')
        ▼
   ┌────┴─────────────────┐
   ▼                      ▼
wallet-card           transaction-history
#[On('transaction-    #[On('transaction-
 success')]            success')]
refreshBalance()      loadTransactions()
```

---

# 8. STATE MANAGEMENT

## 8.1 Tingkatan State

| Tingkat | Lokasi | Contoh | Umur |
|---------|--------|--------|------|
| **Global persisten** | Database | wallets, transactions, profiles | Permanen |
| **Sesi** | `storage/framework/sessions` (atau tabel `sessions`) | auth, flash message | Per-sesi |
| **Komponen (server)** | Properti publik Livewire | `$cart`, `$activeTab`, `$search` | Per-request lifecycle komponen |
| **Computed** | `#[Computed]` / `with()` | `cartSummary`, `stats` | Dihitung tiap render |
| **Client (Alpine)** | `x-data` | `sidebarOpen`, `open` (modal) | Di browser, hilang saat reload |

## 8.2 State Komponen Livewire

State = **properti publik** class. Contoh `pos-merchant`:
```php
public $kategoriAktif = 'semua';   // filter kategori
public array $cart = [];           // keranjang
public $metode_pembayaran = 'digital';
public $showQrModal = false;
```
State otomatis di-*serialize* ke browser & di-*hydrate* kembali tiap request. Diubah via `wire:model` (binding 2-arah) atau di dalam method.

## 8.3 Computed Properties

Dua gaya dipakai bercampur:
- **Atribut modern:** `#[Computed]` di atas method → diakses `$this->namaMethod` di template.
- **Magic getter legacy:** `getXxxProperty()` → diakses `$this->xxx`.
- **Volt `with()`:** kembalikan array data ke template.

Computed di-cache per-request, dihitung ulang tiap render. `unset($this->profile)` memaksa recompute.

## 8.4 Flash & Event

- **Flash message:** `session()->flash('message', '...')` → tampil sekali lalu hilang.
- **Event browser:** `$this->dispatch('nama-event', ...)` → komponen lain tangkap dengan `#[On('nama-event')]`; Alpine tangkap dengan `@nama-event.window`.
- Event penting: `transaction-success`, `profile-updated`, `swal:success`, `swal:error`, `update-chart`, `update-admin-chart`, `toast`.

## 8.5 State Client (Alpine.js)

Dipakai untuk UI murni: buka/tutup sidebar, modal, dropdown, toggle password, masking input rupiah. `@entangle('propertyLivewire')` menyambungkan state Alpine ke properti Livewire.

---

# 9. API INTEGRATION

Base: `routes/api.php`, controller `MahasiswaAuthController`. Auth via **Sanctum** (Bearer token). Hanya untuk role `mahasiswa` (aplikasi Flutter).

## 9.1 Daftar Endpoint

### `POST /api/login` — Publik
Request: `{ email, password }` (validasi `LoginMahasiswaRequest`).
Logika:
1. Cari user by email.
2. Cek: user ada, `Hash::check(password)` benar, `role === 'mahasiswa'`. Gagal → `401`.
3. Catat `login_logs`.
4. `createToken('flutter-mobile-app')` → token plain text.
Response `200`:
```json
{ "status":"success", "message":"Login Berhasil",
  "data": { "user": {"id","name","email"}, "token":"..." } }
```

### `GET /api/profile` — `auth:sanctum`
Return `MahasiswaResource($user)` — id, name, email, role, `student_profile` (nim, jurusan, no_hp, alamat, semester, ipk, status, saldo, ktm_image URL).

### `POST /api/logout` — `auth:sanctum`
`currentAccessToken()->delete()` → hapus token.

### `POST /api/pay-qr` — `auth:sanctum` ⭐
Request: `{ order_id }`.
Logika (dalam `DB::transaction`):
1. Lock `MahasiswaProfile` mahasiswa login.
2. Cari `Transaction` by `order_id` status `pending` (lock). Tidak ada → exception.
3. Cek `saldo >= total_amount`. Kurang → exception.
4. `MahasiswaProfile->decrement('saldo', total_amount)`.
5. Hitung: `hakLkbb = total_pokok + fee_lkbb`; `hakMerchant = (total_amount − total_pokok) − fee_lkbb`.
6. `MerchantProfile->increment('saldo_token', hakMerchant)`.
7. `Wallet LKBB_OPERATIONAL->increment('balance', hakLkbb)` (buat jika belum ada).
8. `Transaction->update(user_id, status='sukses')`.
Response: `200` sukses / `400` error (saldo kurang, transaksi tidak ada).

### `GET /api/transactions` — `auth:sanctum`
Riwayat `pembayaran_makanan` status `sukses/lunas`, `paginate(15)`. Return data + meta paginasi.

### `POST /api/update-avatar` — `auth:sanctum`
Upload `avatar` (image, max 2MB) → simpan ke `avatars/`, hapus foto lama, return URL.

### `POST /api/update-profile` — `auth:sanctum`
Update `no_hp` & `alamat` di `MahasiswaProfile`.

## 9.2 Format Response Konsisten

```json
{ "status": "success" | "error",
  "message": "...",
  "data": { ... } }
```

## 9.3 Integrasi Eksternal

- **QR Code:** `https://api.qrserver.com/v1/create-qr-code/` — generate gambar QR di POS.
- **Fonts:** Bunny Fonts & Google Fonts (CDN).
- **ApexCharts, Chart.js, SweetAlert2:** CDN.

---

# 10. CONFIGURATION

## 10.1 Variabel Lingkungan (`.env`)

| Variabel | Nilai Aktif | Fungsi |
|----------|-------------|--------|
| `APP_NAME` | Laravel | Nama aplikasi (belum di-rebrand) |
| `APP_ENV` | local | Lingkungan |
| `APP_KEY` | base64:... | Kunci enkripsi (WAJIB ada) |
| `APP_DEBUG` | true | Mode debug (⚠️ harus `false` di produksi) |
| `APP_URL` | http://localhost | URL dasar |
| `DB_CONNECTION` | mysql | Driver DB |
| `DB_HOST` / `DB_PORT` | 127.0.0.1 / 3306 | Host DB |
| `DB_DATABASE` | scfs_web | Nama database |
| `DB_USERNAME` / `DB_PASSWORD` | root / (kosong) | Kredensial DB |
| `SESSION_DRIVER` | file | Penyimpanan sesi |
| `SESSION_LIFETIME` | 120 | Umur sesi (menit) |
| `QUEUE_CONNECTION` | database | Driver antrian job |
| `CACHE_STORE` | file | Penyimpanan cache |
| `MAIL_MAILER` | log | Email ditulis ke log (bukan dikirim) |
| `BCRYPT_ROUNDS` | 12 | Kekuatan hashing |

> ⚠️ `.env` (aktif) memakai `mysql` + `SESSION_DRIVER=file`. `.env.example` memakai `sqlite` + `SESSION_DRIVER=database`. Beda — lihat §14.

## 10.2 File Config Penting

| File | Setting Non-Default |
|------|---------------------|
| `config/app.php` | locale `en` |
| `config/auth.php` | Default Breeze — guard `web`, provider Eloquent `User`, token reset 60 menit |
| `config/database.php` | Default `sqlite`, tapi `.env` override ke `mysql` |
| `config/session.php` | Default driver `database`, `.env` override ke `file`, lifetime 120 |
| `config/queue.php` | Default `database` |
| `config/sanctum.php` | Default — guard `web`, tanpa expiry token |
| `bootstrap/app.php` | Route web/api/console, health `/up`, **tanpa middleware custom** |
| `vite.config.js` | Entry `resources/css/app.css` + `resources/js/app.js`, refresh on |
| `tailwind.config.js` | Font Figtree/DM Sans, palet `brand` (Horizon-UI) |

## 10.3 Akun Default (Hasil Seeder)

| Email | Password | Role |
|-------|----------|------|
| admin@gmail.com | password | admin |
| lkbb@gmail.com | password | lkbb |
| kantin@gmail.com | password | merchant |
| pemasok@gmail.com | password | pemasok |
| mhs1@gmail.com .. mhs10@gmail.com | password | mahasiswa |

---

# 11. COMMON FLOWS (SKENARIO UMUM)

## 11.1 User Login

```
1. Buka http://localhost:8000 → redirect /login
2. Halaman login custom render (livewire/login.blade.php)
3. Isi email + password → submit (wire:submit="login")
4. Server: validate() → Auth::attempt()
   ├─ GAGAL → ValidationException → pesan "These credentials..." tampil
   └─ SUKSES → event Login (→ LoginLog dicatat)
              → session()->regenerate()
              → redirect /dashboard
5. /dashboard cek role → redirect ke dashboard role
6. Layout + sidebar role dipilih → dashboard render
```

## 11.2 User Create Data (Contoh: Admin Tambah Mahasiswa)

```
1. Admin di /admin/data-mahasiswa → klik "Tambah Mahasiswa"
2. openAddModal() → $isAddModalOpen = true → modal muncul
3. Isi form (wire:model) → submit "saveMahasiswa"
4. Server: validate() (nama, nim, email unique, password min:6, ipk)
   ├─ GAGAL → error per field tampil di bawah input
   └─ SUKSES → User::create() + MahasiswaProfile::create()
              → closeAddModal() → session flash 'message'
5. #[Computed] students recompute → tabel ter-update otomatis
6. Banner sukses tampil
```

## 11.3 Data Loading & Display

```
1. Komponen render → mount() (jika ada) load data awal
2. #[Computed]/with() jalankan query Eloquent
3. Template Blade @foreach/@forelse iterasi data
   @forelse($items as $i) ... @empty (tampilkan "kosong") @endforelse
4. wire:model.live pada search → updatingSearch() → resetPage()
   → query ulang dengan filter → tabel ter-render ulang
5. Pagination: {{ $items->links() }} → klik halaman → query ulang
```

## 11.4 Error Handling Flow

```
A. Error VALIDASI:
   validate() gagal → ValidationException → Livewire tangkap
   → <x-input-error> / @error tampilkan pesan di field

B. Error BISNIS (saldo kurang, dll):
   throw new Exception("...") di dalam DB::transaction
   → DB rollback otomatis
   → catch → session()->flash('error', $e->getMessage())
   → banner merah tampil

C. Error FATAL (APP_DEBUG=true):
   → halaman Whoops (stack trace)
   (APP_DEBUG=false → halaman 500 generik)
```

---

# 12. ERROR HANDLING

## 12.1 Strategi per Lapisan

| Lapisan | Mekanisme |
|---------|-----------|
| **Validasi input** | `$this->validate()` / `#[Validate]` / `LoginMahasiswaRequest` → `ValidationException` |
| **Aturan bisnis** | `throw new Exception("pesan")` di dalam `DB::transaction` → rollback |
| **Race condition** | `lockForUpdate()` (pessimistic lock) di POS, ApprovalPo, payQr |
| **Idempotensi** | Guard cek status sebelum proses (mis. `ApprovalPo`: `if status != 'menunggu_lkbb' throw`) |
| **API** | `try/catch` → JSON `{status:error, message}` dengan kode HTTP sesuai |
| **Notifikasi user** | `session()->flash('error')` → banner; `dispatch('swal:error')` → popup |

## 12.2 Contoh Penanganan Error (POS Tunai)

```php
try {
    DB::transaction(function () {
        // ... lock, decrement stok, hitung fee ...
        if ($produk->stok < $qty) throw new \Exception("Stok tidak cukup");
        // ...
    });
    session()->flash('success', 'Pembayaran berhasil');
} catch (\Exception $e) {
    session()->flash('error', $e->getMessage()); // DB sudah rollback
}
```

## 12.3 Kasus Error yang Ditangani

| Kasus | Lokasi | Penanganan |
|-------|--------|------------|
| Kredensial salah | login | `ValidationException(auth.failed)` |
| Rate limit login | `LoginForm` | 5x gagal → lockout + event `Lockout` |
| Saldo brankas kurang | `ApprovalPo`, `approval/mahasiswa` | exception → rollback → flash error |
| Stok habis | `pos-merchant`, `order-bahan` | exception / flash "Maksimal pesanan..." |
| Double-cairkan PO | `ApprovalPo` | guard status → exception "sudah diproses" |
| Saldo mahasiswa kurang | `payQr` API | exception → JSON 400 |
| Transaksi QR tidak ada | `payQr` API | exception → JSON 400 |
| Hapus akun sendiri | `user-management` | guard `auth()->id() === user->id` → flash error |
| Merchant belum verifikasi | `order-bahan` | `abort(403)` |
| Akses non-admin ke dashboard admin | `dashboard.admin` | `abort(403)` |
| Password konfirmasi salah | `profile`, `ProfilePemasok` | `Hash::check` → `ValidationException` |
| WD pending ganda | `merchant/withdraw` | guard `exists()` → exception |

---

# 13. PERFORMANCE

## 13.1 Sudah Baik

- ✅ **Pessimistic locking** (`lockForUpdate`) cegah race condition di transaksi uang.
- ✅ **DB transaction** menjamin atomicity (ACID) di operasi keuangan.
- ✅ **Eager loading** (`with()`) di banyak query — kurangi N+1.
- ✅ **Pagination** di list besar (`user-management`, `merchant-data`, modul LKBB, merchant `riwayat`).
- ✅ **Debounce** pada input search (`wire:model.live.debounce.300ms`).
- ✅ **`wire:ignore`** pada container grafik supaya Livewire tak re-render canvas.

## 13.2 Peluang Optimasi

| Masalah | Dampak | Saran |
|---------|--------|-------|
| Banyak list pakai `->get()` tanpa paginate | Lambat saat data besar (admin: mahasiswa-data, pemasok-data, investor-data, donatur-data) | Ganti ke `paginate()` |
| Aset CDN (ApexCharts, Chart.js, SweetAlert2) | Bergantung internet, render blocking | Bundle via npm/Vite |
| `monitoring-transaksi` `limit(100)` tanpa index | Scan tabel | Tambah index pada kolom filter |
| `wire:poll.2s` di POS | Request tiap 2 detik per kasir aktif | Pertimbangkan WebSocket/Echo |
| Query computed dihitung tiap render | Beberapa query berat berulang | Cache hasil bila perlu |
| Tidak ada index DB selain bawaan FK/unique | Query filter lambat | Tambah index pada `transactions.status`, `type`, `created_at` |
| QR via API eksternal | Latensi pihak ketiga | Pakai library lokal (mis. `simple-qrcode`) |
| `RiwayatProduksi` query tak di-scope user | Ambil semua data + lambat (sekaligus bug keamanan) | Tambah `where('user_id', Auth::id())` |

## 13.3 Pertimbangan Skala

- `QUEUE_CONNECTION=database` cukup untuk skala kecil; untuk besar pakai Redis.
- `SESSION_DRIVER=file` tidak cocok multi-server; pakai `database`/`redis`.
- `CACHE_STORE=file` — sama, pakai `redis` untuk produksi.

---

# 14. TROUBLESHOOTING

## 14.1 Masalah Setup Umum

| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| `No application encryption key` | `APP_KEY` kosong | `php artisan key:generate` |
| `SQLSTATE... Unknown database 'scfs_web'` | Database MySQL belum dibuat | Buat DB `scfs_web` di MySQL, lalu `php artisan migrate` |
| Halaman blank / aset 404 | Belum build aset | `npm install && npm run build` (atau `npm run dev`) |
| `Class "..." not found` | Autoload belum di-update | `composer dump-autoload` |
| Foto upload tidak tampil | Symlink storage belum ada | `php artisan storage:link` |
| `.env` vs `.env.example` beda | `.env` pakai mysql, example pakai sqlite | Sesuaikan `DB_CONNECTION` dengan DB tersedia |
| Migrasi error `supply_chains` | Tabel di-drop migrasi 05_12 | Normal — `SupplyChain` memang dead code |
| Login API 401 walau benar | User bukan role `mahasiswa` | API login hanya untuk mahasiswa |

## 14.2 Cara Debugging

```bash
# Lihat log real-time
php artisan pail
# atau buka storage/logs/laravel.log

# Cek route terdaftar
php artisan route:list

# Bersihkan cache config/route/view
php artisan optimize:clear

# REPL untuk inspeksi data
php artisan tinker
>>> App\Models\Wallet::all()
```

- `APP_DEBUG=true` → halaman error detail (Whoops) saat dev.
- `dd($var)` / `dump()` untuk inspeksi variabel di komponen.
- `MAIL_MAILER=log` → email "terkirim" muncul di `laravel.log`.

## 14.3 Masalah Spesifik Aplikasi

| Gejala | Penyebab | Lihat |
|--------|----------|-------|
| Halaman `/keuangan/penagihan` error fatal | File `penagihan.blade.php` rusak (variabel undefined, import hilang) | §18 |
| Saldo brankas tidak sinkron | Dua skema nama dompet (A vs B) | §18 |
| Popup SweetAlert dobel | Listener didaftarkan 2x di `app.blade.php` | §18 |
| Halaman pemasok "Tarik Dana" tidak menyimpan | Modul `TarikDana` 100% simulasi | §18 |
| Komponen supply-chain LKBB error | Tabel `supply_chains` sudah di-drop | §18 |

---

# 15. TESTING

## 15.1 Framework

- **Pest 4** (di atas PHPUnit), plugin `pest-plugin-laravel`.
- Konfigurasi `phpunit.xml`: 2 suite — `Unit` (`tests/Unit`), `Feature` (`tests/Feature`).
- Env test: SQLite `:memory:`, `BCRYPT_ROUNDS=4`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`.

## 15.2 Test yang Ada

| File | Cakupan |
|------|---------|
| `tests/Feature/Auth/AuthenticationTest.php` | Render login, login sukses/gagal, render nav, logout |
| `tests/Feature/Auth/RegistrationTest.php` | Render register, registrasi user baru |
| `tests/Feature/Auth/EmailVerificationTest.php` | Verifikasi email |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | Konfirmasi password |
| `tests/Feature/Auth/PasswordResetTest.php` | Reset password |
| `tests/Feature/Auth/PasswordUpdateTest.php` | Update password |
| `tests/Feature/ProfileTest.php` | Tampil profil, update info, hapus akun |
| `tests/Feature/ExampleTest.php` | Test contoh (GET `/`) |
| `tests/Unit/ExampleTest.php` | Test unit contoh |

## 15.3 Keterbatasan Testing

> ⚠️ **Test hanya meng-cover fitur auth bawaan Breeze.** Test menargetkan komponen Breeze (`pages.auth.login`, `pages.auth.register`) — **bukan** halaman custom SCFS (`livewire/login`, `livewire/register`).
>
> **Tidak ada test** untuk logika bisnis inti: POS, pembayaran QR, approval PO, mutasi saldo, supply chain, withdrawal. Ini area risiko utama — semua logika keuangan tidak teruji otomatis.

## 15.4 Menjalankan Test

```bash
composer test
# atau
php artisan test
php artisan test --filter=AuthenticationTest
```

## 15.5 Strategi Test yang Disarankan

1. **Feature test logika keuangan** — POS tunai/QR, `payQr`, `ApprovalPo::setujuiPendanaan`, withdrawal. Assert saldo & ledger setelah operasi.
2. **Test idempotensi** — pastikan double-submit tidak menggandakan mutasi uang.
3. **Test race condition** — concurrency pada `lockForUpdate`.
4. **Test alur SupplyOrder** — transisi status end-to-end.

---

# 16. DEPLOYMENT

## 16.1 Setup Local Development

```bash
# 1. Clone & masuk folder
cd scfs-web

# 2. Install dependensi
composer install
npm install

# 3. Siapkan .env
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env (mysql atau sqlite)
#    Jika MySQL: buat database 'scfs_web' dulu
#    Jika SQLite: pastikan database/database.sqlite ada

# 5. Migrasi + seeder
php artisan migrate --seed

# 6. Symlink storage (untuk file upload)
php artisan storage:link

# 7. Jalankan (cara cepat — semua sekaligus)
composer run dev
#   → menjalankan: php artisan serve + queue:listen + pail + npm run dev
```

`composer run dev` menjalankan 4 proses paralel (`concurrently`): server, queue, log, vite.

## 16.2 Build Produksi

```bash
composer install --optimize-autoloader --no-dev
npm run build                       # compile aset ke public/build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 16.3 Checklist Produksi

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` ter-generate
- [ ] Database produksi dikonfigurasi & dimigrasi
- [ ] `php artisan storage:link`
- [ ] Web server arahkan document root ke `public/`
- [ ] Permission folder `storage/` & `bootstrap/cache/` writable
- [ ] Queue worker jalan (`php artisan queue:work`) — disupervisi (Supervisor/systemd)
- [ ] `SESSION_DRIVER`, `CACHE_STORE` → `redis`/`database` (bukan `file`) jika multi-server
- [ ] `MAIL_MAILER` dikonfigurasi SMTP nyata
- [ ] HTTPS aktif
- [ ] Backup database terjadwal

## 16.4 Script Composer Tersedia

| Script | Fungsi |
|--------|--------|
| `composer setup` | Install + key + migrate + npm install + build (setup penuh) |
| `composer dev` | Jalankan server+queue+log+vite paralel |
| `composer test` | `config:clear` + `artisan test` |

---

# 17. DEVELOPMENT GUIDELINES

## 17.1 Konvensi Kode

- **PSR-4 autoload:** `App\` → `app/`.
- **Code style:** Laravel Pint (`./vendor/bin/pint`).
- **Bahasa:** kode & komentar campuran Indonesia/Inggris (UI Bahasa Indonesia).
- **Model:** `$fillable` atau `$guarded` selalu didefinisikan (cegah mass-assignment).
- **Migrasi:** satu perubahan = satu file migrasi baru (jangan edit migrasi lama).

## 17.2 Pola yang Dipakai

| Pola | Penggunaan |
|------|------------|
| Volt single-file | Mayoritas halaman — class anonim + Blade dalam 1 file |
| Class-based Livewire | Modul Pemasok & `ApprovalPo` |
| `#[Computed]` | Data turunan di template |
| `#[Layout(...)]` | Tentukan layout komponen |
| `DB::transaction` + `lockForUpdate` | Semua operasi keuangan |
| Snapshot kolom | `*_snapshot` di order detail — simpan harga saat transaksi |
| Nomor dokumen | Prefix + tanggal + random (`PO-`, `WD-`, `ST-`, `TRX-`, `INJ-`, `DIG-`, `UMM-`) |

## 17.3 Cara Menambah Fitur Baru (Halaman Volt)

```
1. Buat file: resources/views/livewire/{modul}/{nama}.blade.php
   ┌──────────────────────────────────────────────┐
   │ <?php                                        │
   │ use Livewire\Volt\Component;                  │
   │ use Livewire\Attributes\Layout;               │
   │ new #[Layout('layouts.app')] class extends    │
   │     Component {                               │
   │     public $state = '';                       │
   │     public function aksi() { /* ... */ }      │
   │     public function with() { return [...]; }  │
   │ }; ?>                                          │
   │ <div> {{-- template Blade --}} </div>         │
   └──────────────────────────────────────────────┘

2. Daftarkan route di routes/web.php:
   Volt::route('/modul/nama', 'modul.nama')->name('modul.nama');

3. Tambah link di sidebar terkait (livewire/layout/{role}-sidebar.blade.php)

4. (Jika butuh tabel) buat migrasi:
   php artisan make:migration create_xxx_table
   lalu php artisan migrate
```

## 17.4 Best Practices untuk Proyek Ini

- **Operasi uang:** SELALU bungkus `DB::transaction`, pakai `lockForUpdate`, dan tambahkan **guard idempotensi** (cek status sebelum mutasi). Contoh terbaik: `ApprovalPo::setujuiPendanaan()`.
- **Catat ledger:** setiap mutasi `wallet.balance` idealnya diikuti `LedgerEntry` (beberapa halaman LKBB melanggar ini — perbaiki).
- **Pakai `FinanceService`** untuk transfer antar dompet daripada `increment/decrement` manual.
- **Konsistenkan** nama tipe dompet & nilai string status (lihat §18 — banyak inkonsistensi).
- **Scope query** ke `Auth::id()` pada data milik user.
- **Validasi** semua input sebelum proses.
- **Hindari** kode dummy/simulasi di jalur produksi (banyak `getDummy*`, `simulasi*`, `buat*Dummy`).

## 17.5 Hal yang Harus Diperhatikan

- Jangan edit migrasi yang sudah jalan di produksi.
- Hati-hati: ada **dua sistem status** pada `SupplyOrder` (`status` vs `status_pembiayaan`) yang sebagian tumpang tindih.
- `SupplyChain` & modul supply-chain LKBB adalah **dead code** (tabel di-drop) — jangan dikembangkan, refactor ke `SupplyOrder`.

---

# 18. LAMPIRAN — DAFTAR BUG & INKONSISTENSI DIKETAHUI

Bagian ini merangkum temuan dari analisis menyeluruh. Penting dipahami agar tidak salah arah saat development.

## 18.1 🔴 Kritis

| # | Masalah | Lokasi | Dampak |
|---|---------|--------|--------|
| 1 | ✅ **SELESAI (2026-05-22)** — ~~Dua skema nama dompet (Skema A vs Skema B)~~. Skema B dihapus: file `wallet-index`/`wallet-card`/`supply-chain/bills` dibuang + migrasi `2026_05_22_000001` hapus baris dompet lama. | — | Treasury sudah tunggal: `LKBB_INVESTMENT/DONATION/OPERATIONAL`. |
| 2 | **`penagihan.blade.php` rusak total** | `lkbb/keuangan/penagihan.blade.php` | Variabel `$setId`/`$petugas` undefined, `use WithPagination` tanpa import, Blade malformed → **halaman fatal saat dibuka** |
| 3 | **Tabel `supply_chains` di-drop** tapi kode masih memakainya | Migrasi `2026_05_12`; dipakai `PengajuanDanaLkbb`, `lkbb/supply-chain/*` | Fitur SCF berbasis `SupplyChain` **error** kalau migrasi dijalankan penuh |
| 4 | **`approval-po.blade.php` tanpa blok PHP/Volt** | `lkbb/approval-po.blade.php` | Hanya template; logika ada di class `App\Livewire\Lkbb\ApprovalPo` — file ini sendiri tak bisa render mandiri |
| 5 | **Saldo mahasiswa di 2 tempat** — `STUDENT_WALLET.balance` vs `mahasiswa_profiles.saldo` | `keuangan/mahasiswa` (pakai wallet) vs `approval/mahasiswa` & API `payQr` (pakai profil) | Dua sumber kebenaran saldo beasiswa yang tidak sinkron |

## 18.2 🟠 Penting

| # | Masalah | Lokasi |
|---|---------|--------|
| 6 | Top-up merchant **non-atomik & tanpa ledger/transaction** | `lkbb/keuangan/merchant.blade.php` |
| 7 | ✅ **SELESAI (2026-05-22)** — ~~`supply-chain/bills` pindahkan uang tanpa `LedgerEntry`~~. File `bills.blade.php` dihapus (dead code, tak ada route). | — |
| 8 | Dua sistem status `SupplyOrder`: `status` vs `status_pembiayaan` — tumpang tindih, dijembatani manual | `SupplyOrder`, `PesananMasuk`, `PengajuanDanaLkbb`, `ApprovalPo` |
| 9 | Modul **`TarikDana` & `LaporanAnalitik` pemasok 100% dummy** — data hardcoded, tidak persist | `app/Livewire/Pemasok/TarikDana.php`, `LaporanAnalitik.php` |
| 10 | `RiwayatProduksi`: query `$riwayat` **tidak di-scope `user_id`** → pemasok lihat data pemasok lain | `app/Livewire/Pemasok/RiwayatProduksi.php` |
| 11 | **Tanpa middleware role** — route hanya `auth`. Hanya `dashboard.admin` punya `abort(403)` | `routes/web.php` |
| 12 | Dua model profil pemasok: `PemasokProfile` (tabel lama) & `SupplierProfile` (tabel baru) dipakai bercampur | `ProfilePemasok` pakai `SupplierProfile`; `PengajuanDanaLkbb`/`TarikDana` pakai `PemasokProfile` |
| 13 | Kolom `Transaction` tidak konsisten: `monitoring-transaksi` pakai `amount/reference_number/category`, komponen lain pakai `total_amount/order_id` | `admin/monitoring-transaksi.blade.php` |

## 18.3 🟡 Minor

| # | Masalah | Lokasi |
|---|---------|--------|
| 14 | Script SweetAlert2 & listener `swal:success` didaftarkan **2x** → popup dobel | `layouts/app.blade.php` |
| 15 | Nilai status string tidak konsisten: `success` vs `sukses`, `PENDING` vs `pending`, `lunas` | Banyak komponen |
| 16 | `supply-chain/create` simpan `status='PENDING'` tapi tabelnya cek `'pending'` → badge selalu salah | `lkbb/supply-chain/create.blade.php` |
| 17 | `LedgerEntry.balance_after` dicatat tanpa `fresh()` → bisa stale | `injeksi-saldo`, `approval/mahasiswa` |
| 18 | Prefix deskripsi POS tunai `[UMUM]` tapi laporan strip `[TUNAI]` → deskripsi tunai tak terbersihkan | `pos-merchant` vs `dashboard/riwayat` |
| 19 | Link sidebar `href="#"` (placeholder belum dirouting) | admin: Distribusi Saldo, PO & Pendanaan, Setoran Tunai, Riwayat Bagi Hasil; pemasok: Informasi Saldo |
| 20 | `investor-data` avatar pakai relasi salah (`pemasokProfile` bukan `investorProfile`) | `admin/investor-data.blade.php` |
| 21 | Kode dummy/test di jalur produksi: `getDummyPo`, `getDummyDonasi`, `simulasiTambahDonasi`, `buatTransaksiDummy` | `pemasok-detail`, `donatur-detail`, `monitoring-transaksi` |
| 22 | `tailwind.config.js`: key `sans` salah letak di dalam `colors` | `tailwind.config.js` |
| 23 | `riwayat.blade.php` tombol "Unduh Laporan Excel" tanpa handler | `merchant/riwayat.blade.php` |
| 24 | QR pending reserve stok **tanpa expiry** → sesi QR ditinggal menahan stok selamanya | `merchant/pos-merchant.blade.php` |
| 25 | `Transaction` saat `PEMBIAYAAN_PO` set `sender_wallet_id` tapi tanpa `receiver_wallet_id` | `ApprovalPo` |
| 26 | API `payQr` & POS pakai status `sukses`, sebagian laporan cek `success/lunas` | beragam |
| 27 | Register custom: role diambil langsung dari dropdown user, tanpa guard server selain rule `in:` | `livewire/register.blade.php` |

## 18.4 Status Kematangan Modul

```
✅ MATANG (logika nyata, atomik)   : POS Merchant, ApprovalPo, Withdraw Merchant,
                                     Approval Mahasiswa, Onboarding Merchant
🟡 SEBAGIAN (jalan tapi ada isu)   : Modul Admin, Order Bahan, Katalog, Penerimaan,
                                     Setoran, Pesanan Online, Manajemen Produk Pemasok
🔴 RUSAK / DUMMY / DEAD CODE       : penagihan.blade.php, supply-chain/* (tabel drop),
                                     TarikDana, LaporanAnalitik, PengajuanDanaLkbb
```

---

## 📌 RINGKASAN EKSEKUTIF

**SCFS Web** adalah aplikasi Laravel 12 + Livewire/Volt untuk **ekosistem keuangan kantin kampus**, menghubungkan 6 peran (admin, LKBB, merchant, pemasok, mahasiswa, investor/donatur) di sekitar sebuah lembaga keuangan (LKBB) yang membiayai seluruh rantai pasok. Fitur inti: **POS kantin dengan pembayaran QR beasiswa**, **supply-chain financing** (kantin pesan bahan → LKBB danai → pemasok kirim), **3 brankas treasury**, **bagi hasil otomatis**, dan **API mobile** untuk mahasiswa.

Aplikasi **fungsional pada jalur utama** (POS, approval PO, withdrawal sudah atomik & benar), tetapi mengandung **sejumlah inkonsistensi arsitektur signifikan** — terutama dua skema penamaan dompet, beberapa file rusak/dead-code, dan modul pemasok yang masih simulasi. Prioritas perbaikan: satukan skema dompet (#1), perbaiki `penagihan.blade.php` (#2), bereskan dead code `SupplyChain` (#3), dan tambahkan test untuk logika keuangan.

---

*Dokumen dihasilkan dari analisis menyeluruh seluruh kode sumber: 52 migrasi, 27 model, 9 controller/service, ~80 komponen Livewire/Volt, routing, konfigurasi, dan test.*
