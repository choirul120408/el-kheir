<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$userName = (string) ($_SESSION['user_name'] ?? 'Pengguna SMK BNB');
$userEmail = (string) ($_SESSION['user_email'] ?? 'user@smkbnb.sch.id');
$userRole = (string) ($_SESSION['user_role'] ?? 'siswa');
$userRoleLabel = (string) ($_SESSION['user_role_label'] ?? ucfirst($userRole));
$userDept = (string) ($_SESSION['user_department'] ?? 'Teknologi Vokasi');
$userClass = (string) ($_SESSION['user_class'] ?? 'Kelas Terdaftar');
$userTokens = (int) ($_SESSION['user_tokens'] ?? 2850);
$userGpa = (string) ($_SESSION['user_gpa'] ?? '3.92');
$userAttendance = (string) ($_SESSION['user_attendance'] ?? '98.8%');
$userAvatar = (string) ($_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80');

$tokenPriceIdr = 12850;
$totalEstimatedIdr = number_format($userTokens * $tokenPriceIdr, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Dashboard — SMK Bangun Nusa Bangsa | Indodax Edition</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --idx-dark-900: #07090e;
      --idx-dark-800: #0b0f19;
      --idx-dark-700: #101626;
      --idx-dark-600: #162035;
      --idx-dark-500: #1f2d47;
      
      --idx-primary: #0066f5;
      --idx-cyan: #00c2ff;
      --idx-neon: #00f0ff;
      --idx-green: #0ecb81;
      --idx-red: #f6465d;
      --idx-gold: #f0b90b;
      
      --idx-text-high: #f8fafc;
      --idx-text-mid: #94a3b8;
      --idx-text-muted: #64748b;
      
      --font-main: 'Plus Jakarta Sans', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --font-display: 'Space Grotesk', sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      background-color: var(--idx-dark-900);
      color: var(--idx-text-high);
      font-family: var(--font-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    /* Ambient lighting */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      right: 0;
      width: 50vw;
      height: 50vw;
      background: radial-gradient(circle, rgba(0, 102, 245, 0.12) 0%, transparent 70%);
      filter: blur(100px);
      z-index: 0;
      pointer-events: none;
    }

    /* TOP LIVE TICKER */
    .crypto-ticker-bar {
      background: rgba(7, 10, 17, 0.95);
      border-bottom: 1px solid rgba(0, 194, 255, 0.12);
      padding: 6px 0;
      font-family: var(--font-mono);
      font-size: 0.76rem;
      overflow: hidden;
      white-space: nowrap;
      position: relative;
      z-index: 10;
    }

    .ticker-content {
      display: inline-flex;
      gap: 32px;
      animation: tickerScroll 40s linear infinite;
    }

    @keyframes tickerScroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    .ticker-pair { display: inline-flex; align-items: center; gap: 7px; }
    .ticker-pair .symbol { font-weight: 700; color: #fff; }
    .ticker-pair .price { color: #94a3b8; }
    .ticker-pair .change-bnb {
      color: var(--idx-cyan);
      background: rgba(0, 194, 255, 0.12);
      border: 1px solid rgba(0, 194, 255, 0.3);
      padding: 1px 7px;
      border-radius: 4px;
      font-weight: 700;
    }
    .ticker-pair .change-up {
      color: var(--idx-green);
      background: rgba(14, 203, 129, 0.1);
      padding: 1px 6px;
      border-radius: 4px;
      font-weight: 600;
    }

    /* HEADER */
    .portal-header {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(11, 15, 25, 0.82);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding: 14px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .brand-group {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .brand-hex {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #0066f5, #00f0ff);
      clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%);
      display: grid;
      place-items: center;
      box-shadow: 0 0 16px rgba(0, 194, 255, 0.4);
    }

    .brand-hex-inner {
      width: 34px;
      height: 34px;
      background: var(--idx-dark-800);
      clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%);
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 0.85rem;
      color: var(--idx-neon);
      font-family: var(--font-display);
    }

    .brand-info strong {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.98rem;
      color: #fff;
    }

    .badge-ed {
      background: linear-gradient(90deg, #0066f5, #00c2ff);
      color: #fff;
      font-size: 0.6rem;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 999px;
    }

    .user-profile-menu {
      display: flex;
      align-items: center;
      gap: 18px;
    }

    .user-badge-card {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(16, 25, 45, 0.65);
      border: 1px solid rgba(0, 194, 255, 0.2);
      padding: 6px 14px 6px 8px;
      border-radius: 999px;
    }

    .avatar-img {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--idx-cyan);
    }

    .user-meta-txt {
      line-height: 1.2;
    }

    .user-meta-txt strong {
      display: block;
      font-size: 0.82rem;
      color: #fff;
    }

    .user-meta-txt small {
      color: var(--idx-cyan);
      font-size: 0.68rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .btn-logout {
      background: rgba(246, 70, 93, 0.12);
      border: 1px solid rgba(246, 70, 93, 0.35);
      color: #fca5a5;
      padding: 7px 16px;
      border-radius: 9px;
      font-size: 0.8rem;
      font-weight: 600;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .btn-logout:hover {
      background: var(--idx-red);
      color: #fff;
      box-shadow: 0 0 16px rgba(246, 70, 93, 0.4);
    }

    /* MAIN DASHBOARD SHELL */
    .dashboard-container {
      position: relative;
      z-index: 1;
      max-width: 1320px;
      width: calc(100% - 64px);
      margin: 28px auto;
      flex: 1;
    }

    /* GREETING HERO BANNER */
    .dash-hero-banner {
      background: linear-gradient(135deg, rgba(16, 25, 45, 0.85) 0%, rgba(11, 15, 25, 0.95) 100%);
      border: 1px solid rgba(0, 194, 255, 0.25);
      border-radius: 20px;
      padding: 28px 32px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      overflow: hidden;
      box-shadow: 0 18px 45px rgba(0, 0, 0, 0.4);
    }

    .dash-hero-banner::after {
      content: '';
      position: absolute;
      top: -40px;
      right: -40px;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(0, 194, 255, 0.25), transparent 70%);
      pointer-events: none;
    }

    .banner-left h1 {
      font-size: 1.65rem;
      font-family: var(--font-display);
      margin-bottom: 6px;
      color: #fff;
    }

    .banner-left p {
      color: var(--idx-text-mid);
      font-size: 0.88rem;
    }

    .banner-right-stats {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .status-chip {
      background: rgba(14, 203, 129, 0.1);
      border: 1px solid rgba(14, 203, 129, 0.3);
      color: var(--idx-green);
      padding: 8px 16px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ASSET CARDS ROW (INDODAX PORTFOLIO VIBE) */
    .asset-metrics-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 24px;
    }

    .metric-card {
      background: rgba(14, 21, 37, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 22px;
      backdrop-filter: blur(20px);
      transition: all 0.25s ease;
      position: relative;
      overflow: hidden;
    }

    .metric-card:hover {
      border-color: rgba(0, 194, 255, 0.4);
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0, 102, 245, 0.2);
    }

    .card-label-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: var(--idx-text-mid);
      font-size: 0.75rem;
      font-weight: 600;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .card-val-big {
      font-size: 1.55rem;
      font-family: var(--font-display);
      font-weight: 700;
      color: #fff;
      margin-bottom: 6px;
    }

    .card-sub-val {
      font-family: var(--font-mono);
      font-size: 0.74rem;
      color: var(--idx-green);
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* TWO COLUMN LAYOUT */
    .dashboard-grid-2col {
      display: grid;
      grid-template-columns: 1.35fr 0.85fr;
      gap: 24px;
    }

    .dash-panel {
      background: rgba(14, 21, 37, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 24px;
      backdrop-filter: blur(20px);
    }

    .panel-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .panel-head h2 {
      font-size: 1.1rem;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* SCHEDULE / TRANSACTION LEDGER TABLE */
    .crypto-ledger-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
    }

    .crypto-ledger-table th {
      text-align: left;
      padding: 10px 12px;
      color: var(--idx-text-muted);
      font-size: 0.72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .crypto-ledger-table td {
      padding: 14px 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      color: #cbd5e1;
    }

    .crypto-ledger-table tr:hover td {
      background: rgba(0, 194, 255, 0.03);
    }

    .badge-status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.7rem;
      font-weight: 700;
    }

    .badge-status.done {
      background: rgba(14, 203, 129, 0.12);
      color: var(--idx-green);
      border: 1px solid rgba(14, 203, 129, 0.3);
    }

    .badge-status.process {
      background: rgba(0, 194, 255, 0.12);
      color: var(--idx-cyan);
      border: 1px solid rgba(0, 194, 255, 0.3);
    }

    /* QUICK ACTION GRID */
    .quick-actions-list {
      display: grid;
      gap: 12px;
    }

    .action-btn-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      background: rgba(16, 25, 45, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 14px;
      color: #fff;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .action-btn-item:hover {
      background: rgba(0, 194, 255, 0.08);
      border-color: var(--idx-cyan);
      transform: translateX(4px);
    }

    .action-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .action-icon-box {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, rgba(0, 102, 245, 0.2), rgba(0, 194, 255, 0.1));
      border: 1px solid rgba(0, 194, 255, 0.3);
      display: grid;
      place-items: center;
      color: var(--idx-cyan);
    }

    .action-titles strong {
      display: block;
      font-size: 0.84rem;
    }

    .action-titles small {
      color: var(--idx-text-mid);
      font-size: 0.72rem;
    }

    /* FOOTER */
    .dash-footer {
      padding: 24px 32px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      background: rgba(7, 10, 17, 0.95);
      margin-top: 48px;
      text-align: center;
      color: var(--idx-text-muted);
      font-size: 0.75rem;
    }

    @media (max-width: 1024px) {
      .asset-metrics-grid { grid-template-columns: repeat(2, 1fr); }
      .dashboard-grid-2col { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
      .portal-header { padding: 12px 18px; }
      .dashboard-container { width: calc(100% - 32px); margin: 16px auto; }
      .dash-hero-banner { flex-direction: column; align-items: flex-start; gap: 14px; padding: 20px; }
      .asset-metrics-grid { grid-template-columns: 1fr; }
      .user-meta-txt { display: none; }
    }
  </style>
</head>
<body>
  <!-- TICKER -->
  <aside class="crypto-ticker-bar">
    <div class="ticker-content">
      <div class="ticker-pair"><span class="symbol">BNB-SMK/IDR</span><span class="price">Rp 12.850</span><span class="change-bnb">▲ +8.75%</span></div>
      <div class="ticker-pair"><span class="symbol">BTC/IDR</span><span class="price">Rp 1.542.180.000</span><span class="change-up">▲ +3.42%</span></div>
      <div class="ticker-pair"><span class="symbol">ETH/IDR</span><span class="price">Rp 54.120.000</span><span class="change-up">▲ +2.18%</span></div>
      <div class="ticker-pair"><span class="symbol">PRESTASI-INDEX</span><span class="price"><?= e($userGpa) ?> / 4.00</span><span class="change-bnb">▲ EXCELLENT</span></div>
      <!-- Duplicate -->
      <div class="ticker-pair"><span class="symbol">BNB-SMK/IDR</span><span class="price">Rp 12.850</span><span class="change-bnb">▲ +8.75%</span></div>
      <div class="ticker-pair"><span class="symbol">BTC/IDR</span><span class="price">Rp 1.542.180.000</span><span class="change-up">▲ +3.42%</span></div>
    </div>
  </aside>

  <!-- HEADER -->
  <header class="portal-header">
    <a href="index.php" class="brand-group">
      <div class="brand-hex">
        <div class="brand-hex-inner">BNB</div>
      </div>
      <div class="brand-info">
        <strong>SMK BANGUN NUSA BANGSA <span class="badge-ed">PORTAL</span></strong>
      </div>
    </a>

    <div class="user-profile-menu">
      <div class="user-badge-card">
        <img src="<?= e($userAvatar) ?>" alt="<?= e($userName) ?>" class="avatar-img">
        <div class="user-meta-txt">
          <strong><?= e($userName) ?></strong>
          <small><?= e($userRoleLabel) ?> · <?= e($userClass) ?></small>
        </div>
      </div>
      <a href="logout.php" class="btn-logout" title="Keluar dari sesi portal">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Keluar
      </a>
    </div>
  </header>

  <!-- MAIN DASHBOARD CONTENT -->
  <main class="dashboard-container">
    <!-- GREETING BANNER -->
    <section class="dash-hero-banner">
      <div class="banner-left">
        <h1>Selamat Datang, <?= e($userName) ?>! 👋</h1>
        <p><?= e($userDept) ?> · Terhubung dengan Sesi Terenkripsi Kriptografis</p>
      </div>
      <div class="banner-right-stats">
        <div class="status-chip">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
          Sesi Otentikasi: Aktif & Terverifikasi
        </div>
      </div>
    </section>

    <!-- ASSET METRICS GRID (CRYPTO PORTFOLIO STYLE) -->
    <section class="asset-metrics-grid">
      <div class="metric-card">
        <div class="card-label-top">
          <span>Poin Vokasi (BNB-Token)</span>
          <span style="color: var(--idx-cyan);">🪙</span>
        </div>
        <div class="card-val-big"><?= number_format($userTokens, 0, ',', '.') ?> <span style="font-size: 0.9rem; color: var(--idx-cyan);">BNB</span></div>
        <div class="card-sub-val">
          <span>≈ Rp <?= e($totalEstimatedIdr) ?></span>
          <span style="color: var(--idx-green); margin-left: auto;">▲ +14.2%</span>
        </div>
      </div>

      <div class="metric-card">
        <div class="card-label-top">
          <span>Indeks Prestasi (IPK)</span>
          <span style="color: var(--idx-gold);">⭐</span>
        </div>
        <div class="card-val-big"><?= e($userGpa) ?> <span style="font-size: 0.85rem; color: var(--idx-gold);">/ 4.00</span></div>
        <div class="card-sub-val">
          <span style="color: var(--idx-gold);">Status: Diamond Tier</span>
          <span style="color: var(--idx-green); margin-left: auto;">▲ Cumlaude</span>
        </div>
      </div>

      <div class="metric-card">
        <div class="card-label-top">
          <span>Presensi RFID & Wajah</span>
          <span style="color: var(--idx-green);">📡</span>
        </div>
        <div class="card-val-big"><?= e($userAttendance) ?></div>
        <div class="card-sub-val">
          <span>78 / 79 Hari Hadir</span>
          <span style="color: var(--idx-green); margin-left: auto;">Tepat Waktu</span>
        </div>
      </div>

      <div class="metric-card">
        <div class="card-label-top">
          <span>Sertifikasi Kompetensi</span>
          <span style="color: var(--idx-neon);">📜</span>
        </div>
        <div class="card-val-big">14 <span style="font-size: 0.85rem; color: var(--idx-text-muted);">/ 16 Modul</span></div>
        <div class="card-sub-val">
          <span>Terverifikasi BNSP</span>
          <span style="color: var(--idx-cyan); margin-left: auto;">87.5% Selesai</span>
        </div>
      </div>
    </section>

    <!-- 2 COLUMN SPLIT -->
    <div class="dashboard-grid-2col">
      <!-- LEFT: RECENT ACTIVITY & SCHEDULE LEDGER -->
      <section class="dash-panel">
        <div class="panel-head">
          <h2>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--idx-cyan)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Jadwal Praktik & Ledger Aktivitas Terverifikasi
          </h2>
          <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--idx-text-muted);">REALTIME SYNC</span>
        </div>

        <table class="crypto-ledger-table">
          <thead>
            <tr>
              <th>Waktu / Modul</th>
              <th>Instruktur / Pengajar</th>
              <th>Ruang / Lab</th>
              <th>Status Blok</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong style="color: #fff; display: block;">Pemrograman Web Lanjut</strong>
                <small style="color: var(--idx-text-muted);">08:00 - 10:15 WIB</small>
              </td>
              <td>Budi Santoso, S.Kom.</td>
              <td>Lab Komputer 03</td>
              <td><span class="badge-status done">✓ Hadir & Selesai</span></td>
            </tr>
            <tr>
              <td>
                <strong style="color: #fff; display: block;">Basis Data & Keamanan Kripto</strong>
                <small style="color: var(--idx-text-muted);">10:30 - 12:45 WIB</small>
              </td>
              <td>Dewi Sartika, M.T.</td>
              <td>Lab Rekayasa AI</td>
              <td><span class="badge-status process">⚡ Sedang Berlangsung</span></td>
            </tr>
            <tr>
              <td>
                <strong style="color: #fff; display: block;">Proyek Vokasi Kolaboratif</strong>
                <small style="color: var(--idx-text-muted);">13:30 - 15:30 WIB</small>
              </td>
              <td>Tim Industri Partner</td>
              <td>Innovation Hub</td>
              <td><span class="badge-status" style="background: rgba(255,255,255,0.06); color: #94a3b8;">Menunggu Sesi</span></td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- RIGHT: QUICK ACTIONS & SECURITY STATUS -->
      <section class="dash-panel">
        <div class="panel-head">
          <h2>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--idx-cyan)" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Aksi Cepat Portal
          </h2>
        </div>

        <div class="quick-actions-list">
          <a href="javascript:void(0)" class="action-btn-item" onclick="alert('Presensi Digital Berhasil Divalidasi!')">
            <div class="action-left">
              <div class="action-icon-box">📲</div>
              <div class="action-titles">
                <strong>Presensi Digital RFID / QR</strong>
                <small>Check-in instan ruang kelas & bengkel</small>
              </div>
            </div>
            <span>→</span>
          </a>

          <a href="javascript:void(0)" class="action-btn-item" onclick="alert('e-Rapor & Transkrip Vokasi siap diunduh dalam format PDF terenkripsi!')">
            <div class="action-left">
              <div class="action-icon-box">📊</div>
              <div class="action-titles">
                <strong>e-Rapor & Transkrip Digital</strong>
                <small>Unduh sertifikat nilai bertanda tangan digital</small>
              </div>
            </div>
            <span>→</span>
          </a>

          <a href="javascript:void(0)" class="action-btn-item" onclick="alert('Pengaturan 2FA Google Authenticator telah aktif pada akun ini.')">
            <div class="action-left">
              <div class="action-icon-box">🛡️</div>
              <div class="action-titles">
                <strong>Keamanan 2FA & Kunci Sesi</strong>
                <small>Pengaturan keamanan standar exchange</small>
              </div>
            </div>
            <span>→</span>
          </a>

          <a href="index.php" class="action-btn-item">
            <div class="action-left">
              <div class="action-icon-box">🌐</div>
              <div class="action-titles">
                <strong>Halaman Beranda SMK BNB</strong>
                <small>Jelajahi portal publik & profil sekolah</small>
              </div>
            </div>
            <span>→</span>
          </a>
        </div>
      </section>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="dash-footer">
    © <?= date('Y') ?> SMK Bangun Nusa Bangsa · Sistem Terintegrasi Arsitektur Indodax Fintech Security
  </footer>
</body>
</html>
