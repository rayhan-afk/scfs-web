# SCFS — Claude AI Agent Instructions

## PROJECT IDENTITY
Project  : SCFS (Supply Chain Financing System)
Stack    : Laravel 11 | PHP 8+ | MySQL | Blade | TailwindCSS | AlpineJS
Pattern  : Layered Architecture (Controller → Service → Repository → DB)
Language : Bahasa Indonesia untuk komentar dan dokumentasi

## ARCHITECTURE RULES (WAJIB DIIKUTI)
- Controller    : hanya handle request, response, validasi ringan
- Service Layer : SEMUA business logic ada di sini
- Repository    : reusable query, abstraction database
- Jangan buat God Controller
- Jangan duplicate logic antar layer

## SECURITY RULES (PRIORITAS UTAMA)
- WAJIB gunakan auth middleware di semua route yang butuh login
- WAJIB gunakan Policy/Gate untuk authorization
- WAJIB validate semua input dengan Form Request
- WAJIB gunakan Eloquent, hindari raw query
- WAJIB CSRF protection aktif
- JANGAN expose data sensitif (nominal payment, dll.)
- JANGAN hardcode credential/API key

## DATABASE RULES
- WAJIB gunakan foreign key
- WAJIB index pada: status, merchant_id, supplier_id, created_at
- WAJIB eager loading untuk relasi (hindari N+1)
- JANGAN SELECT * — selalu select kolom spesifik
- Gunakan soft delete jika data perlu dipertahankan

## PAYMENT SYSTEM — CRITICAL AREA
- SEMUA operasi payment wajib dalam DB::transaction()
- WAJIB locking jika update payment bisa race condition
- HINDARI double submit / duplicate payment
- SEMUA payment event wajib di-audit log

## APPROVAL FLOW
- Jangan bypass approval flow
- Histori approval bersifat immutable
- Reject wajib ada notes/alasan
- Multi-level: Administrative → Financial → Management

## STATUS FLOW — JANGAN SKIP
Supply Order: pending → approved → processing → shipping → delivered → completed
Jika masalah  : processing → rejected | delivered → returned
Purchase Order: pending → approved/rejected → processing → completed/cancelled

## AUDIT TRAIL
- Log semua: login, logout, create, update, delete, approval, reject, payment
- Field wajib: user_id, action, reference_type, reference_id, old_value, new_value, timestamp

## PERFORMANCE
- Gunakan queue untuk heavy process (notifikasi, email, laporan)
- Caching untuk dashboard metrics
- Optimize pagination
- Hindari nested loop query

## REFACTORING RULES
- Sebelum refactor: analisa dependency, side effect, relasi DB
- JANGAN ubah schema sembarangan
- JANGAN merusak sistem existing
- Prioritas: (1) tidak merusak → (2) security → (3) maintainability

## ROLE SYSTEM
Admin    : full access
Merchant : create PO, lihat data sendiri saja
Supplier : lihat order masuk, update pengiriman
Approver : approve/reject transaksi
Monev    : monitoring, audit, laporan
RULE     : User tidak boleh akses data role lain

## COMMON PITFALLS — HINDARI INI
1. ENUM status mismatch antara migration dan model
2. N+1 Query — selalu eager load relasi
3. Duplicate transaction — gunakan unique constraint + transaction
4. Race condition pada payment — gunakan lockForUpdate()
5. Merge conflict — komunikasi sebelum edit file yang sama
