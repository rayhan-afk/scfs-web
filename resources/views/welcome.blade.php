<!DOCTYPE html>
<html lang="id" style="scroll-behavior: smooth;">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TREVORA | Ekosistem Operasional Modern</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
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

        /* ─── Floating Hero Animation ─── */
        @keyframes float {
            0% { transform: translateY(0px) rotateY(-12deg) rotateX(8deg); }
            50% { transform: translateY(-14px) rotateY(-10deg) rotateX(6deg); }
            100% { transform: translateY(0px) rotateY(-12deg) rotateX(8deg); }
        }
        .animate-float { animation: float 7s ease-in-out infinite; }

        /* ─── Marquee ─── */
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .animate-marquee { animation: marquee 28s linear infinite; }

        /* ─── Scroll Reveal ─── */
        .reveal { opacity: 0; transform: translateY(32px); transition: all 0.9s cubic-bezier(0.16,1,0.3,1); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .reveal-left { opacity: 0; transform: translateX(-32px); transition: all 0.9s cubic-bezier(0.16,1,0.3,1); }
        .reveal-left.active { opacity: 1; transform: translateX(0); }
        .reveal-right { opacity: 0; transform: translateX(32px); transition: all 0.9s cubic-bezier(0.16,1,0.3,1); }
        .reveal-right.active { opacity: 1; transform: translateX(0); }

        /* ─── Stagger delays ─── */
        .delay-100 { transition-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; }
        .delay-400 { transition-delay: 0.4s; }
        .delay-500 { transition-delay: 0.5s; }
        .delay-600 { transition-delay: 0.6s; }

        /* ─── Perspective ─── */
        .perspective-container { perspective: 1300px; }

        /* ─── Step connector line ─── */
        .step-line { position: absolute; left: 27px; top: 56px; bottom: -32px; width: 2px; background: linear-gradient(to bottom, #006c49, #6b38d4); opacity: 0.2; }

        /* ─── Stat number pulse ─── */
        @keyframes countup { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        .stat-num { animation: countup 0.6s ease-out forwards; }

        /* ─── Blob BG ─── */
        .blob1 { position: absolute; width: 700px; height: 700px; background: radial-gradient(circle, rgba(107,56,212,0.09) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
        .blob2 { position: absolute; width: 800px; height: 800px; background: radial-gradient(circle, rgba(0,108,73,0.07) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }

        /* ─── Glow dot ─── */
        .glow-dot { width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,0.5); animation: ping-glow 2s cubic-bezier(0,0,0.2,1) infinite; }
        @keyframes ping-glow { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.5); } 70% { box-shadow: 0 0 0 8px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }

        /* ─── Hover ripple for CTA ─── */
        .btn-primary {
            background: #006c49;
            color: #fff;
            border-radius: 999px;
            font-weight: 600;
            font-size: 15px;
            padding: 14px 32px;
            display: inline-block;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0);
            transition: background 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,108,73,0.35); }
        .btn-primary:hover::after { background: rgba(255,255,255,0.08); }

        .btn-outline {
            border: 1.5px solid rgba(187,202,191,0.7);
            border-radius: 999px;
            font-weight: 600;
            font-size: 15px;
            padding: 13px 32px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            color: #161d19;
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
            padding: 24px;
            border: 1px solid rgba(187,202,191,0.4);
            background: #fff;
            transition: all 0.3s;
        }
        .problem-card:hover { border-color: rgba(186,26,26,0.25); box-shadow: 0 8px 32px rgba(186,26,26,0.06); }

        /* ─── Notification ─── */
        #notification-container .notif { transition: all 0.35s cubic-bezier(0.16,1,0.3,1); }

        /* ─── Smooth scrollspy nav ─── */
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: #006c49; transition: width 0.3s; border-radius: 1px; }
        .nav-link.active::after { width: 100%; }
        .nav-link.active { color: #006c49; }

        /* ─── Pricing card ─── */
        .pricing-card { border-radius: 24px; border: 1.5px solid rgba(187,202,191,0.4); background: #fff; overflow: hidden; transition: all 0.4s cubic-bezier(0.16,1,0.3,1); }
        .pricing-card:hover { transform: translateY(-8px); box-shadow: 0 32px 80px rgba(0,108,73,0.1); border-color: rgba(0,108,73,0.3); }
        .pricing-card.featured { border-color: #006c49; box-shadow: 0 0 0 4px rgba(0,108,73,0.08); }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,108,73,0.25); border-radius: 3px; }
    </style>
</head>
<body class="bg-background text-on-surface overflow-x-hidden selection:bg-primary/10 selection:text-primary">

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav id="navbar" class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-20 bg-white/75 backdrop-blur-xl border-b border-outline-variant/20 shadow-sm transition-all duration-300">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-tr from-primary to-secondary rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-md">T</div>
        <span class="font-bold tracking-wider text-xl text-on-surface">TREVORA</span>
    </div>
    <div class="hidden md:flex gap-8 items-center">
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#hero">Beranda</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#features">Ekosistem</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#how-it-works">Cara Kerja</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#command-center">Dashboard</a>
        <a class="nav-link font-medium text-sm text-on-surface-variant hover:text-primary transition-colors" href="#pricing">Harga</a>
    </div>
    <div class="flex gap-3 items-center">
        <button class="hidden sm:block px-5 py-2 border border-outline-variant/60 rounded-full font-semibold text-sm hover:bg-surface-container-low transition-all text-on-surface">Lihat Demo</button>
        <a href="{{ route('login') }}" class="btn-primary !py-2.5 !px-5 !text-sm">Masuk</a>
    </div>
</nav>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<section id="hero" class="relative min-h-screen flex items-center pt-28 pb-20 px-margin-mobile md:px-margin-desktop overflow-hidden">
    <!-- Blobs -->
    <div class="blob1" style="top:-100px; left:-200px;"></div>
    <div class="blob2" style="bottom:-200px; right:-200px;"></div>
    <!-- Grid pattern -->
    <div class="absolute inset-0 pointer-events-none opacity-[0.025]" style="background-image: linear-gradient(#006c49 1px,transparent 1px),linear-gradient(90deg,#006c49 1px,transparent 1px); background-size: 40px 40px;"></div>

    <div class="relative z-10 w-full max-w-[1440px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
        <!-- Left copy -->
        <div class="lg:col-span-5 flex flex-col gap-7">
            <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-full bg-white/90 border border-secondary/20 w-fit shadow-sm reveal active">
                <span class="glow-dot"></span>
                <span class="font-bold text-xs tracking-widest text-secondary uppercase">TREVORA Engine v2.0 — Live</span>
            </div>

            <h1 class="text-[42px] md:text-[54px] lg:text-[62px] font-extrabold tracking-tight leading-[1.05] text-on-surface reveal active delay-100">
                Satu Platform,<br/>
                <span class="text-gradient">Seluruh Ekosistem</span><br/>
                Institusi Anda.
            </h1>

            <p class="text-base md:text-lg text-on-surface-variant leading-relaxed max-w-xl reveal active delay-200">
                TREVORA adalah <strong class="text-on-surface font-semibold">sistem operasi modern</strong> untuk institusi — mengelola supply chain, distribusi bantuan, merchant, pemasok, dan keuangan secara realtime dalam satu kendali terpusat berstandar enterprise.
            </p>

            <!-- Live ticker -->
            <div class="flex items-center gap-3 reveal active delay-300">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Sedang Berjalan:</span>
                <div class="px-3 py-1.5 rounded-lg bg-primary/8 border border-primary/15 overflow-hidden h-7 flex items-center" style="min-width:220px;">
                    <span class="ticker-item text-xs font-mono font-bold text-primary" id="ticker-text">Rp 4,820,500,000 diverifikasi hari ini</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 mt-1 reveal active delay-400">
                <a href="{{ route('login') }}" class="btn-primary">Masuk ke Dashboard</a>
                <button class="btn-outline">
                    <span class="material-symbols-outlined mat-outline text-[20px] text-secondary">play_circle</span>
                    Lihat Demo
                </button>
            </div>

            <!-- Trust row -->
            <div class="flex flex-wrap items-center gap-5 pt-4 border-t border-outline-variant/30 reveal active delay-500">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">verified</span>
                    <span class="text-xs text-on-surface-variant">SOC 2 Type II</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">lock</span>
                    <span class="text-xs text-on-surface-variant">AES-256 Encrypted</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">speed</span>
                    <span class="text-xs text-on-surface-variant">99.98% Uptime SLA</span>
                </div>
            </div>
        </div>

        <!-- Right mockup -->
        <div class="lg:col-span-7 relative h-[560px] w-full hidden lg:block perspective-container reveal active delay-200" id="parallax-container">
            <div class="absolute inset-0 animate-float" id="parallax-element" style="transform: rotateY(-12deg) rotateX(8deg);">
                <!-- Main panel -->
                <div class="absolute inset-0 bg-white/85 backdrop-blur-2xl rounded-2xl p-5 shadow-2xl border border-white/80 overflow-hidden">
                    <!-- Title bar -->
                    <div class="flex justify-between items-center mb-5 border-b border-outline-variant/30 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                            <span class="w-3 h-3 rounded-full bg-green-400"></span>
                            <span class="text-xs text-on-surface-variant font-medium ml-2">trevora.io/dashboard</span>
                        </div>
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/8 border border-primary/15 text-xs font-bold font-mono text-primary">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-ping inline-block"></span>
                            LIVE
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 h-[calc(100%-60px)]">
                        <div class="col-span-2 flex flex-col gap-4">
                            <!-- Chart card -->
                            <div class="p-4 rounded-xl bg-white/90 border border-outline-variant/30 shadow-sm">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <div class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider">Volume Alur Finansial</div>
                                        <div class="text-2xl font-bold text-on-surface mt-0.5">Rp 4,820,500,000</div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">↑ 18.4%</span>
                                </div>
                                <div class="h-[72px] w-full mt-2">
                                    <svg class="w-full h-full" viewBox="0 0 300 60" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="lineGrad" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#006c49" stop-opacity="0.12"/>
                                                <stop offset="100%" stop-color="#006c49" stop-opacity="0"/>
                                            </linearGradient>
                                        </defs>
                                        <path d="M0,50 Q30,35 60,40 T120,20 T180,28 T240,12 T300,5" fill="none" stroke="#006c49" stroke-width="2.5" stroke-linecap="round"/>
                                        <path d="M0,60 L0,50 Q30,35 60,40 T120,20 T180,28 T240,12 T300,5 L300,60 Z" fill="url(#lineGrad)"/>
                                        <!-- Data points -->
                                        <circle cx="60" cy="40" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="120" cy="20" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="180" cy="28" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="240" cy="12" r="3" fill="#006c49" opacity="0.6"/>
                                        <circle cx="300" cy="5" r="4" fill="#006c49"/>
                                    </svg>
                                </div>
                            </div>
                            <!-- Validation queue -->
                            <div class="p-4 rounded-xl bg-white/90 border border-outline-variant/30 shadow-sm flex-1 overflow-hidden">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Antrean Validasi OCR-AI</span>
                                    <span class="text-[10px] text-secondary font-semibold">3 dokumen</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between p-2.5 rounded-lg bg-surface items-center border border-outline-variant/20">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-primary">description</span>
                                            <span class="text-[11px] font-mono font-medium">INV-2026-089.pdf</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary text-[10px] font-bold">SELESAI</span>
                                    </div>
                                    <div class="flex justify-between p-2.5 rounded-lg bg-surface items-center border border-outline-variant/20">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-secondary">shield_person</span>
                                            <span class="text-[11px] font-mono font-medium">KYC_Merchant_33.jpg</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">PROSES</span>
                                    </div>
                                    <div class="flex justify-between p-2.5 rounded-lg bg-surface items-center border border-outline-variant/20">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-outline">pending</span>
                                            <span class="text-[11px] font-mono font-medium">NIB_Supplier_07.pdf</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md bg-surface-container text-on-surface-variant text-[10px] font-semibold">ANTREAN</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right col -->
                        <div class="col-span-1 flex flex-col gap-3">
                            <div class="p-3 rounded-xl bg-white/90 border border-outline-variant/30 shadow-sm text-center">
                                <div class="text-[10px] uppercase tracking-wider text-on-surface-variant mb-1">Supply Chain</div>
                                <div class="text-[26px] font-extrabold text-secondary">99.2%</div>
                                <div class="text-[10px] text-on-surface-variant">Efisiensi</div>
                            </div>
                            <div class="p-3 rounded-xl bg-white/90 border border-outline-variant/30 shadow-sm text-center">
                                <div class="text-[10px] uppercase tracking-wider text-on-surface-variant mb-1">Merchant Aktif</div>
                                <div class="text-[26px] font-extrabold text-primary">1,284</div>
                                <div class="text-[10px] text-emerald-600 font-semibold">↑ 24 hari ini</div>
                            </div>
                            <div class="p-3 rounded-xl bg-gradient-to-br from-[#2b322d] to-[#1a2120] text-white shadow-sm flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[9px] uppercase text-white/50 tracking-widest font-bold block mb-1">AI Auditor</span>
                                    <p class="text-[11px] text-white/80 leading-snug">Anomali terdeteksi & dimitigasi otomatis 0.02s lalu.</p>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-primary-fixed-dim bg-white/10 p-1.5 rounded-lg mt-2">
                                    <span class="material-symbols-outlined text-[14px] animate-spin">sync</span>
                                    <span>Auto-Mitigated</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating badge 1 -->
                <div class="absolute -top-8 -left-8 glass-card p-3 rounded-xl w-56 shadow-xl z-20 parallax-layer" data-speed="25">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-primary bg-primary/10 p-2 rounded-xl text-[18px]">verified_user</span>
                        <div>
                            <div class="font-bold text-xs text-on-surface">Merchant Terverifikasi</div>
                            <div class="text-[10px] text-on-surface-variant">KYC otomatis berhasil</div>
                        </div>
                    </div>
                </div>

                <!-- Floating badge 2 -->
                <div class="absolute -bottom-6 right-8 glass-card p-3 rounded-xl w-60 shadow-xl z-20 parallax-layer" data-speed="-15">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary bg-secondary/10 p-2 rounded-xl text-[18px]">analytics</span>
                        <div>
                            <div class="font-bold text-xs text-on-surface">Laporan Risiko Siap</div>
                            <div class="text-[10px] text-on-surface-variant">Analisis AI selesai — unduh</div>
                        </div>
                    </div>
                </div>

                <!-- Floating badge 3 -->
                <div class="absolute top-1/2 -right-10 glass-card p-3 rounded-xl w-52 shadow-xl z-20 parallax-layer" data-speed="20">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-emerald-600 bg-emerald-50 p-2 rounded-xl text-[18px]">payments</span>
                        <div>
                            <div class="font-bold text-xs text-on-surface">Transfer Disetujui</div>
                            <div class="text-[10px] text-on-surface-variant">Smart approval aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     MARQUEE BRANDS
══════════════════════════════════════ -->
<div class="relative border-y border-outline-variant/20 bg-white/60 backdrop-blur-sm py-5 overflow-hidden">
    <div class="absolute left-0 top-0 h-full w-28 bg-gradient-to-r from-background to-transparent z-10 pointer-events-none"></div>
    <div class="absolute right-0 top-0 h-full w-28 bg-gradient-to-l from-background to-transparent z-10 pointer-events-none"></div>
    <p class="text-center text-[11px] font-bold text-on-surface-variant uppercase tracking-widest mb-4">Dipercaya institusi terkemuka nasional & global</p>
    <div class="flex overflow-hidden">
        <div class="animate-marquee flex whitespace-nowrap gap-20 px-8 items-center text-sm font-semibold tracking-tight text-on-surface-variant/50">
            <span>CampusTech Hub</span><span>GlobalBank Asia</span><span>MegaMerchant Corp</span><span>SupraChain Logistics</span><span>FinCorp Group</span><span>UniKoop Nusantara</span><span>NexaDistrib</span><span>AcademiPay</span>
            <span>CampusTech Hub</span><span>GlobalBank Asia</span><span>MegaMerchant Corp</span><span>SupraChain Logistics</span><span>FinCorp Group</span><span>UniKoop Nusantara</span><span>NexaDistrib</span><span>AcademiPay</span>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     PROBLEM STATEMENT
══════════════════════════════════════ -->
<section class="py-24 px-margin-mobile md:px-margin-desktop bg-background overflow-hidden">
    <div class="max-w-[1440px] mx-auto">
        <div class="grid lg:grid-cols-2 gap-20 items-center">
            <div class="reveal-left">
                <span class="text-error font-bold text-xs tracking-widest uppercase bg-error/8 border border-error/15 px-3 py-1 rounded-full">Masalah Yang Sering Terjadi</span>
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-5 leading-tight">
                    Institusi Anda Masih<br/>
                    Tersandera <span class="text-gradient">Fragmentasi Sistem?</span>
                </h2>
                <p class="text-on-surface-variant text-base mt-4 leading-relaxed">
                    Banyak institusi mengoperasikan lusinan sistem terpisah — akuntansi, pengadaan, distribusi, KYC, monitoring. Data tidak tersinkronisasi. Keputusan lambat. Biaya operasional meledak.
                </p>
                <div class="flex flex-col gap-3 mt-8">
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-error/5 border border-error/15">
                        <span class="material-symbols-outlined text-error text-[20px] mt-0.5">cancel</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">5–7 aplikasi berbeda setiap harinya</div>
                            <div class="text-xs text-on-surface-variant mt-0.5">ERP, spreadsheet, WA blast, email, form manual — tidak ada yang terhubung.</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200/60">
                        <span class="material-symbols-outlined text-amber-600 text-[20px] mt-0.5">warning</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">Approval manual memakan 3–5 hari kerja</div>
                            <div class="text-xs text-on-surface-variant mt-0.5">KYC merchant, validasi dokumen, persetujuan transfer — semua antri manual.</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200/60">
                        <span class="material-symbols-outlined text-amber-600 text-[20px] mt-0.5">visibility_off</span>
                        <div>
                            <div class="font-semibold text-sm text-on-surface">Nol visibilitas realtime ke seluruh rantai</div>
                            <div class="text-xs text-on-surface-variant mt-0.5">Tidak bisa tahu posisi stok, status pengiriman, atau anomali finansial secara langsung.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reveal-right">
                <div class="relative">
                    <!-- Arrow / solution card -->
                    <div class="absolute -top-6 left-1/2 -translate-x-1/2 z-10 flex items-center justify-center w-14 h-14 rounded-full bg-primary text-white shadow-xl">
                        <span class="material-symbols-outlined text-[28px]">south</span>
                    </div>
                    <div class="glass-card rounded-2xl p-7 mt-8 border-primary/20">
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold mb-3">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Solusi TREVORA
                            </div>
                            <h3 class="font-extrabold text-2xl text-on-surface">Semua Terhubung,<br/>Semua Realtime.</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-primary">1</div>
                                <div class="text-xs text-on-surface-variant mt-0.5">Platform Terpadu</div>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-secondary">0.002s</div>
                                <div class="text-xs text-on-surface-variant mt-0.5">Waktu Approval AI</div>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-primary">99.8%</div>
                                <div class="text-xs text-on-surface-variant mt-0.5">Akurasi OCR-AI</div>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-outline-variant/30 text-center">
                                <div class="text-2xl font-extrabold text-secondary">∞</div>
                                <div class="text-xs text-on-surface-variant mt-0.5">Skalabilitas Node</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     STATS
══════════════════════════════════════ -->
<section class="py-16 px-margin-mobile md:px-margin-desktop bg-gradient-to-br from-inverse-surface to-[#1a2120] reveal">
    <div class="max-w-[1440px] mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center p-6">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim stat-num" data-target="1284">1,284</div>
                <div class="text-sm text-surface-variant/70 mt-2">Merchant Terverifikasi</div>
            </div>
            <div class="text-center p-6">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim stat-num">Rp 4.8T</div>
                <div class="text-sm text-surface-variant/70 mt-2">Volume Diproses</div>
            </div>
            <div class="text-center p-6">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim stat-num">99.98%</div>
                <div class="text-sm text-surface-variant/70 mt-2">Uptime SLA</div>
            </div>
            <div class="text-center p-6">
                <div class="text-4xl md:text-5xl font-extrabold text-primary-fixed-dim stat-num">72+</div>
                <div class="text-sm text-surface-variant/70 mt-2">Institusi Aktif</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FEATURES / ECOSYSTEM
══════════════════════════════════════ -->
<section id="features" class="py-24 px-margin-mobile md:px-margin-desktop bg-surface-container-low overflow-hidden">
    <div class="max-w-[1440px] mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="text-primary font-bold text-xs tracking-widest uppercase bg-primary/10 px-3 py-1.5 rounded-full">Ekosistem Terintegrasi</span>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 leading-tight">
                Enam Modul Inti,<br/><span class="text-gradient">Satu Sistem Hidup.</span>
            </h2>
            <p class="text-on-surface-variant text-base mt-3 leading-relaxed">Setiap modul dirancang berfungsi mandiri, namun jauh lebih powerful saat bekerja bersama dalam satu ekosistem TREVORA.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="feature-card reveal delay-100">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-primary text-[24px] mat-outline">account_tree</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Supply Chain Management</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Pantau seluruh rantai pasokan dari pemasok ke distribusi akhir. Visibilitas realtime stok, pengiriman, dan status pengadaan.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-primary/8 text-primary text-[11px] font-semibold">Live Tracking</span>
                    <span class="px-2 py-1 rounded-md bg-primary/8 text-primary text-[11px] font-semibold">Auto Reorder</span>
                    <span class="px-2 py-1 rounded-md bg-primary/8 text-primary text-[11px] font-semibold">Multi-Supplier</span>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="feature-card reveal delay-200">
                <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-secondary text-[24px] mat-outline">storefront</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Merchant & KYC Engine</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Onboarding merchant otomatis dengan AI-OCR untuk ekstraksi KTP, NIB, dan dokumen legalitas. Approval dalam detik, bukan hari.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-secondary/8 text-secondary text-[11px] font-semibold">OCR-AI</span>
                    <span class="px-2 py-1 rounded-md bg-secondary/8 text-secondary text-[11px] font-semibold">Smart Approval</span>
                    <span class="px-2 py-1 rounded-md bg-secondary/8 text-secondary text-[11px] font-semibold">Risk Scoring</span>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="feature-card reveal delay-300">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-emerald-700 text-[24px] mat-outline">volunteer_activism</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Distribusi Bantuan Digital</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Salurkan bantuan beasiswa, subsidi, atau tunjangan langsung ke penerima terverifikasi dengan audit trail penuh dan anti-fraud.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-emerald-100 text-emerald-700 text-[11px] font-semibold">Smart Distribution</span>
                    <span class="px-2 py-1 rounded-md bg-emerald-100 text-emerald-700 text-[11px] font-semibold">Audit Trail</span>
                    <span class="px-2 py-1 rounded-md bg-emerald-100 text-emerald-700 text-[11px] font-semibold">Anti-Fraud</span>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="feature-card reveal delay-200">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-amber-700 text-[24px] mat-outline">monitoring</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Financial Monitoring & AI Audit</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Deteksi anomali transaksi dengan AI secara realtime. Dashboard finansial lengkap dengan insight prediktif dan laporan otomatis.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-amber-100 text-amber-700 text-[11px] font-semibold">Anomali Detection</span>
                    <span class="px-2 py-1 rounded-md bg-amber-100 text-amber-700 text-[11px] font-semibold">Predictive AI</span>
                    <span class="px-2 py-1 rounded-md bg-amber-100 text-amber-700 text-[11px] font-semibold">Auto Report</span>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="feature-card reveal delay-300">
                <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-sky-700 text-[24px] mat-outline">local_shipping</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Logistik & Pemasok Terpadu</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Kelola hubungan pemasok, negosiasi kontrak, penerimaan barang, dan pembayaran dalam satu platform terintegrasi.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-sky-100 text-sky-700 text-[11px] font-semibold">E-Procurement</span>
                    <span class="px-2 py-1 rounded-md bg-sky-100 text-sky-700 text-[11px] font-semibold">Vendor Portal</span>
                    <span class="px-2 py-1 rounded-md bg-sky-100 text-sky-700 text-[11px] font-semibold">GRN Digital</span>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="feature-card reveal delay-400">
                <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-violet-700 text-[24px] mat-outline">hub</span>
                </div>
                <h3 class="font-bold text-lg text-on-surface mb-2">Command Center & Notifikasi</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-4">Satu layar untuk memantau seluruh ekosistem institusi. Notifikasi cerdas, workflow approval, dan escalation otomatis berbasis rule.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded-md bg-violet-100 text-violet-700 text-[11px] font-semibold">Realtime Feed</span>
                    <span class="px-2 py-1 rounded-md bg-violet-100 text-violet-700 text-[11px] font-semibold">Smart Notif</span>
                    <span class="px-2 py-1 rounded-md bg-violet-100 text-violet-700 text-[11px] font-semibold">Role-Based</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════ -->
<section id="how-it-works" class="py-24 px-margin-mobile md:px-margin-desktop bg-background">
    <div class="max-w-[1440px] mx-auto">
        <div class="grid lg:grid-cols-2 gap-20 items-center">
            <!-- Steps -->
            <div class="reveal-left">
                <span class="text-secondary font-bold text-xs tracking-widest uppercase bg-secondary/10 px-3 py-1.5 rounded-full">Cara Kerja</span>
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 mb-10 leading-tight">
                    Dari Onboarding<br/><span class="text-gradient">ke Operasi Penuh</span><br/>dalam 4 Langkah.
                </h2>

                <div class="flex flex-col gap-0">
                    <div class="flex gap-5 relative pb-10">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">1</span>
                        </div>
                        <div class="pt-1">
                            <h4 class="font-bold text-base text-on-surface">Daftar & Konfigurasi Institusi</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Setup profil institusi, tambahkan anggota tim, dan konfigurasikan modul yang dibutuhkan dalam hitungan menit.</p>
                        </div>
                    </div>
                    <div class="flex gap-5 relative pb-10">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-secondary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">2</span>
                        </div>
                        <div class="pt-1">
                            <h4 class="font-bold text-base text-on-surface">Onboarding Merchant & Pemasok</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Undang merchant dan pemasok ke portal. AI-OCR memverifikasi dokumen legalitas secara otomatis, tanpa proses manual.</p>
                        </div>
                    </div>
                    <div class="flex gap-5 relative pb-10">
                        <div class="step-line"></div>
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">3</span>
                        </div>
                        <div class="pt-1">
                            <h4 class="font-bold text-base text-on-surface">Aktifkan Alur Operasional</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Jalankan pengadaan, distribusi bantuan, dan monitoring keuangan. Semua terhubung realtime — satu aksi, efek di seluruh ekosistem.</p>
                        </div>
                    </div>
                    <div class="flex gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-600 flex items-center justify-center shrink-0 shadow-md z-10">
                            <span class="text-white font-extrabold text-xl">4</span>
                        </div>
                        <div class="pt-1">
                            <h4 class="font-bold text-base text-on-surface">Monitor, Audit, dan Scale</h4>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Pantau seluruh ekosistem dari command center. AI Auditor bekerja 24/7. Skalakan ke ratusan merchant tanpa menambah overhead.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual panel -->
            <div class="reveal-right">
                <div class="relative">
                    <!-- Ecosystem diagram -->
                    <div class="bg-white rounded-2xl border border-outline-variant/40 shadow-xl p-6 overflow-hidden">
                        <div class="text-center mb-6">
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">TREVORA Ecosystem Architecture</span>
                        </div>
                        <!-- Center node -->
                        <div class="flex justify-center mb-4">
                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-xl relative">
                                <span class="text-white font-extrabold text-sm text-center leading-tight">TREVORA<br/>Core</span>
                                <!-- Pulse rings -->
                                <span class="absolute inset-0 rounded-full border-2 border-primary/30 animate-ping"></span>
                            </div>
                        </div>
                        <!-- Connected nodes -->
                        <div class="grid grid-cols-3 gap-3 mt-2">
                            <div class="p-3 rounded-xl border border-outline-variant/40 bg-surface text-center">
                                <span class="material-symbols-outlined text-primary text-[22px] mat-outline block mb-1">account_tree</span>
                                <span class="text-[11px] font-semibold text-on-surface">Supply Chain</span>
                            </div>
                            <div class="p-3 rounded-xl border border-secondary/20 bg-secondary/5 text-center">
                                <span class="material-symbols-outlined text-secondary text-[22px] mat-outline block mb-1">storefront</span>
                                <span class="text-[11px] font-semibold text-on-surface">Merchant KYC</span>
                            </div>
                            <div class="p-3 rounded-xl border border-outline-variant/40 bg-surface text-center">
                                <span class="material-symbols-outlined text-emerald-700 text-[22px] mat-outline block mb-1">volunteer_activism</span>
                                <span class="text-[11px] font-semibold text-on-surface">Distribusi</span>
                            </div>
                            <div class="p-3 rounded-xl border border-outline-variant/40 bg-surface text-center">
                                <span class="material-symbols-outlined text-amber-600 text-[22px] mat-outline block mb-1">monitoring</span>
                                <span class="text-[11px] font-semibold text-on-surface">Financial AI</span>
                            </div>
                            <div class="p-3 rounded-xl border border-outline-variant/40 bg-surface text-center">
                                <span class="material-symbols-outlined text-sky-600 text-[22px] mat-outline block mb-1">local_shipping</span>
                                <span class="text-[11px] font-semibold text-on-surface">Logistik</span>
                            </div>
                            <div class="p-3 rounded-xl border border-violet-200 bg-violet-50 text-center">
                                <span class="material-symbols-outlined text-violet-700 text-[22px] mat-outline block mb-1">hub</span>
                                <span class="text-[11px] font-semibold text-on-surface">Command Ctr</span>
                            </div>
                        </div>
                        <!-- Connecting lines overlay -->
                        <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-20" style="top:0;left:0;" preserveAspectRatio="none" viewBox="0 0 100 100">
                            <line x1="50" y1="35" x2="16" y2="65" stroke="#006c49" stroke-width="0.5" stroke-dasharray="2,2"/>
                            <line x1="50" y1="35" x2="50" y2="65" stroke="#6b38d4" stroke-width="0.5" stroke-dasharray="2,2"/>
                            <line x1="50" y1="35" x2="84" y2="65" stroke="#006c49" stroke-width="0.5" stroke-dasharray="2,2"/>
                        </svg>
                    </div>

                    <!-- Live indicator -->
                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 px-4 py-2 rounded-full bg-primary text-white text-xs font-bold shadow-lg">
                        <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                        Semua Node Aktif — 0 Error
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
            <span class="text-primary font-bold text-xs tracking-widest uppercase bg-primary/10 px-3 py-1.5 rounded-full">Live Dashboard</span>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 leading-tight">
                Command Center<br/><span class="text-gradient">Operasional & Finansial</span>
            </h2>
            <p class="text-on-surface-variant text-base mt-3">Visibilitas data tinggi, kecepatan kueri instan, akurasi mutakhir — semuanya di satu layar.</p>
        </div>

        <!-- Tab switcher -->
        <div class="flex gap-2 justify-center mb-8">
            <button class="tab-btn active" onclick="switchTab('financial')">Log Finansial</button>
            <button class="tab-btn" onclick="switchTab('kyc')">Verifikasi KYC</button>
            <button class="tab-btn" onclick="switchTab('supply')">Supply Chain</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Main table -->
            <div class="lg:col-span-8 bg-white border border-outline-variant/40 rounded-2xl shadow-sm overflow-hidden">
                <!-- Financial Tab -->
                <div id="tab-financial">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-6 border-b border-outline-variant/20">
                        <div>
                            <h3 class="font-bold text-lg text-on-surface">Log Aktivitas Finansial Merchant</h3>
                            <p class="text-xs text-on-surface-variant">Realtime update dari seluruh kluster distribusi</p>
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
                                    <th class="p-4">ID</th>
                                    <th class="p-4">Institusi / Instansi</th>
                                    <th class="p-4">Volume</th>
                                    <th class="p-4">Status Risiko</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/20 font-mono text-[13px]">
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">#TRV-90812</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">CampusTech Distribution Hub</td>
                                    <td class="p-4 font-semibold">Rp 1,240,000,000</td>
                                    <td class="p-4"><span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-sans font-bold border border-emerald-200">✓ Safe Node</span></td>
                                    <td class="p-4 text-right font-sans"><button class="text-secondary hover:underline text-xs font-semibold">Audit →</button></td>
                                </tr>
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">#TRV-89102</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">MegaMerchant Logistic Utama</td>
                                    <td class="p-4 font-semibold">Rp 850,000,000</td>
                                    <td class="p-4"><span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-sans font-bold border border-emerald-200">✓ Safe Node</span></td>
                                    <td class="p-4 text-right font-sans"><button class="text-secondary hover:underline text-xs font-semibold">Audit →</button></td>
                                </tr>
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">#TRV-88271</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">SupraChain Regional West</td>
                                    <td class="p-4 font-semibold">Rp 2,110,000,000</td>
                                    <td class="p-4"><span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-sans font-bold border border-amber-200">⚠ Reviewing</span></td>
                                    <td class="p-4 text-right font-sans"><button class="text-secondary hover:underline text-xs font-semibold">Periksa →</button></td>
                                </tr>
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="p-4 font-bold text-primary">#TRV-87910</td>
                                    <td class="p-4 font-sans font-medium text-on-surface">UniKoop Nusantara Pusat</td>
                                    <td class="p-4 font-semibold">Rp 620,500,000</td>
                                    <td class="p-4"><span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-sans font-bold border border-emerald-200">✓ Safe Node</span></td>
                                    <td class="p-4 text-right font-sans"><button class="text-secondary hover:underline text-xs font-semibold">Audit →</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- KYC Tab (hidden by default) -->
                <div id="tab-kyc" class="hidden p-6">
                    <h3 class="font-bold text-lg text-on-surface mb-4">Antrean Verifikasi KYC</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface">
                            <span class="material-symbols-outlined text-primary text-[20px]">person</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">PT Sinar Makmur Sentosa</div>
                                <div class="text-xs text-on-surface-variant">KTP + NIB diupload · 2 mnt lalu</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold text-primary">98.8% akurasi</div>
                                <span class="px-2 py-0.5 text-[10px] rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold">Menunggu Review</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface">
                            <span class="material-symbols-outlined text-secondary text-[20px]">storefront</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">CV Maju Bersama Digital</div>
                                <div class="text-xs text-on-surface-variant">Semua dokumen terverifikasi · 5 mnt lalu</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold text-primary">99.2% akurasi</div>
                                <span class="px-2 py-0.5 text-[10px] rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">Disetujui AI</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface">
                            <span class="material-symbols-outlined text-on-surface-variant text-[20px]">business</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">UD Karya Mandiri Jaya</div>
                                <div class="text-xs text-on-surface-variant">Dokumen belum lengkap · 12 mnt lalu</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold text-error">67% akurasi</div>
                                <span class="px-2 py-0.5 text-[10px] rounded-full bg-red-50 text-red-700 border border-red-200 font-bold">Perlu Revisi</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Supply Chain Tab -->
                <div id="tab-supply" class="hidden p-6">
                    <h3 class="font-bold text-lg text-on-surface mb-4">Status Supply Chain Aktif</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface">
                            <span class="material-symbols-outlined text-sky-600 text-[20px]">local_shipping</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">PO-2026-1024 · Alat Tulis Kantor</div>
                                <div class="text-xs text-on-surface-variant">Vendor: SupraChain · ETA: 2 hari lagi</div>
                            </div>
                            <div class="w-24">
                                <div class="h-1.5 bg-outline-variant/30 rounded-full overflow-hidden">
                                    <div class="bg-sky-500 h-full rounded-full" style="width:75%"></div>
                                </div>
                                <div class="text-[11px] text-on-surface-variant mt-1 text-right">75% terkirim</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-outline-variant/30 bg-surface">
                            <span class="material-symbols-outlined text-emerald-600 text-[20px]">inventory_2</span>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-on-surface">PO-2026-1018 · Sembako Bantuan</div>
                                <div class="text-xs text-on-surface-variant">Vendor: NexaDistrib · Diterima penuh</div>
                            </div>
                            <div class="w-24">
                                <div class="h-1.5 bg-outline-variant/30 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width:100%"></div>
                                </div>
                                <div class="text-[11px] text-emerald-600 mt-1 text-right font-semibold">Selesai ✓</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right sidebar cards -->
            <div class="lg:col-span-4 flex flex-col gap-5">
                <!-- KYC card -->
                <div class="bg-white border border-outline-variant/40 rounded-2xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-base text-on-surface">Pemeriksaan Legalitas</h4>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                    </div>
                    <div class="p-4 rounded-xl bg-surface border border-outline-variant/30 mb-4">
                        <div class="text-xs text-on-surface-variant">Dokumen KTP & NIB diupload</div>
                        <div class="font-bold text-sm text-on-surface mt-1">PT Sinar Makmur Sentosa</div>
                        <div class="w-full bg-outline-variant/30 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width:88%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-on-surface-variant mt-1.5">
                            <span>Akurasi Ekstraksi AI</span>
                            <span class="font-bold text-primary">98.8%</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button class="py-2.5 rounded-xl bg-error/8 hover:bg-error/15 text-error font-semibold text-xs transition-colors border border-error/20">Tolak Dokumen</button>
                        <button class="py-2.5 rounded-xl bg-primary text-white font-semibold text-xs shadow-sm hover:bg-primary/90 transition-colors">Setujui Instan</button>
                    </div>
                </div>

                <!-- AI load card -->
                <div class="bg-gradient-to-br from-secondary to-[#3d1a8c] text-white rounded-2xl shadow-sm p-5">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="font-bold text-sm uppercase tracking-wider opacity-80">AI Automation</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/15 border border-white/25">AKTIF</span>
                    </div>
                    <div class="text-4xl font-extrabold mt-1">0.0042s</div>
                    <div class="text-xs opacity-60 mb-4">rata-rata latency per operasi</div>
                    <p class="text-xs opacity-70 leading-relaxed">Seluruh node AI bekerja di bawah ambang batas aman enterprise. Sinkronisasi data lancar tanpa interruption.</p>
                    <div class="mt-4 flex items-center gap-2 text-xs bg-white/10 rounded-lg p-2.5 border border-white/15">
                        <span class="material-symbols-outlined text-[16px] text-primary-fixed-dim mat-outline">security</span>
                        <span class="text-white/80">0 anomali aktif — sistem normal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     PRICING
══════════════════════════════════════ -->
<section id="pricing" class="py-24 px-margin-mobile md:px-margin-desktop bg-background">
    <div class="max-w-[1440px] mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="text-primary font-bold text-xs tracking-widest uppercase bg-primary/10 px-3 py-1.5 rounded-full">Paket Harga</span>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mt-4 leading-tight">
                Harga Transparan,<br/><span class="text-gradient">Tanpa Biaya Tersembunyi.</span>
            </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
            <!-- Starter -->
            <div class="pricing-card p-7 reveal delay-100">
                <div class="mb-5">
                    <div class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-2">Starter</div>
                    <div class="text-4xl font-extrabold text-on-surface">Gratis</div>
                    <div class="text-sm text-on-surface-variant mt-1">Uji coba penuh, 30 hari</div>
                </div>
                <div class="flex flex-col gap-3 mb-7 text-sm">
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">Hingga 50 merchant</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">Modul supply chain dasar</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">KYC AI — 100 dokumen/bulan</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-outline text-[18px]">remove_circle</span><span class="text-on-surface-variant/60">Financial Monitoring AI</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-outline text-[18px]">remove_circle</span><span class="text-on-surface-variant/60">Supply Chain Financing</span></div>
                </div>
                <button class="w-full btn-outline !justify-center">Mulai Gratis</button>
            </div>
            <!-- Pro (featured) -->
            <div class="pricing-card featured p-7 reveal delay-200">
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="text-xs font-bold uppercase tracking-widest text-primary">Professional</div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">Terpopuler</span>
                    </div>
                    <div class="text-4xl font-extrabold text-on-surface">Rp 2.4jt<span class="text-lg font-medium text-on-surface-variant">/bln</span></div>
                    <div class="text-sm text-on-surface-variant mt-1">Per institusi, semua modul</div>
                </div>
                <div class="flex flex-col gap-3 mb-7 text-sm">
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">Merchant tidak terbatas</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">Semua 6 modul ekosistem</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">KYC AI — unlimited</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">Financial Monitoring AI</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-primary text-[18px]">check_circle</span><span class="text-on-surface-variant">Supply Chain Financing</span></div>
                </div>
                <a href="{{ route('login') }}" class="btn-primary w-full !text-center block">Mulai Sekarang</a>
            </div>
            <!-- Enterprise -->
            <div class="pricing-card p-7 reveal delay-300">
                <div class="mb-5">
                    <div class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-2">Enterprise</div>
                    <div class="text-4xl font-extrabold text-on-surface">Custom</div>
                    <div class="text-sm text-on-surface-variant mt-1">Untuk institusi skala besar</div>
                </div>
                <div class="flex flex-col gap-3 mb-7 text-sm">
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-secondary text-[18px]">check_circle</span><span class="text-on-surface-variant">Dedicated infrastructure</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-secondary text-[18px]">check_circle</span><span class="text-on-surface-variant">SLA 99.99% + support 24/7</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-secondary text-[18px]">check_circle</span><span class="text-on-surface-variant">Custom AI model training</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-secondary text-[18px]">check_circle</span><span class="text-on-surface-variant">White-label dashboard</span></div>
                    <div class="flex items-center gap-2.5"><span class="material-symbols-outlined text-secondary text-[18px]">check_circle</span><span class="text-on-surface-variant">Integrasi ERP existing</span></div>
                </div>
                <button class="w-full btn-outline !justify-center border-secondary/30 text-secondary hover:bg-secondary/5">Hubungi Sales</button>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════ -->
<section class="py-20 px-margin-mobile md:px-margin-desktop bg-surface-container-low reveal">
    <div class="max-w-[1440px] mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-on-surface">Apa Kata Mereka?</h2>
            <p class="text-on-surface-variant mt-2">Institusi yang sudah merasakan perbedaan TREVORA.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-outline-variant/40 p-6 shadow-sm hover:shadow-lg transition-shadow">
                <div class="flex gap-1 mb-4">
                    <span class="text-amber-400 text-lg">★★★★★</span>
                </div>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-5">"Proses verifikasi merchant kami yang dulunya 3-5 hari kerja, sekarang selesai dalam hitungan menit. TREVORA benar-benar mengubah cara kami beroperasi."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center text-primary font-bold text-sm">AW</div>
                    <div>
                        <div class="font-semibold text-sm text-on-surface">Andi Wijaya</div>
                        <div class="text-xs text-on-surface-variant">COO · CampusTech Distribution Hub</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-outline-variant/40 p-6 shadow-sm hover:shadow-lg transition-shadow">
                <div class="flex gap-1 mb-4">
                    <span class="text-amber-400 text-lg">★★★★★</span>
                </div>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-5">"Supply chain monitoring yang dulu butuh 4 orang penuh, kini otomatis. Kami bisa fokus ke strategi, bukan operasional harian. ROI-nya luar biasa."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-secondary/15 flex items-center justify-center text-secondary font-bold text-sm">SR</div>
                    <div>
                        <div class="font-semibold text-sm text-on-surface">Siti Rahayu</div>
                        <div class="text-xs text-on-surface-variant">Head of Ops · SupraChain Logistics</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-outline-variant/40 p-6 shadow-sm hover:shadow-lg transition-shadow">
                <div class="flex gap-1 mb-4">
                    <span class="text-amber-400 text-lg">★★★★★</span>
                </div>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-5">"Distribusi bantuan beasiswa ke 2,000+ mahasiswa kini transparan dan teraudit penuh. Tidak ada lagi kebocoran, tidak ada pertanyaan dari audit internal."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-sm">BP</div>
                    <div>
                        <div class="font-semibold text-sm text-on-surface">Budi Pratama</div>
                        <div class="text-xs text-on-surface-variant">Direktur Keuangan · UniKoop Nusantara</div>
                    </div>
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
            <span class="text-xs font-bold tracking-wider text-primary-fixed-dim uppercase">Tersedia Sekarang</span>
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-tight mb-6">
            Siap Mengubah Cara<br/>Institusi Anda Beroperasi?
        </h2>
        <p class="text-white/60 text-lg leading-relaxed mb-10 max-w-xl mx-auto">
            Bergabunglah dengan 72+ institusi yang telah menjadikan TREVORA sebagai sistem operasi ekosistem mereka. Mulai gratis, tanpa kartu kredit.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('login') }}" class="btn-primary !bg-primary-fixed-dim !text-[#002113] !font-extrabold hover:!bg-primary-fixed text-base">
                Mulai Gratis 30 Hari →
            </a>
            <button class="btn-outline !border-white/20 !text-white/80 !bg-white/5 hover:!bg-white/10 text-base">
                <span class="material-symbols-outlined mat-outline text-[20px]">calendar_today</span>
                Jadwalkan Demo
            </button>
        </div>
        <p class="text-white/30 text-xs mt-6">Tidak perlu kartu kredit · Setup dalam 5 menit · Batalkan kapan saja</p>
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
            <p class="text-sm text-surface-variant/60 leading-relaxed max-w-xs">
                Ekosistem Operasional Modern. Platform SaaS next-generation untuk manajemen ekosistem ekonomi institusi secara efisien, scalable, dan intelligent.
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
            <h5 class="font-bold text-sm text-surface-bright mb-4 uppercase tracking-wider">Produk</h5>
            <div class="flex flex-col gap-2.5">
                <a href="#features" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Ekosistem Modul</a>
                <a href="#how-it-works" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Cara Kerja</a>
                <a href="#command-center" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Command Center</a>
                <a href="#pricing" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Harga</a>
                <a href="#" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Dokumentasi API</a>
            </div>
        </div>
        <div>
            <h5 class="font-bold text-sm text-surface-bright mb-4 uppercase tracking-wider">Perusahaan</h5>
            <div class="flex flex-col gap-2.5">
                <a href="#" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Tentang Kami</a>
                <a href="#" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Privacy Policy</a>
                <a href="#" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Terms of Service</a>
                <a href="#" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Security Core</a>
                <a href="#" class="text-sm text-surface-variant/60 hover:text-surface-bright transition">Hubungi Kami</a>
            </div>
        </div>
    </div>
    <div class="border-t border-white/8 px-margin-mobile md:px-margin-desktop py-5 flex flex-col md:flex-row justify-between items-center gap-3">
        <p class="text-xs text-surface-variant/40">© 2026 Trevora AI Operational Ecosystem. All rights reserved.</p>
        <p class="text-xs text-primary-fixed-dim/60">Dibuat dengan ❤ untuk ekosistem institusi Indonesia</p>
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

    // ── Tab switcher ──
    window.switchTab = function(tab) {
        ['financial', 'kyc', 'supply'].forEach(t => {
            const el = document.getElementById('tab-' + t);
            if (el) el.classList.toggle('hidden', t !== tab);
        });
        document.querySelectorAll('.tab-btn').forEach((btn, i) => {
            const tabs = ['financial', 'kyc', 'supply'];
            btn.classList.toggle('active', tabs[i] === tab);
        });
    };

    // ── Live ticker ──
    const tickers = [
        'Rp 4,820,500,000 diverifikasi hari ini',
        '1,284 merchant aktif di jaringan',
        '0 anomali terdeteksi dalam 5,000 log',
        '99.2% efisiensi supply chain bulan ini',
        '72 institusi beroperasi di TREVORA',
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

    // ── Live notifications ──
    const msgs = [
        { icon: 'account_balance', title: 'Dana Masuk Node', desc: 'Rp 150,000,000 terverifikasi dari CampusTech', color: 'text-secondary' },
        { icon: 'check_circle', title: 'KYC Otomatis', desc: 'NIB MegaMerchant disetujui AI dalam 0.8s', color: 'text-primary' },
        { icon: 'monitoring', title: 'Audit Selesai', desc: '0 anomali dalam 5,000 log terakhir', color: 'text-emerald-600' },
        { icon: 'local_shipping', title: 'Pengiriman Tiba', desc: 'PO-2026-1018 diterima penuh di gudang', color: 'text-sky-600' },
        { icon: 'payments', title: 'Transfer Disetujui', desc: 'Smart approval aktif — Rp 620jt diproses', color: 'text-violet-600' },
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
        setInterval(showNotif, 6000 + Math.random() * 3000);
    }, 3000);
});
</script>
</body>
</html>