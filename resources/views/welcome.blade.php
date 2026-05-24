<!DOCTYPE html>
<html lang="id" style="scroll-behavior: smooth;">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TREVORA | Ekosistem Supply Chain Financing Kantin Kampus</title>
    <meta name="description" content="TREVORA menghubungkan kantin, pemasok, dan LKBB dalam satu ekosistem pembiayaan & operasional terintegrasi. Modal mengalir, rantai pasok lancar, bagi hasil transparan."/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "secondary-fixed-dim": "#d0bcff",
                        "on-surface-variant": "#3c4a42",
                        "secondary-container": "#8455ef",
                        "tertiary": "#a43a3a",
                        "on-secondary-fixed-variant": "#5516be",
                        "tertiary-container": "#fc7c78",
                        "surface-container": "#e8f0e9",
                        "on-error": "#ffffff",
                        "secondary-fixed": "#e9ddff",
                        "primary-fixed-dim": "#4edea3",
                        "outline-variant": "#bbcabf",
                        "surface-variant": "#dde4dd",
                        "on-secondary-fixed": "#23005c",
                        "inverse-surface": "#2b322d",
                        "surface": "#f4fbf4",
                        "surface-tint": "#006c49",
                        "primary-container": "#10b981",
                        "secondary": "#6b38d4",
                        "on-secondary-container": "#fffbff",
                        "primary": "#006c49",
                        "surface-container-high": "#e3eae3",
                        "tertiary-fixed-dim": "#ffb3af",
                        "outline": "#6c7a71",
                        "surface-dim": "#d4dcd5",
                        "on-background": "#161d19",
                        "surface-container-lowest": "#ffffff",
                        "inverse-on-surface": "#ebf3eb",
                        "on-primary-fixed-variant": "#005236",
                        "on-tertiary-fixed": "#410005",
                        "surface-container-low": "#eef6ee",
                        "error": "#ba1a1a",
                        "on-secondary": "#ffffff",
                        "on-primary-container": "#00422b",
                        "error-container": "#ffdad6",
                        "background": "#f4fbf4",
                        "inverse-primary": "#4edea3",
                        "on-primary": "#ffffff",
                        "surface-bright": "#f4fbf4",
                        "tertiary-fixed": "#ffdad7",
                        "on-tertiary-fixed-variant": "#842225",
                        "primary-fixed": "#6ffbbe",
                        "on-error-container": "#93000a",
                        "surface-container-highest": "#dde4dd",
                        "on-tertiary-container": "#711419",
                        "on-primary-fixed": "#002113",
                        "on-surface": "#161d19",
                        "on-tertiary": "#ffffff"
                    },
                    fontFamily: { global: ["Geist", "sans-serif"] },
                    spacing: {
                        "md": "24px", "margin-desktop": "64px", "gutter": "24px",
                        "xs": "8px", "lg": "48px", "sm": "16px", "xl": "80px",
                        "margin-mobile": "16px", "base": "4px"
                    },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Geist', sans-serif; box-sizing: border-box; }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .mat-outline {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        }

        /* ─── Gradient Text ─── */
        .text-gradient {
            background: linear-gradient(135deg, #006c49 0%, #6b38d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .text-gradient-soft {
            background: linear-gradient(120deg, #10b981 0%, #6b38d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ─── Glass Card ─── */
        .glass-card {
            background: rgba(255,255,255,0.55);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.75);
            box-shadow: 0 8px 40px rgba(0,108,73,0.04), 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
        }
        .glass-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 60px rgba(107,56,212,0.1), 0 4px 16px rgba(0,0,0,0.06);
            border-color: rgba(107,56,212,0.25);
        }

        /* ─── Feature Card ─── */
        .feature-card {
            background: #fff;
            border: 1px solid rgba(187,202,191,0.4);
            border-radius: 20px;
            padding: 32px 28px;
            transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #006c49, #6b38d4, transparent);
            opacity: 0;
            transition: opacity 0.4s;
        }
        .feature-card:hover { transform: translateY(-8px); box-shadow: 0 24px 60px rgba(0,108,73,0.08); border-color: rgba(0,108,73,0.2); }
        .feature-card:hover::before { opacity: 1; }

        /* ─── Role Card ─── */
        .role-card {
            background: #fff;
            border: 1px solid rgba(187,202,191,0.5);
            border-radius: 24px;
            padding: 28px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
        }
        .role-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 100% 0%, var(--accent, rgba(0,108,73,0.06)) 0%, transparent 60%);
            pointer-events: none;
            transition: opacity 0.4s;
            opacity: 0.55;
        }
        .role-card:hover { transform: translateY(-8px); box-shadow: 0 28px 60px rgba(0,108,73,0.10); border-color: rgba(0,108,73,0.25); }
        .role-card:hover::after { opacity: 1; }

        /* ─── Floating Hero Animation ─── */
        @keyframes float {
            0% { transform: translateY(0px) rotateY(-12deg) rotateX(8deg); }
            50% { transform: translateY(-14px) rotateY(-10deg) rotateX(6deg); }
            100% { transform: translateY(0px) rotateY(-12deg) rotateX(8deg); }
        }
        .animate-float { animation: float 7s ease-in-out infinite; }

        /* ─── Marquee ─── */
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .animate-marquee { animation: marquee 32s linear infinite; }

        /* ─── Scroll Reveal ─── */
        .reveal { opacity: 0; transform: translateY(32px); transition: all 0.9s cubic-bezier(0.16,1,0.3,1); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .reveal-left { opacity: 0; transform: translateX(-32px); transition: all 0.9s cubic-bezier(0.16,1,0.3,1); }
        .reveal-left.active { opacity: 1; transform: translateX(0); }
        .reveal-right { opacity: 0; transform: translateX(32px); transition: all 0.9s cubic-bezier(0.16,1,0.3,1); }
        .reveal-right.active { opacity: 1; transform: translateX(0); }

        .delay-100 { transition-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; }
        .delay-400 { transition-delay: 0.4s; }
        .delay-500 { transition-delay: 0.5s; }
        .delay-600 { transition-delay: 0.6s; }

        /* ─── Perspective ─── */
        .perspective-container { perspective: 1300px; }

        /* ─── Step connector line ─── */
        .step-line { position: absolute; left: 27px; top: 56px; bottom: -32px; width: 2px; background: linear-gradient(to bottom, #006c49, #6b38d4); opacity: 0.18; }

        /* ─── Blob BG ─── */
        .blob1 { position: absolute; width: 700px; height: 700px; background: radial-gradient(circle, rgba(107,56,212,0.10) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
        .blob2 { position: absolute; width: 800px; height: 800px; background: radial-gradient(circle, rgba(0,108,73,0.08) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
        .blob-sm { position: absolute; width: 380px; height: 380px; background: radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }

        /* ─── Glow dot ─── */
        .glow-dot { width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,0.5); animation: ping-glow 2s cubic-bezier(0,0,0.2,1) infinite; }
        @keyframes ping-glow { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.5); } 70% { box-shadow: 0 0 0 8px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }

        /* ─── CTA Buttons ─── */
        .btn-primary {
            background: #006c49; color: #fff; border-radius: 999px;
            font-weight: 600; font-size: 15px; padding: 14px 32px;
            display: inline-block; text-align: center;
            position: relative; overflow: hidden;
            transition: all 0.3s;
        }
        .btn-primary::after {
            content: ''; position: absolute; inset: 0;
            background: rgba(255,255,255,0); transition: background 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,108,73,0.35); }
        .btn-primary:hover::after { background: rgba(255,255,255,0.08); }

        .btn-outline {
            border: 1.5px solid rgba(187,202,191,0.7); border-radius: 999px;
            font-weight: 600; font-size: 15px; padding: 13px 32px;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s; color: #161d19;
            background: rgba(255,255,255,0.8);
        }
        .btn-outline:hover { background: #eef6ee; border-color: rgba(0,108,73,0.3); }

        /* ─── Tab switcher ─── */
        .tab-btn { padding: 8px 20px; border-radius: 999px; font-size: 13px; font-weight: 600; color: #6c7a71; cursor: pointer; transition: all 0.25s; border: none; background: transparent; }
        .tab-btn.active { background: #006c49; color: #fff; box-shadow: 0 4px 16px rgba(0,108,73,0.25); }

        /* ─── Ticker ─── */
        @keyframes ticker-in { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes ticker-out { from { transform: translateY(0); opacity: 1; } to { transform: translateY(-100%); opacity: 0; } }
        .ticker-item { display: inline-block; animation: ticker-in 0.4s ease; }
        .ticker-item.out { animation: ticker-out 0.4s ease forwards; }

        /* ─── Problem card ─── */
        .problem-card {
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid rgba(187,202,191,0.4);
            background: #fff;
            transition: all 0.3s;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .problem-card:hover { transform: translateX(4px); border-color: rgba(186,26,26,0.25); box-shadow: 0 8px 32px rgba(186,26,26,0.05); }

        /* ─── Flow node ─── */
        .flow-node {
            border: 1.5px solid rgba(0,108,73,0.18);
            border-radius: 16px;
            padding: 14px;
            background: #fff;
            box-shadow: 0 4px 16px rgba(0,108,73,0.04);
            transition: all 0.3s;
        }
        .flow-node:hover { transform: translateY(-3px); border-color: rgba(0,108,73,0.4); }

        /* ─── Notification ─── */
        #notification-container .notif { transition: all 0.35s cubic-bezier(0.16,1,0.3,1); }

        /* ─── Nav link ─── */
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: #006c49; transition: width 0.3s; border-radius: 1px; }
        .nav-link.active::after { width: 100%; }
        .nav-link.active { color: #006c49; }

        /* ─── Pulse ring ─── */
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.6; }
            70% { transform: scale(1.25); opacity: 0; }
            100% { transform: scale(0.95); opacity: 0; }
        }
        .pulse-ring { position: absolute; inset: -6px; border-radius: 50%; border: 2px solid currentColor; animation: pulse-ring 2.4s ease-out infinite; }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,108,73,0.25); border-radius: 3px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; transition-duration: 0.001ms !important; }
        }
    </style>
</head>
<body class="bg-background text-on-surface overflow-x-hidden selection:bg-primary/10 selection:text-primary">

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav id="navbar" class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-20 bg-white/75 backdrop-blur-xl border-b border-outline-variant/20 shadow-sm transition-all duration-300">
    <a href="#hero" class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-tr from-primary to-secondary rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-md">T</div>
        <div class="flex flex-col leading-none">
            <span class="font-extrabold tracking-wider text-xl text-on-surface">TREVORA</span>
            <span class="text-[10px] font-semibold tracking-wider text-on-surface-variant uppercase mt-0.5 hidden sm:block">Supply Chain Financing</span>
        </div>
    </a>
    <div class="hidden md:flex gap-7 items-center">
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#problem">Masalah</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#how-it-works">Cara Kerja</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#features">Fitur</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#roles">Ekosistem</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#command-center">Dashboard</a>
    </div>
    <div class="flex gap-3 items-center">
        <a href="{{ route('register') }}" class="hidden sm:inline-block px-5 py-2 border border-outline-variant/60 rounded-full font-semibold text-sm hover:bg-surface-container-low transition-all text-on-surface">Daftar</a>
        <a href="{{ route('login') }}" class="btn-primary !py-2.5 !px-5 !text-sm">Masuk Dashboard</a>
    </div>
</nav>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<section id="hero" class="relative min-h-screen flex items-center pt-28 pb-20 px-margin-mobile md:px-margin-desktop overflow-hidden">
    <div class="blob1" style="top:-100px; left:-200px;"></div>
    <div class="blob2" style="bottom:-200px; right:-200px;"></div>
    <div class="absolute inset-0 pointer-events-none opacity-[0.025]" style="background-image: linear-gradient(#006c49 1px,transparent 1px),linear-gradient(90deg,#006c49 1px,transparent 1px); background-size: 40px 40px;"></div>

    <div class="relative z-10 w-full max-w-[1440px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
        <!-- Left copy -->
        <div class="lg:col-span-6 flex flex-col gap-7">
            <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-full bg-white/90 border border-secondary/20 w-fit shadow-sm reveal active">
                <span class="glow-dot"></span>
                <span class="font-bold text-xs tracking-widest text-secondary uppercase">Ekosistem Supply Chain Financing</span>
            </div>

            <h1 class="text-[40px] md:text-[52px] lg:text-[60px] font-extrabold tracking-tight leading-[1.04] text-on-surface reveal active delay-100">
                Modal Mengalir.<br/>
                <span class="text-gradient">Rantai Pasok Bekerja.</span><br/>
                Setiap Aktor Untung.
            </h1>

            <p class="text-base md:text-lg text-on-surface-variant leading-relaxed max-w-xl reveal active delay-200">
                <strong class="text-on-surface font-semibold">TREVORA</strong> menyatukan <strong class="text-primary">kantin</strong>, <strong class="text-secondary">pemasok</strong>, dan <strong class="text-primary">LKBB (Lembaga Keuangan Bukan Bank)</strong> dalam satu ekosistem pembiayaan rantai pasok. Kantin beroperasi tanpa modal pribadi, pemasok dijamin pembayarannya, mahasiswa makan pakai saldo beasiswa — semua tercatat real-time.
            </p>

            <!-- Live ticker -->
            <div class="flex items-center gap-3 reveal active delay-300">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Aktivitas Ekosistem:</span>
                <div class="px-3 py-1.5 rounded-lg bg-primary/8 border border-primary/15 overflow-hidden h-7 flex items-center" style="min-width:260px;">
                    <span class="ticker-item text-xs font-mono font-bold text-primary" id="ticker-text">PO-20260524-A831 didanai LKBB — Rp 18.450.000</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 mt-1 reveal active delay-400">
                <a href="{{ route('register') }}" class="btn-primary">Mulai Bergabung Gratis</a>
                <a href="#how-it-works" class="btn-outline">
                    <span class="material-symbols-outlined mat-outline text-[20px] text-secondary">play_circle</span>
                    Lihat Cara Kerja
                </a>
            </div>

            <!-- Trust row -->
            <div class="flex flex-wrap items-center gap-6 pt-4 border-t border-outline-variant/30 reveal active delay-500">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">account_balance</span>
                    <span class="text-xs text-on-surface-variant">Pembiayaan LKBB</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">lock</span>
                    <span class="text-xs text-on-surface-variant">Buku Besar Double-Entry</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">qr_code_2</span>
                    <span class="text-xs text-on-surface-variant">POS &amp; QR Mahasiswa</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">sync_lock</span>
                    <span class="text-xs text-on-surface-variant">Approval Atomik &amp; Idempotent</span>
                </div>
            </div>
        </div>

        <!-- Right mockup -->
        <div class="lg:col-span-6 relative h-[560px] w-full hidden lg:block perspective-container reveal active delay-200" id="parallax-container">
            <div class="absolute inset-0 animate-float" id="parallax-element" style="transform: rotateY(-12deg) rotateX(8deg);">
                <!-- Main panel: LKBB dashboard preview -->
                <div class="absolute inset-0 bg-white/90 backdrop-blur-2xl rounded-2xl p-5 shadow-2xl border border-white/80 overflow-hidden">
                    <!-- Title bar -->
                    <div class="flex justify-between items-center mb-5 border-b border-outline-variant/30 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                            <span class="w-3 h-3 rounded-full bg-green-400"></span>
                            <span class="text-xs text-on-surface-variant font-medium ml-2">trevora.id/lkbb/dashboard</span>
                        </div>
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/8 border border-primary/15 text-xs font-bold font-mono text-primary">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-ping inline-block"></span>
                            LIVE
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 h-[calc(100%-60px)]">
                        <div class="col-span-2 flex flex-col gap-3">
                            <!-- Chart card -->
                            <div class="p-4 rounded-xl bg-white border border-outline-variant/30 shadow-sm">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <div class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider">GMV Perputaran Ekosistem</div>
                                        <div class="text-2xl font-bold text-on-surface mt-0.5">Rp 824.500.000</div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">↑ 12.4%</span>
                                </div>
                                <div class="h-[72px] w-full mt-2">
                                    <svg class="w-full h-full" viewBox="0 0 300 60" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="lineGrad" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#006c49" stop-opacity="0.18"/>
                                                <stop offset="100%" stop-color="#006c49" stop-opacity="0"/>
                                            </linearGradient>
                                        </defs>
                                        <path d="M0,48 Q30,38 60,42 T120,22 T180,30 T240,14 T300,8" fill="none" stroke="#006c49" stroke-width="2.5" stroke-linecap="round"/>
                                        <path d="M0,60 L0,48 Q30,38 60,42 T120,22 T180,30 T240,14 T300,8 L300,60 Z" fill="url(#lineGrad)"/>
                                        <circle cx="60" cy="42" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="120" cy="22" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="180" cy="30" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="240" cy="14" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="300" cy="8" r="4" fill="#006c49"/>
                                    </svg>
                                </div>
                            </div>
                            <!-- Approval queue -->
                            <div class="p-4 rounded-xl bg-white border border-outline-variant/30 shadow-sm flex-1 overflow-hidden">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Antrean Approval PO &amp; Beasiswa</span>
                                    <span class="text-[10px] text-secondary font-semibold">3 menunggu</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between p-2.5 rounded-lg bg-surface items-center border border-outline-variant/20">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-primary">inventory_2</span>
                                            <span class="text-[11px] font-mono font-medium">PO-20260524-A831</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary text-[10px] font-bold">DIDANAI</span>
                                    </div>
                                    <div class="flex justify-between p-2.5 rounded-lg bg-surface items-center border border-outline-variant/20">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-secondary">school</span>
                                            <span class="text-[11px] font-mono font-medium">BNT-MHS-0029</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">REVIEW</span>
                                    </div>
                                    <div class="flex justify-between p-2.5 rounded-lg bg-surface items-center border border-outline-variant/20">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-outline">pending</span>
                                            <span class="text-[11px] font-mono font-medium">WD-MRC-0418</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-surface-container text-on-surface-variant text-[10px] font-semibold">ANTREAN</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right col: 3 brankas -->
                        <div class="col-span-1 flex flex-col gap-2.5">
                            <div class="p-3 rounded-xl bg-white border border-outline-variant/30 shadow-sm">
                                <div class="text-[9px] uppercase tracking-wider text-on-surface-variant mb-1 font-bold">Brankas Investasi</div>
                                <div class="text-[20px] font-extrabold text-primary leading-tight">Rp 2.1M</div>
                                <div class="text-[10px] text-on-surface-variant">untuk pendanaan PO</div>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-outline-variant/30 shadow-sm">
                                <div class="text-[9px] uppercase tracking-wider text-on-surface-variant mb-1 font-bold">Brankas Donasi</div>
                                <div class="text-[20px] font-extrabold text-secondary leading-tight">Rp 480jt</div>
                                <div class="text-[10px] text-on-surface-variant">untuk beasiswa</div>
                            </div>
                            <div class="p-3 rounded-xl bg-gradient-to-br from-[#2b322d] to-[#1a2120] text-white shadow-sm flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] uppercase text-white/50 tracking-widest font-bold block mb-1">Brankas Operasional</span>
                                    <div class="text-[20px] font-extrabold text-primary-fixed-dim leading-tight">Rp 312jt</div>
                                    <p class="text-[10px] text-white/60 leading-snug mt-1">HPP &amp; profit kembali dari penjualan</p>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] text-primary-fixed-dim bg-white/10 p-1.5 rounded-lg mt-2">
                                    <span class="material-symbols-outlined text-[14px] animate-spin">sync</span>
                                    <span>Settlement otomatis</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating badge 1: Merchant -->
                <div class="absolute -top-8 -left-8 glass-card p-3 rounded-xl w-60 shadow-xl z-20 parallax-layer" data-speed="25">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-primary bg-primary/10 p-2 rounded-xl text-[18px]">storefront</span>
                        <div>
                            <div class="font-bold text-xs text-on-surface">Kantin Sukma Sari</div>
                            <div class="text-[10px] text-on-surface-variant">PO bahan baku terkirim</div>
                        </div>
                    </div>
                </div>

                <!-- Floating badge 2: Mahasiswa QR -->
                <div class="absolute -bottom-6 right-8 glass-card p-3 rounded-xl w-60 shadow-xl z-20 parallax-layer" data-speed="-15">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary bg-secondary/10 p-2 rounded-xl text-[18px]">qr_code_2</span>
                        <div>
                            <div class="font-bold text-xs text-on-surface">QR Mahasiswa Bayar</div>
                            <div class="text-[10px] text-on-surface-variant">Rp 18.500 — saldo beasiswa</div>
                        </div>
                    </div>
                </div>

                <!-- Floating badge 3: Pemasok -->
                <div class="absolute top-1/2 -right-10 glass-card p-3 rounded-xl w-56 shadow-xl z-20 parallax-layer" data-speed="20">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-emerald-600 bg-emerald-50 p-2 rounded-xl text-[18px]">local_shipping</span>
                        <div>
                            <div class="font-bold text-xs text-on-surface">Pemasok Tani Jaya</div>
                            <div class="text-[10px] text-on-surface-variant">Surat jalan dicetak</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     MARQUEE (Aktor Ekosistem)
══════════════════════════════════════ -->
<div class="relative border-y border-outline-variant/20 bg-white/60 backdrop-blur-sm py-5 overflow-hidden">
    <div class="absolute left-0 top-0 h-full w-28 bg-gradient-to-r from-background to-transparent z-10 pointer-events-none"></div>
    <div class="absolute right-0 top-0 h-full w-28 bg-gradient-to-l from-background to-transparent z-10 pointer-events-none"></div>
    <p class="text-center text-[11px] font-bold text-on-surface-variant uppercase tracking-widest mb-4">Mendukung seluruh aktor ekosistem kantin kampus</p>
    <div class="flex overflow-hidden">
        <div class="animate-marquee flex whitespace-nowrap gap-16 px-8 items-center text-sm font-semibold tracking-tight text-on-surface-variant/55">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">storefront</span>Kantin / Merchant</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">local_shipping</span>Pemasok Bahan Baku</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">account_balance</span>LKBB Treasury</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">school</span>Mahasiswa Penerima Beasiswa</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">volunteer_activism</span>Donatur</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">trending_up</span>Investor</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>Admin Yayasan</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">storefront</span>Kantin / Merchant</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">local_shipping</span>Pemasok Bahan Baku</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">account_balance</span>LKBB Treasury</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">school</span>Mahasiswa Penerima Beasiswa</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">volunteer_activism</span>Donatur</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">trending_up</span>Investor</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>Admin Yayasan</span>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     PROBLEM STATEMENT
══════════════════════════════════════ -->
<section id="problem" class="py-24 px-margin-mobile md:px-margin-desktop bg-background overflow-hidden relative">
    <div class="blob-sm" style="top:100px; right:-100px;"></div>
    <div class="max-w-[1440px] mx-auto relative z-10">
        <div class="grid lg:grid-cols-2 gap-16 lg:gap-20 items-center">
            <div class="reveal-left">
                <span class="text-error font-bold text-xs tracking-widest uppercase bg-error/8 border border-error/15 px-3 py-1 rounded-full">Realita Lapangan</span>
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-5 leading-tight">
                    Kantin Punya Pembeli,<br/>
                    Tapi <span class="text-gradient">Kehabisan Modal.</span><br/>
                    Pemasok Ragu Mengirim.
                </h2>
                <p class="text-on-surface-variant text-base mt-4 leading-relaxed max-w-lg">
                    Ekosistem kantin kampus jalan di tempat karena setiap aktor menanggung risikonya masing-masing. Tidak ada pihak yang menjamin uang, barang, dan arus informasi. Hasilnya: pertumbuhan macet.
                </p>
                <div class="flex flex-col gap-3 mt-8 max-w-lg">
                    <div class="problem-card">
                        <span class="material-symbols-outlined text-error text-[22px] mt-0.5 shrink-0">payments</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">Kantin kehabisan modal saat pemasok minta dibayar</div>
                            <div class="text-xs text-on-surface-variant mt-1">Cashflow tersendat. Stok kosong. Pembeli kabur. Pendapatan jadi hilang.</div>
                        </div>
                    </div>
                    <div class="problem-card">
                        <span class="material-symbols-outlined text-error text-[22px] mt-0.5 shrink-0">warning</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">Pemasok takut kirim — tidak ada jaminan pembayaran</div>
                            <div class="text-xs text-on-surface-variant mt-1">Lebih nyaman jual ke tengkulak meski harga lebih rendah, asal pasti dibayar.</div>
                        </div>
                    </div>
                    <div class="problem-card">
                        <span class="material-symbols-outlined text-error text-[22px] mt-0.5 shrink-0">visibility_off</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">LKBB &amp; yayasan buta arah dana mengalir</div>
                            <div class="text-xs text-on-surface-variant mt-1">Tidak tahu posisi modal, status pengiriman, atau realisasi bantuan beasiswa.</div>
                        </div>
                    </div>
                    <div class="problem-card">
                        <span class="material-symbols-outlined text-error text-[22px] mt-0.5 shrink-0">edit_note</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">Pencatatan masih manual — spreadsheet, nota, WhatsApp</div>
                            <div class="text-xs text-on-surface-variant mt-1">Tidak ada audit trail. Sulit rekonsiliasi. Mudah selisih, mudah hilang.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reveal-right">
                <div class="relative">
                    <div class="absolute -top-6 left-1/2 -translate-x-1/2 z-10 flex items-center justify-center w-14 h-14 rounded-full bg-primary text-white shadow-xl">
                        <span class="material-symbols-outlined text-[28px]">south</span>
                    </div>
                    <div class="glass-card rounded-2xl p-7 mt-8 border-primary/20">
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold mb-3">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Solusi TREVORA
                            </div>
                            <h3 class="font-extrabold text-2xl text-on-surface">Satu Ekosistem,<br/>Semua Risiko Tertutup.</h3>
                            <p class="text-xs text-on-surface-variant mt-2 max-w-xs mx-auto">LKBB menjamin pembayaran ke pemasok. Kantin operasi tanpa modal. Mahasiswa bayar dengan saldo beasiswa. Semua tercatat.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-primary">0</div>
                                <div class="text-[11px] text-on-surface-variant mt-1 font-medium">Modal Awal Kantin</div>
                            </div>
                            <div class="p-4 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-secondary">100%</div>
                                <div class="text-[11px] text-on-surface-variant mt-1 font-medium">Pembayaran Pemasok Terjamin</div>
                            </div>
                            <div class="p-4 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-primary">3</div>
                                <div class="text-[11px] text-on-surface-variant mt-1 font-medium">Brankas Treasury LKBB</div>
                            </div>
                            <div class="p-4 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-secondary">Real-time</div>
                                <div class="text-[11px] text-on-surface-variant mt-1 font-medium">Audit Trail &amp; Ledger</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     STATS / TRUST
══════════════════════════════════════ -->
<section class="py-16 px-margin-mobile md:px-margin-desktop bg-gradient-to-br from-inverse-surface to-[#1a2120] reveal relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none opacity-[0.06]" style="background-image: linear-gradient(#4edea3 1px,transparent 1px),linear-gradient(90deg,#4edea3 1px,transparent 1px); background-size: 50px 50px;"></div>
    <div class="max-w-[1440px] mx-auto relative z-10">
        <div class="text-center mb-12">
            <span class="text-primary-fixed-dim font-bold text-xs tracking-widest uppercase">Dirancang untuk Skala</span>
            <h3 class="text-2xl md:text-3xl font-extrabold text-white mt-2">Satu Platform — Empat Aktor — Nol Spreadsheet</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center p-4">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim">4</div>
                <div class="text-sm text-surface-variant/70 mt-2">Peran Operasional Aktif<br/><span class="text-xs opacity-60">Merchant · Pemasok · LKBB · Mahasiswa</span></div>
            </div>
            <div class="text-center p-4">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim">3</div>
                <div class="text-sm text-surface-variant/70 mt-2">Brankas Treasury LKBB<br/><span class="text-xs opacity-60">Investasi · Donasi · Operasional</span></div>
            </div>
            <div class="text-center p-4">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim">100%</div>
                <div class="text-sm text-surface-variant/70 mt-2">Mutasi Tercatat Double-Entry<br/><span class="text-xs opacity-60">Setiap rupiah ada DEBIT &amp; CREDIT</span></div>
            </div>
            <div class="text-center p-4">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim">ACID</div>
                <div class="text-sm text-surface-variant/70 mt-2">Transaksi Atomik &amp; Aman<br/><span class="text-xs opacity-60">Locking + idempotent guard</span></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════ -->
<section id="how-it-works" class="py-24 px-margin-mobile md:px-margin-desktop bg-background relative overflow-hidden">
    <div class="blob-sm" style="bottom:-50px; left:-100px;"></div>
    <div class="max-w-[1440px] mx-auto relative z-10">
        <div class="grid lg:grid-cols-2 gap-16 lg:gap-20 items-start">
            <div class="reveal-left lg:sticky lg:top-28">
                <span class="text-secondary font-bold text-xs tracking-widest uppercase bg-secondary/10 px-3 py-1.5 rounded-full">Alur Bisnis Inti</span>
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 mb-6 leading-tight">
                    Dari Pesanan Bahan<br/>ke <span class="text-gradient">Settlement Otomatis</span><br/>dalam 6 Langkah.
                </h2>
                <p class="text-on-surface-variant text-base leading-relaxed max-w-md mb-8">
                    Setiap langkah memicu transaksi yang tercatat di buku besar TREVORA — tidak ada langkah yang bisa dilompati, tidak ada langkah yang dicatat dua kali.
                </p>

                <!-- Mini ecosystem flow visualization -->
                <div class="bg-white border border-outline-variant/40 rounded-2xl p-5 shadow-sm">
                    <div class="text-[11px] font-bold text-on-surface-variant uppercase tracking-widest mb-4 text-center">Visualisasi Aliran Ekosistem</div>
                    <div class="grid grid-cols-3 gap-2 items-center">
                        <div class="flow-node text-center">
                            <span class="material-symbols-outlined text-primary text-[22px] mat-outline block mx-auto">storefront</span>
                            <div class="text-[10px] font-bold mt-1">Merchant</div>
                        </div>
                        <div class="flex justify-center"><span class="material-symbols-outlined text-on-surface-variant/50 text-[18px]">arrow_forward</span></div>
                        <div class="flow-node text-center" style="border-color: rgba(107,56,212,0.3);">
                            <span class="material-symbols-outlined text-secondary text-[22px] mat-outline block mx-auto">local_shipping</span>
                            <div class="text-[10px] font-bold mt-1">Pemasok</div>
                        </div>
                    </div>
                    <div class="flex justify-center my-2"><span class="material-symbols-outlined text-on-surface-variant/40 text-[18px]">south</span></div>
                    <div class="flow-node text-center max-w-[140px] mx-auto" style="border-color: rgba(0,108,73,0.4); background: linear-gradient(135deg, rgba(0,108,73,0.05), rgba(107,56,212,0.05));">
                        <span class="material-symbols-outlined text-primary text-[24px] block mx-auto">account_balance</span>
                        <div class="text-[10px] font-bold mt-1">LKBB Treasury</div>
                    </div>
                    <div class="flex justify-center my-2"><span class="material-symbols-outlined text-on-surface-variant/40 text-[18px]">south</span></div>
                    <div class="grid grid-cols-3 gap-2 items-center">
                        <div class="flow-node text-center">
                            <span class="material-symbols-outlined text-emerald-600 text-[22px] mat-outline block mx-auto">qr_code_2</span>
                            <div class="text-[10px] font-bold mt-1">POS</div>
                        </div>
                        <div class="flex justify-center"><span class="material-symbols-outlined text-on-surface-variant/50 text-[18px]">arrow_forward</span></div>
                        <div class="flow-node text-center" style="border-color: rgba(107,56,212,0.3);">
                            <span class="material-symbols-outlined text-secondary text-[22px] mat-outline block mx-auto">school</span>
                            <div class="text-[10px] font-bold mt-1">Mahasiswa</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Steps timeline -->
            <div class="reveal-right">
                <div class="flex flex-col gap-0">
                    <div class="flex gap-5 relative pb-8">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">1</span>
                        </div>
                        <div class="pt-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-primary/8 text-primary text-[10px] font-bold uppercase tracking-wider">Merchant</span>
                            </div>
                            <h4 class="font-bold text-base text-on-surface">Kantin mengajukan PO ke pemasok</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Lewat dashboard merchant, kantin memilih produk dari katalog pemasok. Sistem otomatis menghasilkan <code class="text-primary text-xs font-mono bg-primary/8 px-1.5 py-0.5 rounded">PO-YYYYMMDD-xxxxx</code> per pemasok, dengan total estimasi & tanggal kebutuhan.</p>
                        </div>
                    </div>
                    <div class="flex gap-5 relative pb-8">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">2</span>
                        </div>
                        <div class="pt-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-secondary/8 text-secondary text-[10px] font-bold uppercase tracking-wider">LKBB</span>
                            </div>
                            <h4 class="font-bold text-base text-on-surface">LKBB me-review &amp; mendanai PO</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Pengajuan masuk antrean LKBB. Saat disetujui, sistem otomatis mengunci <strong class="text-on-surface">Brankas Investasi</strong>, mendebet sesuai total estimasi, mencatat <code class="text-primary text-xs font-mono bg-primary/8 px-1.5 py-0.5 rounded">PEMBIAYAAN_PO</code>, dan menggeser status PO ke <em>diproses_pemasok</em>. <strong>Atomik &amp; idempotent</strong> — tidak bisa double-cairkan.</p>
                        </div>
                    </div>
                    <div class="flex gap-5 relative pb-8">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-secondary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">3</span>
                        </div>
                        <div class="pt-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-secondary/8 text-secondary text-[10px] font-bold uppercase tracking-wider">Pemasok</span>
                            </div>
                            <h4 class="font-bold text-base text-on-surface">Pemasok produksi &amp; mengirim barang</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Pemasok mencatat batch produksi, mengisi kurir &amp; nomor resi, mencetak surat jalan. Status PO berubah <em>dikirim</em>; setiap update masuk <strong>tracking_history</strong> JSON yang bisa dilacak realtime.</p>
                        </div>
                    </div>
                    <div class="flex gap-5 relative pb-8">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">4</span>
                        </div>
                        <div class="pt-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-primary/8 text-primary text-[10px] font-bold uppercase tracking-wider">Merchant</span>
                            </div>
                            <h4 class="font-bold text-base text-on-surface">Merchant menerima &amp; jadikan menu POS</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Setelah konfirmasi penerimaan (status <em>selesai</em>), kantin tinggal klik <em>Jadikan Menu</em> — bahan baku otomatis terbentuk jadi menu dengan <strong>harga pokok</strong> &amp; <strong>harga jual</strong> yang siap dijual via POS.</p>
                        </div>
                    </div>
                    <div class="flex gap-5 relative pb-8">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-secondary to-[#3d1a8c] flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">5</span>
                        </div>
                        <div class="pt-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">Mahasiswa</span>
                            </div>
                            <h4 class="font-bold text-base text-on-surface">Mahasiswa bayar pakai QR — saldo beasiswa</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Kasir membuat QR di POS web; mahasiswa scan via aplikasi Flutter. Saldo beasiswa dipotong, transaksi <code class="text-primary text-xs font-mono bg-primary/8 px-1.5 py-0.5 rounded">pembayaran_makanan</code> langsung <em>sukses</em> — tidak ada uang fisik berpindah tangan.</p>
                        </div>
                    </div>
                    <div class="flex gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-600 flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">6</span>
                        </div>
                        <div class="pt-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">Sistem</span>
                            </div>
                            <h4 class="font-bold text-base text-on-surface">Settlement &amp; bagi hasil otomatis</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Dari setiap transaksi: <strong>HPP kembali</strong> ke Brankas Operasional, <strong>fee LKBB</strong> dihitung dari profit (rumus konfigurabel), sisa <strong>laba bersih</strong> masuk wallet kantin. Semua mutasi ditulis ganda di <code class="text-primary text-xs font-mono bg-primary/8 px-1.5 py-0.5 rounded">ledger_entries</code>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FEATURES
══════════════════════════════════════ -->
<section id="features" class="py-24 px-margin-mobile md:px-margin-desktop bg-surface-container-low overflow-hidden">
    <div class="max-w-[1440px] mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="text-primary font-bold text-xs tracking-widest uppercase bg-primary/10 px-3 py-1.5 rounded-full">Fitur Utama Platform</span>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 leading-tight">
                Semua yang Dibutuhkan<br/>
                <span class="text-gradient">Ekosistem Operasional Kantin.</span>
            </h2>
            <p class="text-on-surface-variant text-base mt-3 leading-relaxed">Sembilan modul utama yang saling terhubung — bukan integrasi pihak ketiga, tapi <strong class="text-on-surface">single source of truth</strong>.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1: SCF -->
            <div class="feature-card reveal delay-100">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-primary text-[24px] mat-outline">account_balance</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Supply Chain Financing</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">LKBB membiayai seluruh PO kantin dari Brankas Investasi. Kantin tidak butuh modal pribadi (program <em>Zero Risk</em>); pemasok dijamin pembayarannya begitu PO disetujui.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-primary/8 text-primary text-[11px] font-semibold">PO → PEMBIAYAAN_PO</span>
                    <span class="px-2 py-1 rounded-md bg-primary/8 text-primary text-[11px] font-semibold">Brankas Investasi</span>
                </div>
            </div>

            <!-- Feature 2: POS + QR -->
            <div class="feature-card reveal delay-200">
                <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-secondary text-[24px] mat-outline">point_of_sale</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">POS Kantin &amp; QR Pembayaran</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Mesin kasir digital: keranjang, kalkulasi kembalian, generate QR. Mahasiswa scan via aplikasi mobile dan bayar pakai saldo beasiswa — tunai pun didukung dengan setoran ke LKBB.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-secondary/8 text-secondary text-[11px] font-semibold">Polling 2 detik</span>
                    <span class="px-2 py-1 rounded-md bg-secondary/8 text-secondary text-[11px] font-semibold">Stok auto-reserve</span>
                </div>
            </div>

            <!-- Feature 3: Wallet & Brankas -->
            <div class="feature-card reveal delay-300">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-emerald-700 text-[24px] mat-outline">savings</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Wallet &amp; Tiga Brankas Treasury</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Setiap user punya wallet. LKBB punya tiga brankas terpisah: <strong>Investasi</strong> (modal kerja), <strong>Donasi</strong> (beasiswa), <strong>Operasional</strong> (HPP &amp; fee). Saldo tidak pernah bercampur.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-emerald-100 text-emerald-700 text-[11px] font-semibold">LKBB_INVESTMENT</span>
                    <span class="px-2 py-1 rounded-md bg-emerald-100 text-emerald-700 text-[11px] font-semibold">LKBB_DONATION</span>
                    <span class="px-2 py-1 rounded-md bg-emerald-100 text-emerald-700 text-[11px] font-semibold">LKBB_OPERATIONAL</span>
                </div>
            </div>

            <!-- Feature 4: Bagi Hasil -->
            <div class="feature-card reveal delay-200">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-amber-700 text-[24px] mat-outline">handshake</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Bagi Hasil Otomatis (Profit Sharing)</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Setiap menu punya harga pokok &amp; harga jual. Saat transaksi terjadi, sistem otomatis menghitung <code class="text-amber-700 text-[10px] font-mono bg-amber-50 px-1 py-0.5 rounded">feeLKBB = (profit × %)</code>, lalu pisahkan hak kantin &amp; hak LKBB di waktu yang sama.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-amber-100 text-amber-700 text-[11px] font-semibold">Persentase konfigurabel</span>
                    <span class="px-2 py-1 rounded-md bg-amber-100 text-amber-700 text-[11px] font-semibold">Per merchant</span>
                </div>
            </div>

            <!-- Feature 5: Logistik -->
            <div class="feature-card reveal delay-300">
                <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-sky-700 text-[24px] mat-outline">local_shipping</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Logistik &amp; Pengiriman Realtime</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Pemasok mencatat kurir, nomor resi, dan jadwal pengiriman langsung dari dashboard. Status PO bergerak otomatis lewat state machine: <em>menunggu_lkbb → diproses → dikirim → selesai</em>.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-sky-100 text-sky-700 text-[11px] font-semibold">Tracking JSON</span>
                    <span class="px-2 py-1 rounded-md bg-sky-100 text-sky-700 text-[11px] font-semibold">Surat jalan digital</span>
                </div>
            </div>

            <!-- Feature 6: Onboarding KYC -->
            <div class="feature-card reveal delay-400">
                <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-violet-700 text-[24px] mat-outline">badge</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Verifikasi Onboarding Bertingkat</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Gerbang onboarding 4-fase untuk merchant: <em>belum_melengkapi → menunggu_review → disetujui/ditolak</em>. Admin menetapkan persentase bagi hasil per merchant saat approve.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-violet-100 text-violet-700 text-[11px] font-semibold">Upload KTP</span>
                    <span class="px-2 py-1 rounded-md bg-violet-100 text-violet-700 text-[11px] font-semibold">Foto kantin</span>
                </div>
            </div>

            <!-- Feature 7: Penyaluran Bantuan -->
            <div class="feature-card reveal delay-200">
                <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-rose-700 text-[24px] mat-outline">volunteer_activism</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Distribusi Beasiswa &amp; Bantuan</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Admin mengajukan bantuan untuk mahasiswa; LKBB mereview &amp; mencairkan dari Brankas Donasi langsung ke saldo mahasiswa. Tidak ada uang tunai bisa diselewengkan.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-rose-100 text-rose-700 text-[11px] font-semibold">Brankas Donasi</span>
                    <span class="px-2 py-1 rounded-md bg-rose-100 text-rose-700 text-[11px] font-semibold">Saldo mahasiswa</span>
                </div>
            </div>

            <!-- Feature 8: Penarikan / Setoran -->
            <div class="feature-card reveal delay-300">
                <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-teal-700 text-[24px] mat-outline">currency_exchange</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Penarikan Dana &amp; Setoran Tunai</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Merchant &amp; pemasok bisa menarik saldo digital ke rekening bank lewat approval LKBB. Kantin yang menerima tunai mencatat <em>tagihan setoran</em> dan menjadwalkan setoran fisik ke LKBB.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-teal-100 text-teal-700 text-[11px] font-semibold">Withdrawal</span>
                    <span class="px-2 py-1 rounded-md bg-teal-100 text-teal-700 text-[11px] font-semibold">Setoran Tunai</span>
                </div>
            </div>

            <!-- Feature 9: Audit & Governance -->
            <div class="feature-card reveal delay-400">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-indigo-700 text-[24px] mat-outline">policy</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Governance &amp; Audit Trail</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Setiap mutasi uang ditulis ke <code class="text-indigo-700 text-[10px] font-mono bg-indigo-50 px-1 py-0.5 rounded">ledger_entries</code> dengan saldo-setelah. Login di-log otomatis. Approval bersifat <strong>idempotent</strong> — klik 2× tetap aman.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-indigo-100 text-indigo-700 text-[11px] font-semibold">Double-entry</span>
                    <span class="px-2 py-1 rounded-md bg-indigo-100 text-indigo-700 text-[11px] font-semibold">Login log</span>
                    <span class="px-2 py-1 rounded-md bg-indigo-100 text-indigo-700 text-[11px] font-semibold">ACID</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     ROLE ECOSYSTEM
══════════════════════════════════════ -->
<section id="roles" class="py-24 px-margin-mobile md:px-margin-desktop bg-background relative overflow-hidden">
    <div class="blob-sm" style="top:50%; right:-150px;"></div>
    <div class="max-w-[1440px] mx-auto relative z-10">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="text-secondary font-bold text-xs tracking-widest uppercase bg-secondary/10 px-3 py-1.5 rounded-full">Ekosistem 4 Aktor</span>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 leading-tight">
                Empat Peran,<br/>
                <span class="text-gradient">Satu Tujuan Bersama.</span>
            </h2>
            <p class="text-on-surface-variant text-base mt-3 leading-relaxed">TREVORA dirancang sebagai <em>positive-sum game</em> — setiap aktor punya alasan kuat untuk bergabung dan tetap aktif.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Merchant -->
            <div class="role-card reveal delay-100" style="--accent: rgba(0,108,73,0.10);">
                <div class="flex items-center justify-between mb-5">
                    <div class="w-12 h-12 rounded-xl bg-primary/12 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[26px]">storefront</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20 uppercase tracking-wider">Operator</span>
                </div>
                <h3 class="font-extrabold text-lg text-on-surface">Merchant / Kantin</h3>
                <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">Pemilik kantin kampus. Fokus jualan, tidak pusing soal modal.</p>
                <div class="border-t border-outline-variant/30 my-5"></div>
                <div class="flex flex-col gap-2.5 text-sm">
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Pesan bahan baku tanpa modal awal</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">POS digital + QR pembayaran mahasiswa</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Pesanan online &amp; kelola katalog menu</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Tarik laba bersih ke rekening pribadi</span></div>
                </div>
            </div>

            <!-- Pemasok -->
            <div class="role-card reveal delay-200" style="--accent: rgba(107,56,212,0.10);">
                <div class="flex items-center justify-between mb-5">
                    <div class="w-12 h-12 rounded-xl bg-secondary/12 flex items-center justify-center">
                        <span class="material-symbols-outlined text-secondary text-[26px]">local_shipping</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-secondary/10 text-secondary border border-secondary/20 uppercase tracking-wider">Producer</span>
                </div>
                <h3 class="font-extrabold text-lg text-on-surface">Pemasok / Supplier</h3>
                <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">Penyedia bahan baku. Pembayaran dijamin LKBB, bukan kantin.</p>
                <div class="border-t border-outline-variant/30 my-5"></div>
                <div class="flex flex-col gap-2.5 text-sm">
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Pendapatan terjamin — tidak takut kantin telat bayar</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Inbox PO &amp; kalender produksi terjadwal</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Atur pengiriman, kurir, surat jalan digital</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-secondary text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Stok opname &amp; riwayat batch produksi</span></div>
                </div>
            </div>

            <!-- LKBB -->
            <div class="role-card reveal delay-300" style="--accent: rgba(16,185,129,0.10);">
                <div class="flex items-center justify-between mb-5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-emerald-700 text-[26px]">account_balance</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 uppercase tracking-wider">Treasury</span>
                </div>
                <h3 class="font-extrabold text-lg text-on-surface">LKBB</h3>
                <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">Lembaga Keuangan Bukan Bank. Bank pusat ekosistem — mendanai &amp; mengawasi.</p>
                <div class="border-t border-outline-variant/30 my-5"></div>
                <div class="flex flex-col gap-2.5 text-sm">
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Kelola 3 Brankas (Investasi, Donasi, Operasional)</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Approve PO &amp; cairkan beasiswa secara atomik</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Dapat fee bagi hasil tiap transaksi</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Monitoring GMV, laba bulanan, anomali</span></div>
                </div>
            </div>

            <!-- Mahasiswa -->
            <div class="role-card reveal delay-400" style="--accent: rgba(186,26,26,0.06);">
                <div class="flex items-center justify-between mb-5">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-rose-700 text-[26px]">school</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200 uppercase tracking-wider">Consumer</span>
                </div>
                <h3 class="font-extrabold text-lg text-on-surface">Mahasiswa</h3>
                <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">Penerima beasiswa. Makan di kantin pakai saldo digital, bukan tunai pribadi.</p>
                <div class="border-t border-outline-variant/30 my-5"></div>
                <div class="flex flex-col gap-2.5 text-sm">
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-rose-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Aplikasi mobile Flutter — login Sanctum</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-rose-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Scan QR di kantin — bayar saldo beasiswa</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-rose-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Riwayat transaksi &amp; ajukan bantuan baru</span></div>
                    <div class="flex items-start gap-2"><span class="material-symbols-outlined text-rose-700 text-[16px] mt-0.5">check</span><span class="text-on-surface-variant">Bantuan tepat sasaran, teraudit penuh</span></div>
                </div>
            </div>
        </div>

        <!-- Donatur & Investor row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="role-card reveal delay-200" style="--accent: rgba(245,158,11,0.08);">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-amber-700 text-[26px]">volunteer_activism</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-extrabold text-base text-on-surface">Donatur</h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700 border border-amber-200 uppercase tracking-wider">Pendukung Sosial</span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">Menyumbang ke Brankas Donasi. Setiap rupiah disalurkan ke mahasiswa terverifikasi, tercatat audit-grade — bisa dibuktikan tepat sasaran.</p>
                    </div>
                </div>
            </div>
            <div class="role-card reveal delay-300" style="--accent: rgba(14,165,233,0.08);">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-sky-700 text-[26px]">trending_up</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-extrabold text-base text-on-surface">Investor</h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-100 text-sky-700 border border-sky-200 uppercase tracking-wider">Pemodal</span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">Menyuntik modal kerja ke Brankas Investasi. Mendapat imbal hasil dari fee LKBB yang terkumpul dari volume transaksi ekosistem.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     COMMAND CENTER (Dashboard Preview)
══════════════════════════════════════ -->
<section id="command-center" class="py-24 bg-surface-container-low border-y border-outline-variant/30 reveal">
    <div class="max-w-[1440px] mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-primary font-bold text-xs tracking-widest uppercase bg-primary/10 px-3 py-1.5 rounded-full">Dashboard Per Peran</span>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 leading-tight">
                Command Center<br/><span class="text-gradient">untuk Setiap Aktor.</span>
            </h2>
            <p class="text-on-surface-variant text-base mt-3">Tiap peran punya panel khusus — admin, LKBB, merchant, pemasok — dengan layout sidebar warna identitas dan komponen yang sesuai tugasnya.</p>
        </div>

        <div class="flex gap-2 justify-center mb-8 flex-wrap">
            <button class="tab-btn active" onclick="switchTab('lkbb')">Dashboard LKBB</button>
            <button class="tab-btn" onclick="switchTab('merchant')">Dashboard Merchant</button>
            <button class="tab-btn" onclick="switchTab('supply')">Aliran Supply Chain</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Main panel -->
            <div class="lg:col-span-8 bg-white border border-outline-variant/40 rounded-2xl shadow-sm overflow-hidden">
                <!-- LKBB Tab -->
                <div id="tab-lkbb">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-6 border-b border-outline-variant/20">
                        <div>
                            <h3 class="font-bold text-lg text-on-surface">Brankas Perputaran LKBB</h3>
                            <p class="text-xs text-on-surface-variant">Audit GMV — gabungan transaksi POS &amp; pembiayaan PO</p>
                        </div>
                        <div class="flex gap-2">
                            <button class="px-3 py-1.5 bg-surface text-on-surface-variant text-xs font-semibold rounded-lg border border-outline-variant/30 hover:bg-surface-container transition">Filter Status</button>
                            <button class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition shadow-sm">Ekspor CSV</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant/20 text-on-surface-variant font-semibold text-[12px] bg-surface/50 uppercase tracking-wider">
                                    <th class="p-4">Order ID</th>
                                    <th class="p-4">Pihak Terkait</th>
                                    <th class="p-4">Tipe</th>
                                    <th class="p-4">Nilai</th>
                                    <th class="p-4 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/20 font-mono text-[13px]">
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">PO-20260524-A831</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">Kantin Sukma Sari → Pemasok Tani Jaya</td>
                                    <td class="p-4 font-sans text-xs"><span class="px-2 py-0.5 rounded-md bg-primary/8 text-primary font-bold">PEMBIAYAAN_PO</span></td>
                                    <td class="p-4 font-semibold">Rp 18.450.000</td>
                                    <td class="p-4 text-right"><span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-sans font-bold border border-emerald-200">Didanai</span></td>
                                </tr>
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">TRX-002847</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">Mahasiswa Ridho A. → Kantin Sukma Sari</td>
                                    <td class="p-4 font-sans text-xs"><span class="px-2 py-0.5 rounded-md bg-secondary/8 text-secondary font-bold">pembayaran_makanan</span></td>
                                    <td class="p-4 font-semibold">Rp 18.500</td>
                                    <td class="p-4 text-right"><span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-sans font-bold border border-emerald-200">Sukses</span></td>
                                </tr>
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">BNT-MHS-0029</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">Brankas Donasi → Mahasiswa Lestari B.</td>
                                    <td class="p-4 font-sans text-xs"><span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 font-bold">penerimaan_bantuan</span></td>
                                    <td class="p-4 font-semibold">Rp 850.000</td>
                                    <td class="p-4 text-right"><span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-sans font-bold border border-amber-200">Review LKBB</span></td>
                                </tr>
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">WD-MRC-0418</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">Kantin Mbak Yati → Bank BCA</td>
                                    <td class="p-4 font-sans text-xs"><span class="px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 font-bold">withdrawal</span></td>
                                    <td class="p-4 font-semibold">Rp 2.400.000</td>
                                    <td class="p-4 text-right"><span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-sans font-bold border border-amber-200">Menunggu</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Merchant Tab -->
                <div id="tab-merchant" class="hidden p-6">
                    <h3 class="font-bold text-lg text-on-surface mb-1">POS Kantin Sukma Sari</h3>
                    <p class="text-xs text-on-surface-variant mb-5">Riwayat penjualan hari ini &amp; bagi hasil otomatis</p>
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="p-4 rounded-xl bg-primary/5 border border-primary/15">
                            <div class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Penjualan Hari Ini</div>
                            <div class="text-xl font-extrabold text-on-surface mt-1">Rp 1.480.000</div>
                            <div class="text-[10px] text-emerald-700 font-semibold mt-1">↑ 42 transaksi</div>
                        </div>
                        <div class="p-4 rounded-xl bg-secondary/5 border border-secondary/15">
                            <div class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Laba Bersih Kantin</div>
                            <div class="text-xl font-extrabold text-secondary mt-1">Rp 444.000</div>
                            <div class="text-[10px] text-on-surface-variant mt-1">setelah HPP + fee LKBB</div>
                        </div>
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <div class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Saldo Token</div>
                            <div class="text-xl font-extrabold text-emerald-700 mt-1">Rp 3.620.500</div>
                            <div class="text-[10px] text-on-surface-variant mt-1">siap ditarik</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 p-3 rounded-lg border border-outline-variant/30 bg-surface/50">
                            <span class="material-symbols-outlined text-primary text-[18px]">qr_code_2</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">Nasi Ayam Geprek + Es Teh</div>
                                <div class="text-[11px] text-on-surface-variant">QR · Mahasiswa Ridho A. · 14:22</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-sm text-on-surface">Rp 18.500</div>
                                <div class="text-[10px] text-primary font-semibold">+ Rp 5.500 ke kantin</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-lg border border-outline-variant/30 bg-surface/50">
                            <span class="material-symbols-outlined text-amber-600 text-[18px]">payments</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">Mie Goreng Telor</div>
                                <div class="text-[11px] text-on-surface-variant">Tunai · Pelanggan biasa · 13:55</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-sm text-on-surface">Rp 15.000</div>
                                <div class="text-[10px] text-amber-700 font-semibold">+ Rp 9.500 tagihan setoran</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supply Chain Tab -->
                <div id="tab-supply" class="hidden p-6">
                    <h3 class="font-bold text-lg text-on-surface mb-1">Status Supply Chain Aktif</h3>
                    <p class="text-xs text-on-surface-variant mb-5">PO dari kantin → pemasok, dilacak per state</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface/50">
                            <span class="material-symbols-outlined text-emerald-600 text-[20px]">inventory_2</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">PO-20260524-A831 · Beras &amp; Bumbu Dapur</div>
                                <div class="text-xs text-on-surface-variant">Kantin Sukma Sari → Pemasok Tani Jaya · ETA besok</div>
                            </div>
                            <div class="w-24">
                                <div class="h-1.5 bg-outline-variant/30 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width:80%"></div>
                                </div>
                                <div class="text-[11px] text-on-surface-variant mt-1 text-right">dikirim</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface/50">
                            <span class="material-symbols-outlined text-primary text-[20px]">factory</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">PO-20260523-Y718 · Sayur Segar</div>
                                <div class="text-xs text-on-surface-variant">Kantin Mbak Yati → Pemasok Kebun Sehat</div>
                            </div>
                            <div class="w-24">
                                <div class="h-1.5 bg-outline-variant/30 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full rounded-full" style="width:50%"></div>
                                </div>
                                <div class="text-[11px] text-on-surface-variant mt-1 text-right">diproses</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface/50">
                            <span class="material-symbols-outlined text-amber-600 text-[20px]">schedule</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">PO-20260524-C204 · Minuman Kemasan</div>
                                <div class="text-xs text-on-surface-variant">Kantin Pak Joko → Pemasok Sumber Segar</div>
                            </div>
                            <div class="w-24">
                                <div class="h-1.5 bg-outline-variant/30 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full" style="width:25%"></div>
                                </div>
                                <div class="text-[11px] text-on-surface-variant mt-1 text-right">menunggu LKBB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right sidebar cards -->
            <div class="lg:col-span-4 flex flex-col gap-5">
                <!-- Approval card -->
                <div class="bg-white border border-outline-variant/40 rounded-2xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-base text-on-surface">Approval PO Tertunda</h4>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                    </div>
                    <div class="p-4 rounded-xl bg-surface border border-outline-variant/30 mb-4">
                        <div class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">PO-20260524-C204</div>
                        <div class="font-bold text-sm text-on-surface mt-1">Pemasok Sumber Segar</div>
                        <div class="flex justify-between items-center mt-3">
                            <span class="text-xs text-on-surface-variant">Total Estimasi</span>
                            <span class="font-bold text-sm text-on-surface">Rp 6.200.000</span>
                        </div>
                        <div class="flex justify-between items-center mt-1.5">
                            <span class="text-xs text-on-surface-variant">Brankas Investasi</span>
                            <span class="font-bold text-xs text-primary">Rp 2.1M tersedia</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button class="py-2.5 rounded-xl bg-error/8 hover:bg-error/15 text-error font-semibold text-xs transition-colors border border-error/20">Tolak</button>
                        <button class="py-2.5 rounded-xl bg-primary text-white font-semibold text-xs shadow-sm hover:bg-primary/90 transition-colors">Setujui &amp; Cairkan</button>
                    </div>
                    <p class="text-[10px] text-on-surface-variant mt-3 text-center leading-relaxed">Setiap approval dieksekusi dalam <code class="font-mono bg-surface px-1 rounded text-primary">DB::transaction</code> + <code class="font-mono bg-surface px-1 rounded text-primary">lockForUpdate</code></p>
                </div>

                <!-- Ledger card -->
                <div class="bg-gradient-to-br from-secondary to-[#3d1a8c] text-white rounded-2xl shadow-sm p-5">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="font-bold text-sm uppercase tracking-wider opacity-90">Ledger Snapshot</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/15 border border-white/25">DOUBLE-ENTRY</span>
                    </div>
                    <div class="mt-3 space-y-2 text-[11px] font-mono">
                        <div class="flex justify-between p-2 rounded-lg bg-white/8 border border-white/10">
                            <div>
                                <div class="text-white/90 font-bold">LKBB_INVESTMENT</div>
                                <div class="text-white/50 text-[10px]">CREDIT · PEMBIAYAAN_PO</div>
                            </div>
                            <span class="text-rose-200">-18.450.000</span>
                        </div>
                        <div class="flex justify-between p-2 rounded-lg bg-white/8 border border-white/10">
                            <div>
                                <div class="text-white/90 font-bold">SUPPLIER_WALLET</div>
                                <div class="text-white/50 text-[10px]">DEBIT · settlement</div>
                            </div>
                            <span class="text-emerald-200">+18.450.000</span>
                        </div>
                    </div>
                    <p class="text-[11px] opacity-75 leading-relaxed mt-4">Setiap rupiah ada CREDIT &amp; DEBIT yang seimbang — saldo brankas selalu bisa di-audit sampai entry awal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FINAL CTA
══════════════════════════════════════ -->
<section class="py-24 px-margin-mobile md:px-margin-desktop bg-gradient-to-br from-[#001f13] via-[#003328] to-[#0a1a2e] relative overflow-hidden reveal">
    <div class="absolute inset-0 pointer-events-none opacity-5" style="background-image: linear-gradient(#4edea3 1px,transparent 1px),linear-gradient(90deg,#4edea3 1px,transparent 1px); background-size: 50px 50px;"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="relative z-10 text-center max-w-3xl mx-auto">
        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-white/10 border border-white/20 mb-6">
            <span class="glow-dot"></span>
            <span class="text-xs font-bold tracking-wider text-primary-fixed-dim uppercase">Siap Bergabung ke Ekosistem</span>
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-tight mb-6">
            Saatnya Kantin Berdaya,<br/>Pemasok Tenang,<br/>LKBB Berkembang.
        </h2>
        <p class="text-white/65 text-lg leading-relaxed mb-10 max-w-xl mx-auto">
            Daftarkan peran Anda di TREVORA — Merchant, Pemasok, Donatur, atau Investor — dan mulai operasi dalam satu ekosistem terpadu. Tidak perlu modal awal, tidak perlu setup rumit.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="btn-primary !bg-primary-fixed-dim !text-[#002113] !font-extrabold hover:!bg-primary-fixed text-base">
                Daftar Sebagai Peran Saya →
            </a>
            <a href="{{ route('login') }}" class="btn-outline !border-white/20 !text-white/85 !bg-white/5 hover:!bg-white/10 text-base">
                <span class="material-symbols-outlined mat-outline text-[20px]">login</span>
                Sudah punya akun? Masuk
            </a>
        </div>
        <div class="flex flex-wrap justify-center items-center gap-x-6 gap-y-2 mt-8 text-white/40 text-xs">
            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px] text-primary-fixed-dim">check_circle</span>Tanpa biaya pendaftaran</span>
            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px] text-primary-fixed-dim">check_circle</span>Setup profil 5 menit</span>
            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px] text-primary-fixed-dim">check_circle</span>Verifikasi langsung oleh tim</span>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer class="bg-inverse-surface">
    <div class="max-w-[1440px] mx-auto px-margin-mobile md:px-margin-desktop py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
        <div class="md:col-span-2">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-tr from-primary to-secondary rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-md">T</div>
                <span class="font-bold text-xl tracking-wider text-surface-bright">TREVORA</span>
            </div>
            <p class="text-sm text-surface-variant/60 leading-relaxed max-w-sm">
                Ekosistem Supply Chain Financing untuk kantin kampus. Menghubungkan merchant, pemasok, LKBB, dan mahasiswa dalam satu sistem pembiayaan &amp; operasional terintegrasi.
            </p>
            <div class="flex gap-3 mt-5">
                <div class="w-9 h-9 rounded-lg bg-white/8 flex items-center justify-center cursor-pointer hover:bg-white/15 transition border border-white/10">
                    <span class="text-white/50 text-xs font-bold">in</span>
                </div>
                <div class="w-9 h-9 rounded-lg bg-white/8 flex items-center justify-center cursor-pointer hover:bg-white/15 transition border border-white/10">
                    <span class="text-white/50 text-xs font-bold">tw</span>
                </div>
                <div class="w-9 h-9 rounded-lg bg-white/8 flex items-center justify-center cursor-pointer hover:bg-white/15 transition border border-white/10">
                    <span class="text-white/50 text-xs font-bold">ig</span>
                </div>
            </div>
        </div>
        <div>
            <h5 class="font-bold text-sm text-surface-bright mb-4 uppercase tracking-wider">Platform</h5>
            <div class="flex flex-col gap-2.5">
                <a href="#problem" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Masalah yang Diselesaikan</a>
                <a href="#how-it-works" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Cara Kerja</a>
                <a href="#features" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Fitur Utama</a>
                <a href="#roles" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Ekosistem Peran</a>
                <a href="#command-center" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Dashboard</a>
            </div>
        </div>
        <div>
            <h5 class="font-bold text-sm text-surface-bright mb-4 uppercase tracking-wider">Bergabung</h5>
            <div class="flex flex-col gap-2.5">
                <a href="{{ route('register') }}" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Daftar Merchant / Kantin</a>
                <a href="{{ route('register') }}" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Daftar Pemasok</a>
                <a href="{{ route('register') }}" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Daftar Donatur</a>
                <a href="{{ route('register') }}" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Daftar Investor</a>
                <a href="{{ route('login') }}" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Masuk Dashboard</a>
            </div>
        </div>
    </div>
    <div class="border-t border-white/8 px-margin-mobile md:px-margin-desktop py-5 flex flex-col md:flex-row justify-between items-center gap-3">
        <p class="text-xs text-surface-variant/40">© 2026 TREVORA · Supply Chain Finance Service · Dibangun oleh PT LAPI ITB</p>
        <p class="text-xs text-primary-fixed-dim/60">Untuk ekosistem kantin kampus Indonesia</p>
    </div>
</footer>

<!-- Notification container -->
<div class="fixed bottom-8 right-8 z-50 flex flex-col gap-3 pointer-events-none" id="notification-container"></div>

<!-- ══════════════════════════════════════
     SCRIPTS
══════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Scroll reveal ──
    const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    const obs = new IntersectionObserver((entries, o) => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('active'); o.unobserve(e.target); }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(el => obs.observe(el));

    // ── Parallax hero ──
    if (!reduced) {
        const cont = document.getElementById('parallax-container');
        const el = document.getElementById('parallax-element');
        const layers = document.querySelectorAll('.parallax-layer');
        if (cont && el) {
            cont.addEventListener('mousemove', (e) => {
                const r = cont.getBoundingClientRect();
                const cx = r.width / 2, cy = r.height / 2;
                const rx = ((e.clientY - r.top - cy) / cy) * -8;
                const ry = ((e.clientX - r.left - cx) / cx) * 12;
                el.style.transform = `rotateX(${8+rx}deg) rotateY(${-12+ry}deg)`;
                layers.forEach(l => {
                    const sp = parseFloat(l.getAttribute('data-speed'));
                    const ox = (e.clientX - r.left - cx) / (cx / sp) * 0.3;
                    const oy = (e.clientY - r.top - cy) / (cy / sp) * 0.3;
                    l.style.transform = `translate(${ox}px,${oy}px)`;
                });
            });
            cont.addEventListener('mouseleave', () => {
                el.style.transform = 'rotateY(-12deg) rotateX(8deg)';
                layers.forEach(l => { l.style.transform = 'translate(0,0)'; });
            });
        }
    }

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 60) navbar.classList.add('shadow-md');
        else navbar.classList.remove('shadow-md');
    });

    // ── Scrollspy ──
    const sectionIds = ['problem', 'how-it-works', 'features', 'roles', 'command-center'];
    const navLinks = document.querySelectorAll('#navbar .nav-link');
    const ssObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                navLinks.forEach(l => {
                    const targetId = l.getAttribute('href').replace('#', '');
                    l.classList.toggle('active', targetId === e.target.id);
                });
            }
        });
    }, { threshold: 0.3 });
    sectionIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) ssObs.observe(el);
    });

    // ── Tab switcher ──
    window.switchTab = function(tab) {
        ['lkbb', 'merchant', 'supply'].forEach(t => {
            const el = document.getElementById('tab-' + t);
            if (el) el.classList.toggle('hidden', t !== tab);
        });
        const labels = ['lkbb', 'merchant', 'supply'];
        document.querySelectorAll('.tab-btn').forEach((btn, i) => {
            btn.classList.toggle('active', labels[i] === tab);
        });
    };

    // ── Live ticker (real ecosystem events) ──
    const tickers = [
        'PO-20260524-A831 didanai LKBB — Rp 18.450.000',
        'Mahasiswa Ridho bayar QR — Rp 18.500 dari saldo beasiswa',
        'Bantuan beasiswa cair — Rp 850.000 ke saldo mahasiswa',
        'Pemasok Tani Jaya cetak surat jalan — kurir JNE',
        'Settlement otomatis — Rp 5.500 ke wallet kantin',
        'Brankas Operasional menerima fee LKBB — Rp 1.200',
    ];
    let tickerIdx = 0;
    const tickerEl = document.getElementById('ticker-text');
    if (tickerEl) {
        setInterval(() => {
            tickerIdx = (tickerIdx + 1) % tickers.length;
            tickerEl.classList.add('out');
            setTimeout(() => {
                tickerEl.textContent = tickers[tickerIdx];
                tickerEl.classList.remove('out');
                tickerEl.classList.add('ticker-item');
            }, 400);
        }, 3500);
    }

    // ── Live notifications (real domain events) ──
    const msgs = [
        { icon: 'inventory_2', title: 'PO Didanai LKBB', desc: 'PO-20260524-A831 cair Rp 18,4jt ke pemasok', color: 'text-primary' },
        { icon: 'qr_code_2', title: 'QR Pembayaran Sukses', desc: 'Saldo beasiswa Ridho dipotong Rp 18.500', color: 'text-secondary' },
        { icon: 'school', title: 'Beasiswa Cair', desc: 'Mahasiswa Lestari terima Rp 850.000 ke saldo', color: 'text-rose-600' },
        { icon: 'local_shipping', title: 'Surat Jalan Dicetak', desc: 'Pemasok Tani Jaya kirim ke Kantin Sukma Sari', color: 'text-sky-600' },
        { icon: 'handshake', title: 'Bagi Hasil Otomatis', desc: 'Profit dipisah ke kantin (75%) & LKBB (25%)', color: 'text-amber-600' },
        { icon: 'savings', title: 'Settlement Selesai', desc: 'HPP kembali ke Brankas Operasional', color: 'text-emerald-600' },
    ];

    function showNotif() {
        if (reduced || document.hidden) return;
        const nc = document.getElementById('notification-container');
        const m = msgs[Math.floor(Math.random() * msgs.length)];
        const n = document.createElement('div');
        n.className = 'notif glass-card p-3.5 rounded-xl shadow-xl flex items-center gap-3 w-80 pointer-events-auto bg-white/96 border border-white/80';
        n.style.cssText = 'opacity:0;transform:translateY(16px)';
        n.innerHTML = `
            <div class="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined ${m.color} text-[18px]">${m.icon}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-xs text-on-surface">${m.title}</div>
                <div class="text-[11px] text-on-surface-variant mt-0.5 truncate">${m.desc}</div>
            </div>`;
        nc.appendChild(n);
        requestAnimationFrame(() => {
            n.style.transition = 'all 0.4s cubic-bezier(0.16,1,0.3,1)';
            n.style.opacity = '1';
            n.style.transform = 'translateY(0)';
        });
        setTimeout(() => {
            n.style.opacity = '0';
            n.style.transform = 'translateY(-12px)';
            setTimeout(() => n.remove(), 350);
        }, 4500);
    }

    setTimeout(() => {
        showNotif();
        setInterval(showNotif, 6500 + Math.random() * 3000);
    }, 3000);
});
</script>
</body>
</html>
