A. Project Architecture Summary
  Stack

  - Backend: Laravel 12 (PHP ^8.2), Sanctum 4.3 (mobile API), Reverb 1.10 (WebSocket)
  - Frontend: Livewire 4.1 + Volt 1.7 (mixed class/inline), Tailwind 3.1 + Alpine.js
  - Realtime/Async: Reverb broadcasting, Redis (Predis 3.4), queue listener
  - Test: Pest 4 (coverage minimal — auth scaffolds only)

  Layered Pattern (de facto)

  Routes (only [auth] middleware — no role guards)
    │
    ├── Volt views  (e.g. resources/views/livewire/merchant/penerimaan.blade.php)
    └── Class Livewire (app/Livewire/Lkbb|Merchant|Pemasok|Forms|Actions/*)
          │
          └── Direct Eloquent + DB::transaction
                │
                └── Models (app/Models/*)
  - app/Services/Finance/FinanceService.php exists with only transfer() and deposit() — most financial side-effects
  bypass it (e.g. ApprovalPo, withdraw, payQr).
  - app/Http/Middleware/ is empty. No app/Policies/. Authorization is ad-hoc inside components.
  - API surface (routes/api.php) is mahasiswa-only via Sanctum.

  Domain Map

  ┌────────────────┬────────────────────────────────────────────────────────────────────────────────────────────┐
  │     Domain     │                                       Entity Cluster                                       │
  ├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ Identity       │ User + 6 profile models (Mahasiswa/Merchant/Pemasok/Supplier/Investor/Donatur)             │
  ├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ Money          │ Wallet (typed) → Transaction (with sender/receiver wallet) → LedgerEntry (debit/credit +   │
  │                │ balance_after)                                                                             │
  ├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ Supply Chain   │ SupplyOrder → SupplyOrderDetail → ProdukPemasok ; SupplyChain (financing agreement)        │
  │ B2B            │                                                                                            │
  ├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ Retail B2C     │ OnlineOrder → OnlineOrderItem → MerchantProduct (+ ProductPriceHistory)                    │
  ├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ Approval       │ PengajuanBantuan (scholarship), PengajuanReturn (dispute), Withdrawal, SetoranTunai        │
  │ Surfaces       │                                                                                            │
  ├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
  │ Audit          │ LoginLog, LedgerEntry, RiwayatOpnamePemasok (stock variance), ProductPriceHistory          │
  └────────────────┴────────────────────────────────────────────────────────────────────────────────────────────┘

  ---
  B. Role & Permission Summary

  Role: admin
  Stored as: users.role
  Dashboard: /admin/dashboard
  Core Surfaces: user mgmt, monitoring
  Auth Check Today: route [auth] only
  ────────────────────────────────────────
  Role: lkbb
  Stored as: users.role
  Dashboard: /lkbb/dashboard
  Core Surfaces: approve PO, approve withdraw, brankas (4 vaults), monitoring return
  Auth Check Today: route [auth] only
  ────────────────────────────────────────
  Role: merchant
  Stored as: users.role
  Dashboard: /merchant/dashboard
  Core Surfaces: POS, online orders, withdraw, setoran tunai, return
  Auth Check Today: route [auth] only; some components check Auth::id() ownership
  ────────────────────────────────────────
  Role: pemasok
  Stored as: users.role
  Dashboard: /pemasok/dashboard
  Core Surfaces: catalog, opname, pesanan masuk, pengiriman, pengajuan dana, return mgmt
  Auth Check Today: gated by pemasokProfile.status_verifikasi='disetujui' in routes/web.php:49 — but that column
  does
     not exist on pemasok_profiles [needs verification]
  ────────────────────────────────────────
  Role: mahasiswa
  Stored as: users.role
  Dashboard: (mobile API)
  Core Surfaces: pay-QR, bantuan request
  Auth Check Today: Sanctum; role check in MahasiswaAuthController
  ────────────────────────────────────────
  Role: investor
  Stored as: users.role
  Dashboard: /dashboard (generic)
  Core Surfaces: —
  Auth Check Today: minimal
  ────────────────────────────────────────
  Role: donatur
  Stored as: users.role
  Dashboard: /dashboard (generic)
  Core Surfaces: —
  Auth Check Today: minimal
  ────────────────────────────────────────
  Role: supplier
  Stored as: separate SupplierProfile with own status_verifikasi
  Dashboard: overlaps with pemasok onboarding
  Core Surfaces: —
  Auth Check Today: Two parallel pemasok concepts coexist (PemasokProfile + SupplierProfile) [needs verification]

  ▎ Critical gap: no role middleware. Any authenticated user can GET /lkbb/approval-scf and trigger
  ▎ setujuiPendanaan() because ApprovalPo itself never checks Auth::user()->role === 'lkbb'.

  ---
  C. Main Flow Summary

  C.1 Supply Chain Financing (the B2B core)

  Merchant creates PO ──► supply_orders.status = menunggu_pemasok
                                │
                ┌───────────────┘
                ▼
  Pemasok PesananMasuk: setuju ──► status = menunggu_lkbb
                            tolak ──► status = ditolak  (END)
                │
                ▼
  Pemasok PengajuanDanaLkbb: bundles approved POs
     creates SupplyChain (invoice INV-SC-YYmmdd-XXXX)
     sets supply_orders.id_pengajuan = invoice, status_pembiayaan = menunggu_lkbb
     margin hardcoded 5% (PengajuanDanaLkbb.php:81)   ◄── TECH DEBT
                │
                ▼
  LKBB ApprovalPo.setujuiPendanaan()  [DB::transaction]
     1. lock LKBB_INVESTMENT wallet
     2. assert balance >= total_estimasi (else throw)
     3. brankas.decrement('balance', total_estimasi)
     4. Transaction::create(type=PEMBIAYAAN_PO)
     5. for each detail: produkPemasok.decrement('stok_sekarang', qty)
     6. supply_orders.status = diproses_pemasok, status_pembiayaan = didanai
                │
                ▼
  Pemasok PengirimanLogistik: input kurir + resi ──► status = dikirim
                │
                ▼
  Merchant penerimaan.blade.php konfirmasiTerima(): ──► status = selesai
     (moves goods to "Gudang Bahan", NOT POS yet — that's the is_added_to_pos flag's job [needs verification])

  Two things this flow does NOT do that you'd expect:
  1. No corresponding credit to pemasok wallet at step 3–6 — only LKBB debit + Transaction record. The supplier
  payout path is unclear [needs verification].
  2. No LedgerEntry rows created in ApprovalPo — bypasses FinanceService.transfer(). So the LKBB brankas debit lives   only in wallets.balance and the Transaction row, with no balance_after audit trail.

  C.2 Retail PayQR (mobile, mahasiswa → merchant)

  Path: MahasiswaAuthController.php:99-156 (POST /api/pay-qr)
  - Computes hakLkbb = total_pokok + fee_lkbb
  - Computes hakMerchant = (total_amount - total_pokok) - fee_lkbb
  - [needs verification] — does it create LedgerEntry rows? Agent couldn't find them in the controller. Likely a
  missing audit trail.

  C.3 Withdrawal (merchant cash-out)

  Merchant withdraw.blade.php: ajukanPencairan()
     - validates nominal, exactly one pending allowed
     - optional: tagihan_setoran_tunai consolidation
     - decrements merchant_profiles.saldo_token immediately
     - Withdrawal.status = pending
     │
  LKBB withdraw-merchant-approval.blade.php:
     approve() ──► status = disetujui  (manual bank transfer outside system)
     reject()  ──► status = ditolak, refund saldo_token + tagihan_setoran_tunai
  - Withdrawal model fillable still includes potongan_lkbb even though revisi_alur_scf_v1 migration dropped that
  column. This will throw on mass-assign with that key.
  - Approval is acknowledged "manual": comment in approval view says "Pastikan Anda sudah mentransfer uangnya!" —
  payout is human-driven.

  C.4 Return Dispute Flow (newest, WIP)

  Merchant FormReturn: simpanReturn() ──► status = pending
                                              │
  Pemasok ManajemenReturn:                    │
     prosesSetuju ──► status = disetujui      │ (no refund/resend logic visible)
     prosesTolak  ──► status = ditolak ───────┤
                                              │
  Merchant FormReturn.ajukanBanding(): ──► status = banding_lkbb
                                              │
  LKBB MonitoringReturn.putuskanSengketa(): ──► status = selesai_lkbb
     stores "Keputusan LKBB (X): {catatan}"   (no money/inventory movement)
  - FormReturn.php:44-46 contains an active schema-uncertainty comment indicating the developer was unsure whether
  to use pemasok_id or supplier_id. The current write is supplier_id => $order->pemasok_id.
  - No actual refund/restitution logic exists at any approval node — only state changes [needs verification].

  C.5 Onboarding (Merchant + Supplier)

  - Merchant: merchant_profiles.status_verifikasi lifecycle: belum_melengkapi → menunggu_review → disetujui|ditolak.   Notifications: NewMerchantSubmission (broadcast+db), MerchantApproved (broadcast+db), MerchantRejected.
  - Supplier: uses SupplierProfile model (not PemasokProfile). NewSupplierSubmission notification is db-only (no
  broadcast) — inconsistent with merchant flow.

  ---
  D. Critical Area Identification

  These five areas should require extra scrutiny on any change:

  ┌─────┬────────────────────────────────────────────────┬──────────────────────────────────────────────────────┐
  │  #  │                      Area                      │                     Why Critical                     │
  ├─────┼────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │     │                                                │ Single point where LKBB funds leave the brankas,     │
  │ D1  │ app/Livewire/Lkbb/ApprovalPo.php               │ supplier stock is booked, and order state advances.  │
  │     │                                                │ No policy guard. No LedgerEntry.                     │
  ├─────┼────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │     │ app/Services/Finance/FinanceService.php (and   │ Only correctly-paired ledger writer. Bypassed by     │
  │ D2  │ its bypass)                                    │ ApprovalPo, PayQR, Withdrawal, SetoranTunai. Any new │
  │     │                                                │  financial flow MUST route through here.             │
  ├─────┼────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │     │ Wallet typing (Wallet::type) and the 3 LKBB    │ LKBB_INVESTMENT, LKBB_OPERATIONAL, LKBB_DONATION are │
  │ D3  │ vaults                                         │  regulatory-segregated funds. Cross-vault movement   │
  │     │                                                │ without intent is a major correctness bug.           │
  ├─────┼────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │     │                                                │ Direct decrement/increment outside FinanceService,   │
  │ D4  │ merchant_profiles.saldo_token +                │ with refund-on-reject logic in approval view. Race   │
  │     │ tagihan_setoran_tunai + Withdrawal interaction │ risk; uses lockForUpdate only on reject path [needs  │
  │     │                                                │ verification — confirm approve path locks].          │
  ├─────┼────────────────────────────────────────────────┼──────────────────────────────────────────────────────┤
  │ D5  │ total_pokok / fee_lkbb / margin fields         │ Drive the merchant↔LKBB profit split. Wrong values   │
  │     │                                                │ silently change every transaction's economics.       │
  └─────┴────────────────────────────────────────────────┴──────────────────────────────────────────────────────┘

  ---
  E. Technical Debt Identification

  ┌─────┬─────────────────────────────────────────────┬────────────────────────────────────┬───────────────────┐
  │ ID  │                    Item                     │          File / Location           │       Risk        │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │ Duplicate migration updating same enum 20   │ 2026_05_18_141335_* and            │ Migration run     │
  │ E1  │ min apart                                   │ 2026_05_18_143458_*                │ order quirks;     │
  │     │                                             │                                    │ clutter           │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E2  │ Withdrawal model fillable still lists       │ app/Models/Withdrawal.php vs       │ Mass-assign       │
  │     │ potongan_lkbb after column drop             │ revisi_alur_scf_v1                 │ exception waiting │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │                                             │ PengajuanDanaLkbb.php:14-16        │                   │
  │ E3  │ Hardcoded LKBB plafon                       │ (explicit "sementara hardcode"     │ Wrong UI numbers  │
  │     │                                             │ comment)                           │                   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E4  │ Hardcoded saldo dummy                       │ TarikDana.php:12-13, 28-62         │ Entire screen is  │
  │     │                                             │                                    │ fake              │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │                                             │                                    │ Should come from  │
  │ E5  │ Hardcoded 5% margin                         │ PengajuanDanaLkbb.php:81           │ config or         │
  │     │                                             │                                    │ per-merchant      │
  │     │                                             │                                    │ setting           │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │                                             │ FormReturn.php:44-46 (TODO-style   │ Stores wrong FK   │
  │ E6  │ Schema-uncertain code                       │ comment)                           │ if assumption     │
  │     │                                             │                                    │ wrong             │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │                                             │ resources/views/livewire/* vs      │ Cognitive load;   │
  │ E7  │ Mixed Volt + class-based Livewire           │ app/Livewire/*                     │ inconsistent      │
  │     │                                             │                                    │ testing surface   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E8  │ Indonesian/English naming mix               │ Withdrawal vs PengajuanReturn,     │ Onboarding        │
  │     │                                             │ BahanBaku vs Product, etc.         │ confusion         │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E9  │ BahanBaku model essentially dead after      │ 2026_03_27_150332_* migration      │ Should be deleted │
  │     │ produk_pemasok pivot                        │                                    │                   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │ Product model appears unused in favor of    │                                    │                   │
  │ E10 │ MerchantProduct/ProdukPemasok [needs        │ app/Models/Product.php             │ —                 │
  │     │ verification]                               │                                    │                   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │ Direct DB writes in Livewire components —   │ ApprovalPo, PengajuanDanaLkbb,     │ Major; see also   │
  │ E11 │ violates .claude/backend-agent.md rule      │ withdraw.blade.php,                │ D2                │
  │     │ "Semua business logic di Service"           │ penerimaan.blade.php               │                   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E12 │ No LedgerEntry writes from ApprovalPo or    │ financial side-effects             │ Audit trail gap   │
  │     │ payQr [needs verification]                  │                                    │                   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E13 │ No tests for business logic                 │ tests/ is auth scaffolds only      │ Regression risk   │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │ Notification dispatch sites not findable by │ [needs verification] — search      │ Real-time UX may  │
  │ E14 │  grep notify(                               │ controllers next                   │ be broken         │
  │     │                                             │                                    │ silently          │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │ E15 │ is_added_to_pos flag added but no code      │ supply_order_details               │ Half-built        │
  │     │ reads it [needs verification]               │                                    │ feature           │
  ├─────┼─────────────────────────────────────────────┼────────────────────────────────────┼───────────────────┤
  │     │ Commit history with "tess", "tipis"         │                                    │                   │
  │ E16 │ suggests local hacking on main 8ab4e18,     │ git log                            │ Process debt      │
  │     │ 71fc2f5                                     │                                    │                   │
  └─────┴─────────────────────────────────────────────┴────────────────────────────────────┴───────────────────┘

  ---
  F. Security Risk Identification

  ID: F1
  Risk: No role-based middleware. Any logged-in user can hit /lkbb/*, /approval/*, /admin/* routes — routes/web.php
    only uses ['auth'].
  Where: all route groups
  Severity: HIGH
  ────────────────────────────────────────
  ID: F2
  Risk: ApprovalPo::setujuiPendanaan() performs LKBB fund disbursement with no role/policy check. Combined with F1,
    any authenticated user can drain a brankas.
  Where: app/Livewire/Lkbb/ApprovalPo.php:58-113
  Severity: CRITICAL
  ────────────────────────────────────────
  ID: F3
  Risk: Pemasok dashboard gate references pemasokProfile.status_verifikasi which is not in the migration/model
    fillable — gate may be a no-op or throw.
  Where: routes/web.php:49 vs PemasokProfile
  Severity: HIGH
  ────────────────────────────────────────
  ID: F4
  Risk: Manual money transfer step in withdrawal approval ("Pastikan Anda sudah mentransfer uangnya!") — no
    reconciliation, no callback, no proof.
  Where: lkbb/keuangan/withdraw-merchant-approval.blade.php
  Severity: MEDIUM (process risk)
  ────────────────────────────────────────
  ID: F5
  Risk: Mass-assignment on profiles is wide open (guarded = ['id'] style) — combined with no policies, allows
    privilege escalation if any endpoint mass-fills user-controlled input.
  Where: profile models
  Severity: MEDIUM [needs verification per model]
  ────────────────────────────────────────
  ID: F6
  Risk: API login (mahasiswa) creates a Sanctum token named flutter-mobile-app — no abilities scoping.
  Where: MahasiswaAuthController.php:43
  Severity: MEDIUM
  ────────────────────────────────────────
  ID: F7
  Risk: LoginLog stores IP/UA but no logout tracking, no anomaly detection.
  Where: app/Providers/AppServiceProvider.php:23-32
  Severity: LOW
  ────────────────────────────────────────
  ID: F8
  Risk: Broadcast channel auth only verifies App.Models.User.{id} — fine, but ensure no other channels are added
    without auth gates.
  Where: routes/channels.php
  Severity: LOW
  ────────────────────────────────────────
  ID: F9
  Risk: No CSRF concern (Livewire handles), but file uploads in FormReturn (foto_bukti) — only validates
    image|max:2048. No mime/sniff hardening.
  Where: FormReturn.php
  Severity: LOW
  ────────────────────────────────────────
  ID: F10
  Risk: info_rekening stored as JSON on SupplierProfile. Confirm not exposing it through any API response [needs
    verification].
  Where: SupplierProfile accessors
  Severity: LOW

  ---
  G. Required Rules — for CLAUDE.md

  These are the non-negotiable rules the AI agent must follow when touching this codebase:

  G.1 Financial integrity

  1. Never write to wallets.balance directly. Always go through FinanceService::transfer() or
  FinanceService::deposit(). If a flow can't be expressed through them, add a new method to FinanceService — do not
  inline the math.
  2. Every wallet mutation must produce a LedgerEntry row with entry_type, amount, balance_after. No exceptions,
  including ApprovalPo, payQr, Withdrawal approval, SetoranTunai settlement.
  3. Wrap every multi-step financial operation in DB::transaction + lockForUpdate on every affected wallet. Existing   FinanceService::transfer is the template.
  4. Respect the 3 LKBB vaults (LKBB_DONATION, LKBB_INVESTMENT, LKBB_OPERATIONAL). Never move funds between them
  without an explicit business decision documented in the PR.
  5. Do not change total_amount, total_pokok, fee_lkbb, harga_modal, margin_pemasok semantics without updating the
  comment block in MahasiswaAuthController::payQr and adding a test.

  G.2 Authorization

  6. Never assume route [auth] middleware is enough. Every Livewire component method that mutates LKBB/admin data
  must call abort_unless(Auth::user()->role === 'lkbb', 403) (or a Policy) at the top.
  7. Ownership checks must precede firstOrFail() writes — every component touching merchant/pemasok-owned data must
  filter by Auth::id() (the merchant penerimaan view is the pattern; ApprovalPo is the anti-pattern).

  G.3 Schema discipline

  8. Do not add a new migration to "fix" an enum within the same day — if you must, delete the previous migration
  (it isn't shipped yet). The 2026_05_18_141335 / 2026_05_18_143458 pair must not be repeated.
  9. Keep model $fillable in sync with migrations. If a column is dropped (see revisi_alur_scf_v1 dropping
  potongan_lkbb), remove it from Withdrawal::$fillable in the same PR.
  10. Indonesian naming for Indonesian domain concepts (Pengajuan*, Setoran*, profiles), English for generic
  primitives (Transaction, Wallet, LedgerEntry). Don't rename existing models — match what's already there.

  G.4 Livewire conventions

  11. Don't introduce a new file pattern. If the area already uses class-based components (app/Livewire/Pemasok/*),
  stay class-based. If it uses Volt (merchant/penerimaan.blade.php), stay Volt.
  12. Don't put business logic in Volt files beyond what already exists — extract to a class component or a service
  when adding new logic.
  13. Use Livewire form objects (app/Livewire/Forms/) only for auth-style validation-heavy forms. For domain forms,
  current convention is in-component properties.

  G.5 Notifications

  14. Both database and broadcast channels are required for user-facing approval notifications (match
  MerchantApproved pattern). NewSupplierSubmission (db-only) is a deviation, not a target.

  G.6 Don't fabricate data

  15. Never replicate the hardcoded-plafon / dummy-saldo pattern seen in PengajuanDanaLkbb and TarikDana. If real
  data is unavailable, fail loudly or skip the screen — do not invent numbers.

  G.7 Don't touch broken things silently

  16. If you find a status_verifikasi reference on PemasokProfile (route gate), flag it as a separate task — don't
  paper over with a column add unless that's the explicit user request.
  17. If you find [needs verification] items from this analysis still present, verify before relying on them.

  ---
  H. Draft PROJECT_CONTEXT.md

  ▎ Drafted in memory only — not written to disk. Copy this if you want to commit it.

  # SCFS — Supply Chain Financing System

  A multi-role Laravel 12 + Livewire 4 platform that connects students (mahasiswa),
  campus food merchants (kantin), suppliers (pemasok), and a non-bank financial
  institution (LKBB) that finances the supply chain.

  ## What the platform actually does

  1. **B2B financing**: A merchant orders raw goods from a pemasok. The pemasok
     asks LKBB to fund the PO. LKBB pays the pemasok up front from its
     `LKBB_INVESTMENT` brankas. Merchant repays from sales over time.
  2. **B2C retail**: Students pay merchants via QR (mobile app, Sanctum API).
     Each transaction is split into `total_pokok` (LKBB principal), `fee_lkbb`
     (LKBB commission), and merchant net.
  3. **Cash settlement**: Merchant cash sales are picked up by LKBB officers
     (`SetoranTunai`); merchant tokens are withdrawn to bank via approval
     (`Withdrawal`).
  4. **Social funding**: `PengajuanBantuan` (student aid), donor/investor wallets
     feed `LKBB_DONATION` and `LKBB_INVESTMENT` brankas respectively.
  5. **Disputes**: `PengajuanReturn` runs merchant → pemasok → (banding) → LKBB.

  ## Roles

  | Role | Lives in `users.role` | Profile | Notes |
  |---|---|---|---|
  | admin | yes | — | Read-only monitoring + user mgmt |
  | lkbb | yes | — | Financial institution. Approves PO, withdraw, bantuan, return appeals |
  | merchant | yes | `merchant_profiles` | Kantin operator; runs POS + receives goods |
  | pemasok | yes | `pemasok_profiles` + (sometimes) `supplier_profiles` | Supplier; ships to merchants |
  | mahasiswa | yes | `mahasiswa_profiles` | Mobile-only; PayQR + bantuan |
  | investor | yes | `investor_profiles` | Funds LKBB_INVESTMENT |
  | donatur | yes | `donatur_profiles` | Funds LKBB_DONATION |

  **Two profile models for one role**: pemasok onboarding writes to
  `SupplierProfile` (with `status_verifikasi`), but the dashboard gate in
  `routes/web.php:49` reads `pemasokProfile.status_verifikasi`. Resolve before
  adding new pemasok features.

  ## The money model

  Three concepts, all keyed by `Wallet`:

  - **`Wallet`** — `decimal(15,2)` `balance`, typed via `Wallet::type`:
    `LKBB_DONATION`, `LKBB_INVESTMENT`, `LKBB_OPERATIONAL`, `MERCHANT`,
    `PEMASOK`, `MAHASISWA`.
  - **`Transaction`** — one row per economic event; carries
    `sender_wallet_id`, `receiver_wallet_id`, `total_amount`, `total_pokok`
    (COGS), `fee_lkbb`.
  - **`LedgerEntry`** — one row per wallet affected per transaction; carries
    `entry_type` (DEBIT/CREDIT) and `balance_after` (post-state). Pair via
    `transaction_id`.

  `app/Services/Finance/FinanceService.php` is the only correct way to
  mutate wallets. It does pessimistic locking, balance assertion, paired
  ledger writes. **Several existing flows bypass it** — that's a known debt,
  not a pattern to copy.

  ### The payQR split (the heart of B2C economics)

  hak_lkbb     = total_pokok + fee_lkbb       // principal returned + commission
  hak_merchant = (total_amount - total_pokok) - fee_lkbb   // net to merchant
  See `MahasiswaAuthController.php:127-130`.

  ## The supply chain status machine

  `supply_orders.status` enum (final, after both 2026_05_18 migrations):

  menunggu_pemasok → menunggu_lkbb → diproses_pemasok → dikirim → selesai
                                                                ↘ ditolak

  `supply_orders.status_pembiayaan` tracks financing state separately
  (`siap_diajukan`, `menunggu_lkbb`, `didanai`, `sedang_diproduksi`,
  `siap_dikirim`).

  `supply_order_details.is_added_to_pos` — flag that goods have been booked
  into merchant POS inventory (set by `merchant/penerimaan.blade.php`
  confirm-receive flow). **[needs verification — confirm a writer exists]**.

  ## Critical flows by file

  - LKBB funds a PO → `app/Livewire/Lkbb/ApprovalPo.php::setujuiPendanaan()`
  - Pemasok bundles POs for funding → `app/Livewire/Pemasok/PengajuanDanaLkbb.php`
  - Merchant confirms goods → `resources/views/livewire/merchant/penerimaan.blade.php`
  - Merchant files return → `app/Livewire/Merchant/FormReturn.php`
  - Pemasok responds to return → `app/Livewire/Pemasok/ManajemenReturn.php`
  - LKBB arbitrates return appeal → `app/Livewire/Lkbb/MonitoringReturn.php`
  - Merchant withdraws → `resources/views/livewire/merchant/withdraw.blade.php`
  - LKBB approves withdraw → `resources/views/livewire/lkbb/keuangan/withdraw-merchant-approval.blade.php`
  - Student PayQR (API) → `app/Http/Controllers/Api/MahasiswaAuthController.php::payQr`

  ## Key migrations to read before changing data

  - `2026_05_11_131159_alter_transactions_for_3_wallets.php` — introduces
    sender/receiver wallet refs on Transaction.
  - `2026_05_11_134951_create_ledger_entries_table.php` — paired ledger.
  - `2026_05_12_211617_revisi_alur_scf_v1.php` — **major refactor**: dropped
    `supply_chains` table, split supplier pricing into `harga_modal` +
    `margin_pemasok`, renamed merchant fee field, dropped
    `withdrawals.potongan_lkbb` (model wasn't updated — debt).
  - `2026_03_27_150332_*` — moved supply_order_details from `bahan_baku_id`
    to `produk_pemasok_id`. `BahanBaku` is essentially legacy after this.

  ## Realtime

  - Reverb at `localhost:8080`, broadcast connection = `reverb`.
  - Only one channel: `App.Models.User.{id}` (private, owner-gated).
  - Use `ShouldBroadcast` + `['database', 'broadcast']` for any new approval
    notification (`MerchantApproved` is the canonical example).

  ## Things this codebase does NOT have (yet)

  - No `app/Policies/`. No gates. No role middleware.
  - No `app/Http/Middleware/` customizations.
  - No automated payout (withdrawal approval is a manual bank transfer).
  - No `Money`/`Amount` value object — raw `decimal(15,2)` everywhere.
  - No business-logic tests (only Laravel Breeze auth scaffolds).
  - No CI/lint configuration committed.

  ---
  I. Draft CLAUDE.md

  ▎ Drafted in memory only — not written to disk.

  # CLAUDE.md — Agent rules for the SCFS repo

  Read PROJECT_CONTEXT.md first. This file is the rulebook for what you must
  and must not do.

  ## 1. Money rules (these are absolute)

  - **Never** write `Wallet::balance` with `->update()`, `->increment()`,
    `->decrement()`, or raw SQL. Use `FinanceService::transfer()` or
    `FinanceService::deposit()`. If the flow needs something new, extend
    `FinanceService` — do not inline.
  - **Every** wallet movement gets a paired `LedgerEntry` (entry_type +
    amount + balance_after, linked to a `Transaction`).
  - Wrap multi-wallet operations in `DB::transaction` + `lockForUpdate()`
    on every wallet you touch.
  - The 3 LKBB vaults (`LKBB_DONATION`, `LKBB_INVESTMENT`,
    `LKBB_OPERATIONAL`) are regulatorily distinct. Cross-vault transfers
    require explicit user approval in chat — they are never incidental.
  - Don't redefine `total_amount`, `total_pokok`, `fee_lkbb`,
    `harga_modal`, `margin_pemasok`. They drive the split formula in
    `MahasiswaAuthController::payQr`.

  ## 2. Authorization rules

  - Routes only carry `[auth]`. Any Livewire component method that mutates
    LKBB or admin data MUST guard:
    ```php
    abort_unless(Auth::user()?->role === 'lkbb', 403);
  - Components touching merchant- or pemasok-owned rows MUST filter by
  Auth::id() BEFORE firstOrFail(). The pattern is in
  resources/views/livewire/merchant/penerimaan.blade.php. The
  anti-pattern is app/Livewire/Lkbb/ApprovalPo.php — do not copy it.
  - Don't introduce a new auth path without telling the user. No new
  Sanctum token names, no new broadcast channels without channel auth.

  3. Livewire conventions

  - Stay in the existing pattern per directory:
    - app/Livewire/Lkbb/, app/Livewire/Pemasok/, app/Livewire/Merchant/
  — class-based.
    - resources/views/livewire/**/*.blade.php with <?php new class extends Component headers — Volt.
  - Don't move logic between the two without an explicit ask.
  - Form validation: use the component's validate() for domain forms;
  reserve app/Livewire/Forms/* for auth-style cases (see LoginForm).
  - File uploads: continue using WithFileUploads and the image|max:2048
  pattern from FormReturn.

  4. Schema discipline

  - If you change a column or enum, update:
    a. the migration
    b. the model's $fillable / $casts
    c. every view/component that reads it
  in the same change.
  - Don't ship two migrations within minutes that edit the same enum. The
  pair 2026_05_18_141335_* and 2026_05_18_143458_* is the warning.
  - Indonesian names for Indonesian domain concepts (Pengajuan*,
  Setoran*, profile models). English for generic primitives
  (Transaction, Wallet, LedgerEntry). Don't rename existing models
  to "be consistent."

  5. Notifications

  - For approval / status-change notifications to end users, implement
  ShouldBroadcast and return both database and broadcast channels.
  Mirror app/Notifications/MerchantApproved.php.
  - Reverb is the broadcast driver (BROADCAST_CONNECTION=reverb).
  - Only one channel pattern is wired: App.Models.User.{id}. Don't add
  channels without updating routes/channels.php.

  6. Things you must NOT do

  - No hardcoded financial values. The plafon block in
  PengajuanDanaLkbb.php:14-16 and the saldo dummy in
  TarikDana.php:12-13, 28-62 are debt, not templates.
  - No silent fixups of the PemasokProfile.status_verifikasi route
  gate. If a task touches that area, surface it to the user.
  - No mass-assigning potongan_lkbb on Withdrawal — the column was
  dropped in revisi_alur_scf_v1. The model fillable is stale; remove
  the key only if cleanup is in scope.
  - No deletion of BahanBaku or Product models without explicit
  user approval, even though they appear unused — verify call sites
  first.

  7. Tests

  - This repo has no business-logic tests yet. When adding a new financial
  flow, add a Pest test that asserts:
    a. wallet balances after operation match expectation,
    b. one LedgerEntry exists per wallet touched,
    c. ledger balance_after equals current wallets.balance,
    d. unauthorized roles get 403.

  8. Indonesian language

  - The product surface is Indonesian. Status labels, validation messages,
  notification titles must be Indonesian (match existing copy — e.g.
  "Antrean", "Ditransfer", "Ditolak"). Code identifiers stay as they are.

  9. Things that need verification before you rely on them

  These are open questions from the initial repo audit. Don't treat them
  as truth without re-checking:

  - Does PayQR actually create LedgerEntry rows? Audit
  MahasiswaAuthController::payQr end-to-end before assuming so.
  - Where does the pemasok wallet get credited when LKBB funds a PO?
  ApprovalPo only debits LKBB; the matching credit isn't in the file.
  - Where is supply_order_details.is_added_to_pos written? If nowhere,
  it's a half-built feature.
  - Where are notifications dispatched? Grep for ->notify( and
  Notification::send( came up sparse.
  - PemasokProfile.status_verifikasi vs SupplierProfile.status_verifikasi
  — two onboarding paths exist; clarify before touching.

  10. Output etiquette

  - Reply in Indonesian if the user does. Mixed is fine if the user mixes.
  - Cite files as path/to/file.php:123 so the user can jump.
  - When you change financial code, show the before/after balance math in
  the PR description, not just the diff.

  ---

  ## Summary of what I produced

  - 5 parallel read-only investigations across auth/roles, financial core, supply chain, returns/approvals, and
  Livewire/UI/debt
  - Synthesized findings into the 9 deliverables (A–I) you asked for, including draft `PROJECT_CONTEXT.md` and
  `CLAUDE.md` shown as code blocks (no files written)
  - Flagged 16 tech-debt items, 10 security risks, and ~7 "needs verification" items the next session should resolve   before relying on them

  **Top three things I'd address first** (my opinion, you decide):
  1. **F2 + F1**: Add a role middleware and guard `ApprovalPo::setujuiPendanaan()` — currently any authenticated
  user can disburse LKBB funds.
  2. **E2**: Strip `potongan_lkbb` from `Withdrawal::$fillable` (column was dropped, mass-assign will throw).
  3. **F3 / "PemasokProfile vs SupplierProfile" confusion**: decide which model owns pemasok verification status
  before any new pemasok work lands.