<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
require_once __DIR__ . '/config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

$loginError = null;
$loginSuccess = null;

if (isset($_GET['logout'])) {
    $loginSuccess = 'Sesi Anda telah berhasil diakhiri secara aman.';
}

// Built-in Demo accounts (ensures instant testing even before database import)
$demoUsers = [
    'siswa@smkbnb.sch.id' => [
        'id' => 1,
        'username' => '202401001',
        'email' => 'siswa@smkbnb.sch.id',
        'password_hash' => '$2y$12$EsTgTqUKKu51ob4b9wKyqe4LC.XolvvAleL9UEgsJfNjBJ.bkn20u', // siswa123
        'name' => 'Ahmad Rizky Pratama',
        'role' => 'siswa',
        'role_label' => 'Siswa / Taruna Vokasi',
        'department' => 'Rekayasa Perangkat Lunak (RPL)',
        'class' => 'XII RPL 1',
        'academic_tokens' => 2850,
        'gpa' => '3.92',
        'attendance' => '98.8%',
        'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'
    ],
    'guru@smkbnb.sch.id' => [
        'id' => 2,
        'username' => '198503152010011002',
        'email' => 'guru@smkbnb.sch.id',
        'password_hash' => '$2y$12$XJ.Lqd8vqogbhwI5sq0MIuigEmgyFrLdnrgTPiKIZ45Wuk9fod8aK', // guru123
        'name' => 'Budi Santoso, S.Kom., M.T.',
        'role' => 'guru',
        'role_label' => 'Instruktur Vokasi Senior',
        'department' => 'Keahlian Rekayasa Perangkat Lunak & AI',
        'class' => 'Guru Mata Pelajaran Pemrograman Web',
        'academic_tokens' => 8420,
        'gpa' => 'Pendidik Teladan',
        'attendance' => '99.5%',
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80'
    ],
    'admin@smkbnb.sch.id' => [
        'id' => 3,
        'username' => 'admin',
        'email' => 'admin@smkbnb.sch.id',
        'password_hash' => '$2y$12$AjgI7uKvIgpbZ6Z79xzIN.5QAI4j1CoiW9r1d6V.30Fdyyb1BtPbi', // admin123
        'name' => 'Super Administrator Portal',
        'role' => 'admin',
        'role_label' => 'System & Network Administrator',
        'department' => 'Biro Sistem Informasi & Tata Usaha',
        'class' => 'Level 5 Access Granted',
        'academic_tokens' => 50000,
        'gpa' => 'Verified Host',
        'attendance' => '100%',
        'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=80'
    ]
];

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $selectedRole = trim((string) ($_POST['role'] ?? 'siswa'));
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $securityPassed = isset($_POST['security_verified']) && $_POST['security_verified'] === '1';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $loginError = 'Sesi formulir kadaluarsa. Silakan muat ulang halaman.';
    } elseif ($identifier === '' || $password === '') {
        $loginError = 'Harap masukkan Identitas (Email/NISN/NIP) dan Kata Sandi Anda.';
    } elseif (!$securityPassed) {
        $loginError = 'Harap selesaikan verifikasi keamanan kriptografi sebelum masuk.';
    } else {
        $authenticatedUser = null;

        // Try database authentication first if available
        try {
            $pdo = dbConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE (email = :id1 OR username = :id2) LIMIT 1');
            $stmt->execute(['id1' => $identifier, 'id2' => $identifier]);
            $userRow = $stmt->fetch();

            if ($userRow && password_verify($password, $userRow['password'])) {
                if ($userRow['role'] === $selectedRole || $userRow['role'] === 'admin') {
                    $authenticatedUser = [
                        'id' => (int) $userRow['id'],
                        'username' => $userRow['username'],
                        'email' => $userRow['email'],
                        'name' => $userRow['full_name'],
                        'role' => $userRow['role'],
                        'role_label' => ucfirst($userRow['role']),
                        'department' => $userRow['major'] ?? 'Teknologi & Vokasi',
                        'class' => $userRow['class_name'] ?? 'Kelas Terdaftar',
                        'academic_tokens' => (int) ($userRow['academic_tokens'] ?? 1000),
                        'gpa' => $userRow['gpa'] ?? '3.85',
                        'attendance' => '98.5%',
                        'avatar' => $userRow['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=80'
                    ];
                } else {
                    $loginError = 'Akun terdaftar tidak memiliki akses sebagai ' . ucfirst($selectedRole) . '. Periksa tab role yang dipilih.';
                }
            }
        } catch (PDOException $ex) {
            // Database not connected or table not created yet; fall back seamlessly to demo accounts
        }

        // If not authenticated through database, check demo accounts
        if ($authenticatedUser === null && $loginError === null) {
            foreach ($demoUsers as $demoKey => $user) {
                if (($identifier === $user['email'] || $identifier === $user['username']) && password_verify($password, $user['password_hash'])) {
                    if ($user['role'] === $selectedRole || $user['role'] === 'admin') {
                        $authenticatedUser = $user;
                        break;
                    } else {
                        $loginError = 'Akun terdaftar sebagai ' . strtoupper($user['role']) . '. Silakan pilih tab ' . strtoupper($user['role']) . ' di atas.';
                        break;
                    }
                }
            }
        }

        if ($authenticatedUser !== null) {
            // Set session variables
            $_SESSION['user_id'] = $authenticatedUser['id'];
            $_SESSION['user_name'] = $authenticatedUser['name'];
            $_SESSION['user_email'] = $authenticatedUser['email'];
            $_SESSION['user_role'] = $authenticatedUser['role'];
            $_SESSION['user_role_label'] = $authenticatedUser['role_label'];
            $_SESSION['user_department'] = $authenticatedUser['department'];
            $_SESSION['user_class'] = $authenticatedUser['class'];
            $_SESSION['user_tokens'] = $authenticatedUser['academic_tokens'];
            $_SESSION['user_gpa'] = $authenticatedUser['gpa'];
            $_SESSION['user_attendance'] = $authenticatedUser['attendance'];
            $_SESSION['user_avatar'] = $authenticatedUser['avatar'];
            $_SESSION['login_time'] = time();

            header('Location: dashboard.php');
            exit;
        } elseif ($loginError === null) {
            $loginError = 'Kombinasi email/identitas atau kata sandi tidak cocok. Gunakan tombol demo di bawah untuk mencoba instan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Masuk — SMK Bangun Nusa Bangsa | Indodax Edition</title>
  <meta name="description" content="Portal Masuk Resmi SMK Bangun Nusa Bangsa dengan arsitektur visual dan keamanan tingkat tinggi terinspirasi dari Indodax.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      /* INDODAX Signature Color Palette */
      --idx-dark-900: #07090e;
      --idx-dark-800: #0b0f19;
      --idx-dark-700: #101626;
      --idx-dark-600: #162035;
      --idx-dark-500: #1f2d47;
      
      --idx-primary: #0066f5;       /* Indodax Signature Blue */
      --idx-cyan: #00c2ff;          /* Indodax Electric Cyan */
      --idx-neon: #00f0ff;          /* Glow accent */
      --idx-green: #0ecb81;         /* Crypto Bull Green */
      --idx-red: #f6465d;           /* Crypto Bear Red */
      --idx-gold: #f0b90b;          /* VIP Gold */
      
      --idx-text-high: #f8fafc;
      --idx-text-mid: #94a3b8;
      --idx-text-muted: #64748b;
      
      --idx-border: rgba(0, 194, 255, 0.14);
      --idx-border-hover: rgba(0, 194, 255, 0.45);
      --idx-card-bg: rgba(14, 21, 37, 0.78);
      --idx-glass: rgba(16, 25, 45, 0.65);
      
      --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --font-display: 'Space Grotesk', sans-serif;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      min-height: 100%;
      background-color: var(--idx-dark-900);
      color: var(--idx-text-high);
      font-family: var(--font-main);
      overflow-x: hidden;
      font-size: 14px;
    }

    /* Ambient background gradient meshes */
    body::before {
      content: '';
      position: fixed;
      top: -20%;
      left: -10%;
      width: 70vw;
      height: 70vw;
      background: radial-gradient(circle, rgba(0, 102, 245, 0.16) 0%, rgba(0, 194, 255, 0.05) 45%, transparent 70%);
      filter: blur(80px);
      z-index: 0;
      pointer-events: none;
      animation: pulseAura 18s ease-in-out infinite alternate;
    }

    body::after {
      content: '';
      position: fixed;
      bottom: -20%;
      right: -10%;
      width: 65vw;
      height: 65vw;
      background: radial-gradient(circle, rgba(14, 203, 129, 0.12) 0%, rgba(0, 102, 245, 0.08) 50%, transparent 75%);
      filter: blur(90px);
      z-index: 0;
      pointer-events: none;
      animation: pulseAura 22s ease-in-out infinite alternate-reverse;
    }

    @keyframes pulseAura {
      0% { transform: scale(1) translate(0, 0); opacity: 0.8; }
      50% { transform: scale(1.12) translate(3%, 4%); opacity: 1; }
      100% { transform: scale(0.95) translate(-2%, -3%); opacity: 0.85; }
    }

    /* Dynamic Interactive Canvas */
    #matrix-canvas {
      position: fixed;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      opacity: 0.65;
    }

    .app-wrapper {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* TOP LIVE CRYPTO TICKER (Indodax Market Bar) */
    .crypto-ticker-bar {
      background: rgba(7, 10, 17, 0.92);
      border-bottom: 1px solid rgba(0, 194, 255, 0.12);
      backdrop-filter: blur(12px);
      padding: 7px 0;
      font-family: var(--font-mono);
      font-size: 0.78rem;
      overflow: hidden;
      white-space: nowrap;
      position: relative;
    }

    .ticker-content {
      display: inline-flex;
      gap: 36px;
      animation: tickerScroll 42s linear infinite;
    }

    .ticker-content:hover {
      animation-play-state: paused;
    }

    @keyframes tickerScroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    .ticker-pair {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .ticker-pair .symbol {
      font-weight: 700;
      color: #e2e8f0;
    }

    .ticker-pair .price {
      color: #94a3b8;
    }

    .ticker-pair .change-up {
      color: var(--idx-green);
      display: inline-flex;
      align-items: center;
      gap: 2px;
      font-weight: 600;
      background: rgba(14, 203, 129, 0.1);
      padding: 1px 6px;
      border-radius: 4px;
    }

    .ticker-pair .change-bnb {
      color: var(--idx-cyan);
      display: inline-flex;
      align-items: center;
      gap: 2px;
      font-weight: 700;
      background: rgba(0, 194, 255, 0.12);
      border: 1px solid rgba(0, 194, 255, 0.3);
      padding: 1px 8px;
      border-radius: 4px;
      text-shadow: 0 0 10px rgba(0, 194, 255, 0.5);
    }

    /* NAVIGATION HEADER */
    .portal-nav {
      padding: 16px 36px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      background: rgba(11, 15, 25, 0.65);
      backdrop-filter: blur(16px);
    }

    .brand-logo-group {
      display: flex;
      align-items: center;
      gap: 14px;
      text-decoration: none;
    }

    .brand-hex-icon {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, #0066f5, #00f0ff);
      clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%);
      display: grid;
      place-items: center;
      position: relative;
      box-shadow: 0 0 24px rgba(0, 194, 255, 0.45);
      animation: hexGlow 4s ease-in-out infinite alternate;
    }

    @keyframes hexGlow {
      0% { filter: drop-shadow(0 0 8px rgba(0, 102, 245, 0.6)); }
      100% { filter: drop-shadow(0 0 18px rgba(0, 240, 255, 0.85)); }
    }

    .brand-hex-inner {
      width: 38px;
      height: 38px;
      background: var(--idx-dark-800);
      clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%);
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 0.95rem;
      color: var(--idx-neon);
      font-family: var(--font-display);
      letter-spacing: -0.5px;
    }

    .brand-text h1 {
      font-size: 1.05rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.2;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .badge-indodax-style {
      background: linear-gradient(90deg, #0066f5, #00c2ff);
      color: #fff;
      font-size: 0.62rem;
      font-weight: 800;
      padding: 2px 7px;
      border-radius: 999px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      box-shadow: 0 0 12px rgba(0, 194, 255, 0.35);
    }

    .brand-text p {
      font-size: 0.72rem;
      color: var(--idx-text-mid);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .system-status {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.75rem;
      color: var(--idx-text-mid);
      background: rgba(14, 203, 129, 0.08);
      border: 1px solid rgba(14, 203, 129, 0.25);
      padding: 5px 12px;
      border-radius: 999px;
    }

    .pulse-dot {
      width: 7px;
      height: 7px;
      background-color: var(--idx-green);
      border-radius: 50%;
      box-shadow: 0 0 10px var(--idx-green);
      animation: pulseLight 2s infinite;
    }

    @keyframes pulseLight {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(1.3); }
    }

    .btn-ghost-nav {
      color: var(--idx-text-mid);
      text-decoration: none;
      font-size: 0.8rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.2s ease;
    }

    .btn-ghost-nav:hover {
      color: #fff;
      border-color: var(--idx-cyan);
      background: rgba(0, 194, 255, 0.08);
      box-shadow: 0 0 14px rgba(0, 194, 255, 0.2);
    }

    /* MAIN SPLIT CONTAINER */
    .portal-main {
      flex: 1;
      display: grid;
      grid-template-columns: 1.15fr 0.95fr;
      gap: 48px;
      max-width: 1320px;
      width: calc(100% - 64px);
      margin: 36px auto;
      align-items: center;
    }

    /* LEFT SIDE: SHOWCASE & CRYPTO-FINTECH VIBES */
    .showcase-pane {
      display: flex;
      flex-direction: column;
      gap: 24px;
      padding-right: 20px;
    }

    .exclusive-tag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(90deg, rgba(0, 102, 245, 0.2), rgba(0, 240, 255, 0.06));
      border: 1px solid rgba(0, 194, 255, 0.35);
      border-radius: 999px;
      padding: 6px 16px;
      width: fit-content;
      font-size: 0.74rem;
      font-weight: 700;
      color: var(--idx-neon);
      letter-spacing: 0.05em;
      text-transform: uppercase;
      box-shadow: 0 0 20px rgba(0, 194, 255, 0.15);
    }

    .showcase-headline {
      font-family: var(--font-display);
      font-size: clamp(2.4rem, 4vw, 3.4rem);
      font-weight: 700;
      line-height: 1.1;
      letter-spacing: -0.04em;
    }

    .gradient-text-cyan {
      background: linear-gradient(135deg, #ffffff 10%, #00f0ff 65%, #0066f5 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .showcase-desc {
      color: var(--idx-text-mid);
      font-size: 1.05rem;
      line-height: 1.65;
      max-width: 580px;
    }

    /* TRADING-STYLE ANALYTICS CARD */
    .trading-chart-card {
      background: linear-gradient(145deg, rgba(16, 25, 45, 0.9), rgba(11, 15, 25, 0.95));
      border: 1px solid rgba(0, 194, 255, 0.25);
      border-radius: 20px;
      padding: 22px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 102, 245, 0.12);
      backdrop-filter: blur(20px);
    }

    .trading-chart-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--idx-neon), transparent);
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .chart-title-group {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .coin-avatar-bnb {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0066f5, #00c2ff);
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 0.72rem;
      color: #fff;
      box-shadow: 0 0 12px rgba(0, 194, 255, 0.5);
    }

    .chart-name strong {
      display: block;
      font-size: 0.92rem;
      font-weight: 700;
      color: #fff;
    }

    .chart-name small {
      color: var(--idx-text-muted);
      font-size: 0.7rem;
    }

    .chart-price-box {
      text-align: right;
      font-family: var(--font-mono);
    }

    .chart-price-box .price-now {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--idx-green);
    }

    .chart-price-box .price-change {
      font-size: 0.75rem;
      color: var(--idx-green);
      font-weight: 600;
    }

    /* Live Canvas for Candlestick / Neon Chart */
    .canvas-container {
      position: relative;
      width: 100%;
      height: 130px;
      margin: 12px 0;
    }

    #trading-sparkline {
      width: 100%;
      height: 100%;
      display: block;
    }

    .chart-meta-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      font-family: var(--font-mono);
      font-size: 0.74rem;
    }

    .chart-meta-item span {
      display: block;
      color: var(--idx-text-muted);
      font-size: 0.68rem;
      margin-bottom: 2px;
    }

    .chart-meta-item strong {
      color: #e2e8f0;
      font-weight: 600;
    }

    /* SECURITY HIGHLIGHTS GRID */
    .security-badges-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }

    .sec-badge-item {
      background: rgba(16, 25, 45, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 14px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.25s ease;
    }

    .sec-badge-item:hover {
      border-color: rgba(0, 194, 255, 0.35);
      background: rgba(0, 194, 255, 0.06);
      transform: translateY(-2px);
    }

    .sec-icon {
      font-size: 1.25rem;
      color: var(--idx-cyan);
      display: grid;
      place-items: center;
    }

    .sec-text strong {
      display: block;
      font-size: 0.8rem;
      color: #f1f5f9;
    }

    .sec-text small {
      display: block;
      font-size: 0.68rem;
      color: var(--idx-text-mid);
    }

    /* RIGHT SIDE: INDODAX HIGH-TECH LOGIN CARD */
    .login-pane {
      position: relative;
    }

    .login-glass-card {
      position: relative;
      background: var(--idx-card-bg);
      border: 1px solid var(--idx-border);
      border-radius: 26px;
      padding: 36px 36px 30px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(0, 102, 245, 0.18);
      backdrop-filter: blur(28px);
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }

    .login-glass-card:hover {
      border-color: var(--idx-border-hover);
      box-shadow: 0 30px 70px rgba(0, 0, 0, 0.65), 0 0 50px rgba(0, 194, 255, 0.25);
    }

    /* Glowing animated border shimmer line */
    .card-border-glow {
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: conic-gradient(transparent, transparent, var(--idx-cyan), transparent);
      animation: rotateGlow 8s linear infinite;
      z-index: 0;
      opacity: 0.2;
      pointer-events: none;
    }

    @keyframes rotateGlow {
      100% { transform: rotate(360deg); }
    }

    .card-inner-content {
      position: relative;
      z-index: 1;
    }

    .card-header-area {
      margin-bottom: 24px;
    }

    .card-header-area h2 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #ffffff;
      font-family: var(--font-display);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card-header-area p {
      color: var(--idx-text-mid);
      font-size: 0.85rem;
    }

    /* ROLE SELECTOR SEGMENTED CONTROL */
    .role-segmented-box {
      margin-bottom: 22px;
    }

    .role-label-title {
      display: block;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--idx-text-mid);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 8px;
    }

    .role-switch-pills {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 6px;
      background: rgba(11, 15, 25, 0.85);
      padding: 4px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .role-pill-btn {
      background: transparent;
      border: none;
      outline: none;
      color: var(--idx-text-mid);
      padding: 9px 10px;
      border-radius: 9px;
      font-family: var(--font-main);
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .role-pill-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.05);
    }

    .role-pill-btn.active {
      background: linear-gradient(135deg, var(--idx-primary), var(--idx-cyan));
      color: #ffffff;
      box-shadow: 0 4px 16px rgba(0, 102, 245, 0.4);
    }

    /* TAB METHOD (FORM vs QR CODE) */
    .auth-tab-bar {
      display: flex;
      border-bottom: 1px solid rgba(255, 255, 255, 0.09);
      margin-bottom: 22px;
      gap: 20px;
    }

    .auth-tab-item {
      background: transparent;
      border: none;
      outline: none;
      padding: 10px 4px;
      color: var(--idx-text-mid);
      font-family: var(--font-main);
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      position: relative;
      transition: color 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .auth-tab-item:hover {
      color: #ffffff;
    }

    .auth-tab-item.active {
      color: var(--idx-neon);
    }

    .auth-tab-item.active::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      right: 0;
      height: 2px;
      background: var(--idx-cyan);
      box-shadow: 0 0 10px var(--idx-cyan);
    }

    /* ALERTS */
    .alert-box {
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 18px;
      font-size: 0.82rem;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      animation: fadeInAlert 0.3s ease;
    }

    @keyframes fadeInAlert {
      from { opacity: 0; transform: translateY(-6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .alert-box.error {
      background: rgba(246, 70, 93, 0.12);
      border: 1px solid rgba(246, 70, 93, 0.35);
      color: #fca5a5;
    }

    .alert-box.success {
      background: rgba(14, 203, 129, 0.12);
      border: 1px solid rgba(14, 203, 129, 0.35);
      color: #86efac;
    }

    /* FORM STYLING */
    .form-group {
      margin-bottom: 18px;
      position: relative;
    }

    .form-label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 7px;
    }

    .form-label-row label {
      font-size: 0.78rem;
      font-weight: 600;
      color: #cbd5e1;
    }

    .forgot-link {
      color: var(--idx-cyan);
      font-size: 0.76rem;
      text-decoration: none;
      transition: color 0.2s;
    }

    .forgot-link:hover {
      color: var(--idx-neon);
      text-decoration: underline;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon-left {
      position: absolute;
      left: 14px;
      color: var(--idx-text-muted);
      font-size: 1rem;
      pointer-events: none;
      transition: color 0.2s ease;
    }

    .input-control {
      width: 100%;
      background: rgba(11, 15, 25, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      padding: 13px 44px 13px 42px;
      color: #ffffff;
      font-family: var(--font-main);
      font-size: 0.88rem;
      outline: none;
      transition: all 0.25s ease;
    }

    .input-control:focus {
      border-color: var(--idx-cyan);
      background: rgba(16, 25, 45, 0.95);
      box-shadow: 0 0 0 3px rgba(0, 194, 255, 0.18), 0 0 20px rgba(0, 194, 255, 0.1);
    }

    .input-control:focus ~ .input-icon-left {
      color: var(--idx-cyan);
    }

    .toggle-pass-btn {
      position: absolute;
      right: 14px;
      background: none;
      border: none;
      color: var(--idx-text-muted);
      cursor: pointer;
      font-size: 1rem;
      padding: 4px;
      transition: color 0.2s;
      outline: none;
    }

    .toggle-pass-btn:hover {
      color: var(--idx-cyan);
    }

    /* PASSWORD STRENGTH METER */
    .pass-strength-bar {
      height: 4px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 999px;
      margin-top: 8px;
      overflow: hidden;
      display: flex;
      gap: 3px;
    }

    .strength-segment {
      flex: 1;
      height: 100%;
      background: transparent;
      transition: background 0.3s ease;
      border-radius: 999px;
    }

    .strength-hint {
      display: flex;
      justify-content: space-between;
      margin-top: 5px;
      font-size: 0.68rem;
      color: var(--idx-text-muted);
    }

    /* CRYPTO-STYLE SECURITY SLIDER VERIFICATION */
    .crypto-verify-box {
      background: rgba(11, 15, 25, 0.85);
      border: 1px dashed rgba(0, 194, 255, 0.3);
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      user-select: none;
      transition: all 0.25s ease;
    }

    .crypto-verify-box:hover {
      border-color: var(--idx-cyan);
      background: rgba(0, 194, 255, 0.04);
    }

    .crypto-verify-box.verified {
      border: 1px solid rgba(14, 203, 129, 0.5);
      background: rgba(14, 203, 129, 0.08);
    }

    .verify-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .verify-checkbox-custom {
      width: 22px;
      height: 22px;
      border: 2px solid rgba(0, 194, 255, 0.4);
      border-radius: 6px;
      display: grid;
      place-items: center;
      color: transparent;
      transition: all 0.2s ease;
      background: rgba(16, 25, 45, 0.6);
    }

    .crypto-verify-box.verified .verify-checkbox-custom {
      background: var(--idx-green);
      border-color: var(--idx-green);
      color: #07090e;
      box-shadow: 0 0 10px rgba(14, 203, 129, 0.5);
    }

    .verify-text-info strong {
      display: block;
      font-size: 0.78rem;
      color: #e2e8f0;
    }

    .verify-text-info small {
      font-size: 0.68rem;
      color: var(--idx-text-mid);
    }

    .verify-shield-badge {
      font-size: 1.15rem;
      color: var(--idx-cyan);
      filter: drop-shadow(0 0 6px rgba(0, 194, 255, 0.4));
    }

    /* SUBMIT BUTTON WITH INDODAX GRADIENT GLOW */
    .btn-login-submit {
      width: 100%;
      background: linear-gradient(135deg, #0066f5 0%, #00c2ff 100%);
      color: #ffffff;
      border: none;
      border-radius: 13px;
      padding: 15px 20px;
      font-family: var(--font-main);
      font-size: 0.94rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0, 102, 245, 0.35), 0 0 20px rgba(0, 194, 255, 0.2);
      transition: all 0.25s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-login-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 32px rgba(0, 102, 245, 0.45), 0 0 30px rgba(0, 194, 255, 0.35);
      background: linear-gradient(135deg, #0570ff 0%, #0ce2ff 100%);
    }

    .btn-login-submit:active {
      transform: translateY(0);
    }

    /* Shimmer wipe over button */
    .btn-login-submit::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 60%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transform: skewX(-25deg);
      animation: btnShimmer 4s infinite;
    }

    @keyframes btnShimmer {
      0% { left: -100%; }
      30%, 100% { left: 160%; }
    }

    /* 1-CLICK INSTANT DEMO ACCOUNTS BAR */
    .instant-demo-area {
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .demo-bar-title {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--idx-text-muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .demo-bar-title span {
      color: var(--idx-gold);
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .demo-buttons-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
    }

    .btn-demo-fill {
      background: rgba(16, 25, 45, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #e2e8f0;
      padding: 8px 10px;
      border-radius: 9px;
      font-size: 0.72rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
      transition: all 0.2s ease;
    }

    .btn-demo-fill small {
      color: var(--idx-text-mid);
      font-size: 0.64rem;
      font-family: var(--font-mono);
    }

    .btn-demo-fill:hover {
      background: rgba(0, 194, 255, 0.12);
      border-color: var(--idx-cyan);
      color: #fff;
      transform: translateY(-2px);
    }

    /* QR CODE TAB CONTENT */
    .qr-container {
      display: none;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding: 10px 0 16px;
    }

    .qr-container.active {
      display: flex;
    }

    .qr-frame {
      position: relative;
      width: 190px;
      height: 190px;
      background: #ffffff;
      padding: 12px;
      border-radius: 18px;
      box-shadow: 0 0 35px rgba(0, 194, 255, 0.35);
      margin-bottom: 18px;
      overflow: hidden;
    }

    .qr-code-img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    /* Animated scanning laser line */
    .qr-laser-scanner {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, transparent, #0066f5, #00f0ff, transparent);
      box-shadow: 0 0 14px 2px rgba(0, 240, 255, 0.9);
      animation: laserMove 2.6s ease-in-out infinite;
    }

    @keyframes laserMove {
      0% { top: 4%; }
      50% { top: 92%; }
      100% { top: 4%; }
    }

    .qr-instructions strong {
      display: block;
      color: #ffffff;
      font-size: 0.92rem;
      margin-bottom: 4px;
    }

    .qr-instructions p {
      color: var(--idx-text-mid);
      font-size: 0.78rem;
      max-width: 280px;
      line-height: 1.45;
      margin-bottom: 14px;
    }

    .btn-simulate-qr {
      background: rgba(0, 194, 255, 0.12);
      border: 1px solid var(--idx-cyan);
      color: var(--idx-cyan);
      font-size: 0.76rem;
      font-weight: 700;
      padding: 7px 16px;
      border-radius: 999px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-simulate-qr:hover {
      background: var(--idx-cyan);
      color: #07090e;
      box-shadow: 0 0 15px rgba(0, 194, 255, 0.5);
    }

    /* FOOTER REGULATORY & CERTIFICATION BADGES */
    .portal-footer {
      padding: 24px 36px 30px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      background: rgba(7, 10, 17, 0.95);
      margin-top: auto;
    }

    .footer-inner-box {
      max-width: 1320px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      color: var(--idx-text-muted);
      font-size: 0.75rem;
    }

    .compliance-badges {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }

    .comp-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      padding: 4px 10px;
      border-radius: 6px;
      font-family: var(--font-mono);
      font-size: 0.68rem;
      color: #94a3b8;
    }

    .comp-pill span {
      color: var(--idx-cyan);
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 1024px) {
      .portal-main {
        grid-template-columns: 1fr;
        gap: 36px;
      }

      .showcase-pane {
        padding-right: 0;
        text-align: center;
        align-items: center;
      }

      .exclusive-tag {
        margin: 0 auto;
      }

      .security-badges-row {
        width: 100%;
      }

      .login-pane {
        max-width: 520px;
        margin: 0 auto;
        width: 100%;
      }
    }

    @media (max-width: 640px) {
      .portal-nav {
        padding: 12px 20px;
      }

      .system-status {
        display: none;
      }

      .portal-main {
        width: calc(100% - 32px);
        margin: 20px auto;
      }

      .login-glass-card {
        padding: 24px 20px;
      }

      .security-badges-row {
        grid-template-columns: 1fr;
      }

      .demo-buttons-grid {
        grid-template-columns: 1fr;
      }

      .footer-inner-box {
        flex-direction: column;
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <!-- Dynamic Particle Neural Network Background -->
  <canvas id="matrix-canvas"></canvas>

  <div class="app-wrapper">
    <!-- TOP REAL-TIME CRYPTO & ACADEMIC TICKER (Indodax Market Bar) -->
    <aside class="crypto-ticker-bar" aria-label="Indodax Live Market Ticker">
      <div class="ticker-content" id="ticker-strip">
        <div class="ticker-pair">
          <span class="symbol">BNB-SMK/IDR</span>
          <span class="price">Rp 12.850</span>
          <span class="change-bnb">▲ +8.75%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">BTC/IDR</span>
          <span class="price">Rp 1.542.180.000</span>
          <span class="change-up">▲ +3.42%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">ETH/IDR</span>
          <span class="price">Rp 54.120.000</span>
          <span class="change-up">▲ +2.18%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">USDT/IDR</span>
          <span class="price">Rp 16.245</span>
          <span class="change-up">▲ +0.02%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">SOL/IDR</span>
          <span class="price">Rp 3.250.000</span>
          <span class="change-up">▲ +5.60%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">PRESTASI-INDEX</span>
          <span class="price">3.92 / 4.00</span>
          <span class="change-bnb">▲ +98.8% EXCELLENT</span>
        </div>
        <!-- Duplicated for continuous marquee scroll -->
        <div class="ticker-pair">
          <span class="symbol">BNB-SMK/IDR</span>
          <span class="price">Rp 12.850</span>
          <span class="change-bnb">▲ +8.75%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">BTC/IDR</span>
          <span class="price">Rp 1.542.180.000</span>
          <span class="change-up">▲ +3.42%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">ETH/IDR</span>
          <span class="price">Rp 54.120.000</span>
          <span class="change-up">▲ +2.18%</span>
        </div>
        <div class="ticker-pair">
          <span class="symbol">USDT/IDR</span>
          <span class="price">Rp 16.245</span>
          <span class="change-up">▲ +0.02%</span>
        </div>
      </div>
    </aside>

    <!-- NAVIGATION HEADER -->
    <header class="portal-nav">
      <a href="index.php" class="brand-logo-group" title="Kembali ke Beranda SMK Bangun Nusa Bangsa">
        <div class="brand-hex-icon">
          <div class="brand-hex-inner">BNB</div>
        </div>
        <div class="brand-text">
          <h1>SMK BANGUN NUSA BANGSA <span class="badge-indodax-style">INDODAX ED.</span></h1>
          <p>Sistem Portal Akademik & Vokasi Terdesentralisasi</p>
        </div>
      </a>

      <div class="nav-actions">
        <div class="system-status">
          <span class="pulse-dot"></span>
          <span>Gateway Security <strong>Active</strong> · Latensi <strong>12ms</strong></span>
        </div>
        <a href="index.php" class="btn-ghost-nav">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Kembali ke Website
        </a>
      </div>
    </header>

    <!-- MAIN BODY -->
    <main class="portal-main">
      <!-- LEFT SHOWCASE SIDE -->
      <section class="showcase-pane">
        <div class="exclusive-tag">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          INDODAX TECH DESIGN · INSTITUSI VOKASI DIGITAL
        </div>

        <h2 class="showcase-headline">
          Akses Masa Depan.<br>
          <span class="gradient-text-cyan">Keamanan Tingkat Tinggi.</span>
        </h2>

        <p class="showcase-desc">
          Masuk ke portal terpadu SMK Bangun Nusa Bangsa untuk mengelola presensi digital, capaian vokasi, portofolio kejuruan, dan nilai prestasi dengan perlindungan berlapis standar exchange nasional.
        </p>

        <!-- LIVE TRADING-STYLE ANALYTICS CARD -->
        <div class="trading-chart-card">
          <div class="chart-header">
            <div class="chart-title-group">
              <div class="coin-avatar-bnb">BNB</div>
              <div class="chart-name">
                <strong>Indeks Aktivitas Vokasi & Prestasi</strong>
                <small>SMK-TOKEN / REALTIME ACTIVITY INDEX</small>
              </div>
            </div>
            <div class="chart-price-box">
              <div class="price-now">98.84 PTS</div>
              <div class="price-change">▲ +14.2% (24 Jam)</div>
            </div>
          </div>

          <!-- Dynamic Canvas Area Chart -->
          <div class="canvas-container">
            <canvas id="trading-sparkline"></canvas>
          </div>

          <div class="chart-meta-row">
            <div class="chart-meta-item">
              <span>TOTAL JAM PRAKTIK</span>
              <strong>142.850 Jam</strong>
            </div>
            <div class="chart-meta-item">
              <span>VALIDASI BLOKCHAIN</span>
              <strong>100% Konsensus</strong>
            </div>
            <div class="chart-meta-item">
              <span>STATUS KEAMANAN</span>
              <strong style="color: var(--idx-green);">ISO/IEC 27001</strong>
            </div>
          </div>
        </div>

        <!-- SECURITY CARDS -->
        <div class="security-badges-row">
          <div class="sec-badge-item">
            <div class="sec-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="sec-text">
              <strong>Enkripsi 256-Bit</strong>
              <small>End-to-End SSL/TLS 1.3</small>
            </div>
          </div>

          <div class="sec-badge-item">
            <div class="sec-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="sec-text">
              <strong>2FA Authenticator</strong>
              <small>Perlindungan Multi-Faktor</small>
            </div>
          </div>

          <div class="sec-badge-item">
            <div class="sec-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div class="sec-text">
              <strong>Terverifikasi Resmi</strong>
              <small>Database SMK BNB Bogor</small>
            </div>
          </div>
        </div>
      </section>

      <!-- RIGHT LOGIN CARD -->
      <section class="login-pane">
        <div class="login-glass-card">
          <div class="card-border-glow"></div>

          <div class="card-inner-content">
            <div class="card-header-area">
              <h2>
                Masuk ke Akun
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--idx-cyan)" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
              </h2>
              <p>Gunakan kredensial resmi terdaftar SMK Bangun Nusa Bangsa</p>
            </div>

            <!-- TAB METODE: KREDENSIAL VS QR CODE -->
            <div class="auth-tab-bar" role="tablist">
              <button class="auth-tab-item active" id="tab-form-btn" type="button" onclick="switchAuthTab('form')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email / Nomor Induk
              </button>
              <button class="auth-tab-item" id="tab-qr-btn" type="button" onclick="switchAuthTab('qr')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Scan QR Code Mobile
              </button>
            </div>

            <!-- ALERTS -->
            <?php if ($loginError !== null): ?>
              <div class="alert-box error" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div><?= e($loginError) ?></div>
              </div>
            <?php endif; ?>

            <?php if ($loginSuccess !== null): ?>
              <div class="alert-box success" role="status">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div><?= e($loginSuccess) ?></div>
              </div>
            <?php endif; ?>

            <!-- FORM CONTAINER -->
            <div id="form-container">
              <form method="POST" action="login.php" id="login-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="role" id="selected-role-input" value="siswa">
                <input type="hidden" name="security_verified" id="security_verified_input" value="0">

                <!-- ROLE SELECTION PILLS -->
                <div class="role-segmented-box">
                  <span class="role-label-title">Pilih Akses Pengguna</span>
                  <div class="role-switch-pills">
                    <button type="button" class="role-pill-btn active" data-role="siswa" onclick="setRole('siswa')">
                      <span>🎓</span> Siswa
                    </button>
                    <button type="button" class="role-pill-btn" data-role="guru" onclick="setRole('guru')">
                      <span>👨‍🏫</span> Guru
                    </button>
                    <button type="button" class="role-pill-btn" data-role="admin" onclick="setRole('admin')">
                      <span>⚙️</span> Admin
                    </button>
                  </div>
                </div>

                <!-- EMAIL / IDENTIFIER INPUT -->
                <div class="form-group">
                  <div class="form-label-row">
                    <label for="identifier-input" id="identifier-label">Email atau NISN Siswa</label>
                  </div>
                  <div class="input-wrapper">
                    <span class="input-icon-left">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" name="identifier" id="identifier-input" class="input-control" placeholder="siswa@smkbnb.sch.id / 202401001" required autocomplete="username">
                  </div>
                </div>

                <!-- PASSWORD INPUT -->
                <div class="form-group">
                  <div class="form-label-row">
                    <label for="password-input">Kata Sandi Akun</label>
                    <a href="javascript:void(0)" class="forgot-link" onclick="openForgotModal()">Lupa kata sandi?</a>
                  </div>
                  <div class="input-wrapper">
                    <span class="input-icon-left">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" name="password" id="password-input" class="input-control" placeholder="Masukkan kata sandi Anda" required autocomplete="current-password" oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pass-btn" onclick="togglePasswordVisibility()" aria-label="Lihat kata sandi">
                      <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                  </div>
                  <div class="pass-strength-bar">
                    <div class="strength-segment" id="str-1"></div>
                    <div class="strength-segment" id="str-2"></div>
                    <div class="strength-segment" id="str-3"></div>
                    <div class="strength-segment" id="str-4"></div>
                  </div>
                  <div class="strength-hint">
                    <span id="strength-text">Tingkat Keamanan Sandi</span>
                    <span id="encryption-badge" style="color: var(--idx-cyan);">TLS 1.3 256-Bit</span>
                  </div>
                </div>

                <!-- SMART CRYPTO SECURITY VERIFICATION BADGE -->
                <div class="crypto-verify-box" id="verify-box" onclick="toggleSecurityVerification()">
                  <div class="verify-left">
                    <div class="verify-checkbox-custom" id="verify-checkbox">✓</div>
                    <div class="verify-text-info">
                      <strong id="verify-title">Verifikasi Keamanan Kriptografis</strong>
                      <small id="verify-desc">Klik untuk validasi sertifikat anti-bot & integritas sesi</small>
                    </div>
                  </div>
                  <div class="verify-shield-badge">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                  </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="btn-login-submit" id="btn-submit">
                  <span>MASUK KE PORTAL</span>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
              </form>

              <!-- 1-CLICK INSTANT DEMO ACCOUNT PRE-FILL -->
              <div class="instant-demo-area">
                <div class="demo-bar-title">
                  <span>⚡ Quick Demo Access (1-Click Test)</span>
                  <small style="color: var(--idx-text-muted);">Akun Percobaan</small>
                </div>
                <div class="demo-buttons-grid">
                  <button type="button" class="btn-demo-fill" onclick="prefillDemo('siswa')">
                    <strong>Demo Siswa</strong>
                    <small>siswa123</small>
                  </button>
                  <button type="button" class="btn-demo-fill" onclick="prefillDemo('guru')">
                    <strong>Demo Guru</strong>
                    <small>guru123</small>
                  </button>
                  <button type="button" class="btn-demo-fill" onclick="prefillDemo('admin')">
                    <strong>Demo Admin</strong>
                    <small>admin123</small>
                  </button>
                </div>
              </div>
            </div>

            <!-- QR CODE SCAN TAB (Indodax Mobile Web Login Feature) -->
            <div id="qr-container" class="qr-container">
              <div class="qr-frame">
                <div class="qr-laser-scanner"></div>
                <!-- Inline SVG QR Code Matrix for crisp rendering -->
                <svg class="qr-code-img" viewBox="0 0 100 100" fill="#0b0f19">
                  <!-- QR Finder patterns -->
                  <rect x="5" y="5" width="28" height="28" fill="#0066f5" rx="3"/>
                  <rect x="9" y="9" width="20" height="20" fill="#ffffff" rx="2"/>
                  <rect x="13" y="13" width="12" height="12" fill="#0066f5" rx="1"/>

                  <rect x="67" y="5" width="28" height="28" fill="#0066f5" rx="3"/>
                  <rect x="71" y="9" width="20" height="20" fill="#ffffff" rx="2"/>
                  <rect x="75" y="13" width="12" height="12" fill="#0066f5" rx="1"/>

                  <rect x="5" y="67" width="28" height="28" fill="#0066f5" rx="3"/>
                  <rect x="9" y="71" width="20" height="20" fill="#ffffff" rx="2"/>
                  <rect x="13" y="75" width="12" height="12" fill="#0066f5" rx="1"/>

                  <!-- Data modules pattern -->
                  <rect x="38" y="10" width="5" height="5"/>
                  <rect x="48" y="10" width="5" height="5"/>
                  <rect x="58" y="15" width="5" height="5"/>
                  <rect x="40" y="25" width="5" height="5"/>
                  <rect x="50" y="30" width="5" height="5"/>
                  <rect x="15" y="42" width="5" height="5"/>
                  <rect x="25" y="45" width="5" height="5"/>
                  <rect x="35" y="42" width="5" height="5"/>
                  <rect x="45" y="42" width="5" height="5"/>
                  <rect x="55" y="45" width="5" height="5"/>
                  <rect x="65" y="40" width="5" height="5"/>
                  <rect x="80" y="42" width="5" height="5"/>
                  <rect x="10" y="55" width="5" height="5"/>
                  <rect x="30" y="58" width="5" height="5"/>
                  <rect x="40" y="55" width="5" height="5"/>
                  <rect x="50" y="55" width="5" height="5"/>
                  <rect x="60" y="58" width="5" height="5"/>
                  <rect x="75" y="55" width="5" height="5"/>
                  <rect x="85" y="55" width="5" height="5"/>
                  <rect x="38" y="70" width="5" height="5"/>
                  <rect x="48" y="75" width="5" height="5"/>
                  <rect x="58" y="70" width="5" height="5"/>
                  <rect x="70" y="72" width="5" height="5"/>
                  <rect x="80" y="80" width="5" height="5"/>
                  <rect x="45" y="85" width="5" height="5"/>
                  <rect x="60" y="88" width="5" height="5"/>
                  <rect x="88" y="88" width="5" height="5"/>

                  <!-- Center Logo Badge -->
                  <circle cx="50" cy="50" r="11" fill="#00c2ff"/>
                  <text x="50" y="54" font-size="8" font-weight="900" text-anchor="middle" fill="#07090e" font-family="sans-serif">BNB</text>
                </svg>
              </div>

              <div class="qr-instructions">
                <strong>Pindai dengan Aplikasi SMK BNB Mobile</strong>
                <p>Buka menu <em>Profil > Pindai Sesi Web</em> pada aplikasi untuk masuk seketika tanpa kata sandi.</p>
              </div>

              <button type="button" class="btn-simulate-qr" onclick="simulateQRLogin()">
                ⚡ Simulasi Scan Sukses (Demo)
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- FOOTER WITH REGULATORY & SECURITY BADGES -->
    <footer class="portal-footer">
      <div class="footer-inner-box">
        <div>
          © <?= date('Y') ?> <strong>SMK Bangun Nusa Bangsa</strong>. Seluruh Hak Cipta Dilindungi.
        </div>
        <div class="compliance-badges">
          <div class="comp-pill"><span>🔒</span> SSL 256-Bit TLS 1.3</div>
          <div class="comp-pill"><span>🛡️</span> ISO/IEC 27001 Certified</div>
          <div class="comp-pill"><span>⚡</span> Indodax-Grade UX Architecture</div>
          <div class="comp-pill"><span>🏛️</span> Kemendikbudristek Verified</div>
        </div>
      </div>
    </footer>
  </div>

  <!-- JAVASCRIPT: DYNAMIC INTERACTIONS & PARTICLES -->
  <script>
    // 1. DYNAMIC PARTICLE NEURAL NETWORK (Canvas Animation)
    (function initMatrixCanvas() {
      const canvas = document.getElementById('matrix-canvas');
      const ctx = canvas.getContext('2d');
      let width, height;
      let particles = [];
      const particleCount = 48;

      function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
      }
      window.addEventListener('resize', resize);
      resize();

      class Particle {
        constructor() {
          this.x = Math.random() * width;
          this.y = Math.random() * height;
          this.vx = (Math.random() - 0.5) * 0.7;
          this.vy = (Math.random() - 0.5) * 0.7;
          this.radius = Math.random() * 2 + 1;
          this.alpha = Math.random() * 0.5 + 0.2;
        }
        update() {
          this.x += this.vx;
          this.y += this.vy;
          if (this.x < 0 || this.x > width) this.vx *= -1;
          if (this.y < 0 || this.y > height) this.vy *= -1;
        }
        draw() {
          ctx.beginPath();
          ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
          ctx.fillStyle = `rgba(0, 194, 255, ${this.alpha})`;
          ctx.fill();
        }
      }

      for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
      }

      function animate() {
        ctx.clearRect(0, 0, width, height);

        for (let i = 0; i < particles.length; i++) {
          particles[i].update();
          particles[i].draw();

          // Draw interconnecting constellation lines
          for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < 130) {
              ctx.beginPath();
              ctx.moveTo(particles[i].x, particles[i].y);
              ctx.lineTo(particles[j].x, particles[j].y);
              ctx.strokeStyle = `rgba(0, 102, 245, ${0.18 * (1 - dist / 130)})`;
              ctx.lineWidth = 1;
              ctx.stroke();
            }
          }
        }
        requestAnimationFrame(animate);
      }
      animate();
    })();

    // 2. LIVE TRADING CHART SPARKLINE (HTML5 Canvas)
    (function drawTradingChart() {
      const chartCanvas = document.getElementById('trading-sparkline');
      if (!chartCanvas) return;
      const ctx = chartCanvas.getContext('2d');

      function renderChart() {
        const dpr = window.devicePixelRatio || 1;
        const rect = chartCanvas.getBoundingClientRect();
        chartCanvas.width = rect.width * dpr;
        chartCanvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);

        const w = rect.width;
        const h = rect.height;

        // Data points representing academic performance growth (Indodax style upward bull curve)
        const points = [
          { x: 0, y: h * 0.75 },
          { x: w * 0.12, y: h * 0.68 },
          { x: w * 0.25, y: h * 0.72 },
          { x: w * 0.38, y: h * 0.52 },
          { x: w * 0.50, y: h * 0.58 },
          { x: w * 0.63, y: h * 0.38 },
          { x: w * 0.78, y: h * 0.42 },
          { x: w * 0.90, y: h * 0.22 },
          { x: w, y: h * 0.15 }
        ];

        ctx.clearRect(0, 0, w, h);

        // Draw glowing area gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, h);
        gradient.addColorStop(0, 'rgba(0, 194, 255, 0.38)');
        gradient.addColorStop(0.5, 'rgba(0, 102, 245, 0.15)');
        gradient.addColorStop(1, 'rgba(7, 10, 17, 0.0)');

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
          const xc = (points[i - 1].x + points[i].x) / 2;
          const yc = (points[i - 1].y + points[i].y) / 2;
          ctx.quadraticCurveTo(points[i - 1].x, points[i - 1].y, xc, yc);
        }
        ctx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
        ctx.lineTo(w, h);
        ctx.lineTo(0, h);
        ctx.closePath();
        ctx.fillStyle = gradient;
        ctx.fill();

        // Draw neon stroke line
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
          const xc = (points[i - 1].x + points[i].x) / 2;
          const yc = (points[i - 1].y + points[i].y) / 2;
          ctx.quadraticCurveTo(points[i - 1].x, points[i - 1].y, xc, yc);
        }
        ctx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
        ctx.strokeStyle = '#00f0ff';
        ctx.lineWidth = 2.5;
        ctx.shadowColor = '#00c2ff';
        ctx.shadowBlur = 12;
        ctx.stroke();

        // Draw glowing point at the last tip
        const lastPt = points[points.length - 1];
        ctx.beginPath();
        ctx.arc(lastPt.x - 2, lastPt.y, 5, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.shadowColor = '#00f0ff';
        ctx.shadowBlur = 16;
        ctx.fill();
      }

      window.addEventListener('resize', renderChart);
      setTimeout(renderChart, 150);
    })();

    // 3. TAB SWITCHING (Email Form vs QR Code Login)
    function switchAuthTab(tab) {
      const tabFormBtn = document.getElementById('tab-form-btn');
      const tabQrBtn = document.getElementById('tab-qr-btn');
      const formContainer = document.getElementById('form-container');
      const qrContainer = document.getElementById('qr-container');

      if (tab === 'form') {
        tabFormBtn.classList.add('active');
        tabQrBtn.classList.remove('active');
        formContainer.style.display = 'block';
        qrContainer.classList.remove('active');
      } else {
        tabQrBtn.classList.add('active');
        tabFormBtn.classList.remove('active');
        formContainer.style.display = 'none';
        qrContainer.classList.add('active');
      }
    }

    // 4. ROLE SWITCHING
    function setRole(role) {
      document.querySelectorAll('.role-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-role') === role);
      });

      document.getElementById('selected-role-input').value = role;

      const label = document.getElementById('identifier-label');
      const input = document.getElementById('identifier-input');

      if (role === 'siswa') {
        label.textContent = 'Email atau NISN Siswa';
        input.placeholder = 'siswa@smkbnb.sch.id / 202401001';
      } else if (role === 'guru') {
        label.textContent = 'Email atau NIP Pengajar';
        input.placeholder = 'guru@smkbnb.sch.id / 198503152010011002';
      } else {
        label.textContent = 'Email atau Akun Administrator';
        input.placeholder = 'admin@smkbnb.sch.id / admin';
      }
    }

    // 5. PREFILL DEMO ACCOUNT BUTTONS
    function prefillDemo(role) {
      setRole(role);
      const idInput = document.getElementById('identifier-input');
      const passInput = document.getElementById('password-input');

      if (role === 'siswa') {
        idInput.value = 'siswa@smkbnb.sch.id';
        passInput.value = 'siswa123';
      } else if (role === 'guru') {
        idInput.value = 'guru@smkbnb.sch.id';
        passInput.value = 'guru123';
      } else {
        idInput.value = 'admin@smkbnb.sch.id';
        passInput.value = 'admin123';
      }

      checkStrength(passInput.value);

      // Auto verify security on demo click for extreme convenience
      const verifyBox = document.getElementById('verify-box');
      const hiddenInput = document.getElementById('security_verified_input');
      const title = document.getElementById('verify-title');
      const desc = document.getElementById('verify-desc');

      verifyBox.classList.add('verified');
      hiddenInput.value = '1';
      title.textContent = 'Verifikasi Kriptografi Sukses';
      desc.textContent = 'Token integritas hash valid [0x8f2d...c3a1]';
    }

    // 6. TOGGLE PASSWORD VISIBILITY
    function togglePasswordVisibility() {
      const passInput = document.getElementById('password-input');
      const eyeIcon = document.getElementById('eye-icon');
      if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
      } else {
        passInput.type = 'password';
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
      }
    }

    // 7. PASSWORD STRENGTH METER
    function checkStrength(val) {
      const s1 = document.getElementById('str-1');
      const s2 = document.getElementById('str-2');
      const s3 = document.getElementById('str-3');
      const s4 = document.getElementById('str-4');
      const hint = document.getElementById('strength-text');

      [s1, s2, s3, s4].forEach(s => s.style.background = 'transparent');

      if (!val) {
        hint.textContent = 'Tingkat Keamanan Sandi';
        return;
      }

      let score = 0;
      if (val.length >= 6) score++;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val) || /[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val) || val.length >= 10) score++;

      if (score === 1) {
        s1.style.background = 'var(--idx-red)';
        hint.textContent = 'Keamanan: Lemah';
        hint.style.color = 'var(--idx-red)';
      } else if (score === 2) {
        s1.style.background = 'var(--idx-gold)';
        s2.style.background = 'var(--idx-gold)';
        hint.textContent = 'Keamanan: Sedang';
        hint.style.color = 'var(--idx-gold)';
      } else if (score === 3) {
        s1.style.background = 'var(--idx-cyan)';
        s2.style.background = 'var(--idx-cyan)';
        s3.style.background = 'var(--idx-cyan)';
        hint.textContent = 'Keamanan: Kuat';
        hint.style.color = 'var(--idx-cyan)';
      } else {
        s1.style.background = 'var(--idx-green)';
        s2.style.background = 'var(--idx-green)';
        s3.style.background = 'var(--idx-green)';
        s4.style.background = 'var(--idx-green)';
        hint.textContent = 'Keamanan: Kriptografi Maksimal';
        hint.style.color = 'var(--idx-green)';
      }
    }

    // 8. SECURITY VERIFICATION BADGE INTERACTION
    function toggleSecurityVerification() {
      const verifyBox = document.getElementById('verify-box');
      const hiddenInput = document.getElementById('security_verified_input');
      const title = document.getElementById('verify-title');
      const desc = document.getElementById('verify-desc');

      const isVerified = verifyBox.classList.contains('verified');

      if (!isVerified) {
        verifyBox.classList.add('verified');
        hiddenInput.value = '1';
        title.textContent = 'Verifikasi Kriptografi Sukses';
        desc.textContent = 'Token integritas hash valid [0x4e1a...9b72]';
      } else {
        verifyBox.classList.remove('verified');
        hiddenInput.value = '0';
        title.textContent = 'Verifikasi Keamanan Kriptografis';
        desc.textContent = 'Klik untuk validasi sertifikat anti-bot & integritas sesi';
      }
    }

    // 9. QR CODE SIMULATION (One-click login for demonstration)
    function simulateQRLogin() {
      prefillDemo('siswa');
      switchAuthTab('form');
      const form = document.getElementById('login-form');
      const submitBtn = document.getElementById('btn-submit');
      submitBtn.innerHTML = '<span>Memverifikasi Sesi QR...</span>';
      setTimeout(() => {
        form.submit();
      }, 700);
    }

    // 10. FORGOT PASSWORD MODAL NOTIFICATION
    function openForgotModal() {
      alert('Untuk pemulihan akun atau reset kredensial portal SMK Bangun Nusa Bangsa, silakan hubungi Administrator Lab IT / Tim Tata Usaha di ruang Server Kampus Karadenan atau hubungi WhatsApp Resmi BNB: +62 812-9900-BNB.');
    }

    // 11. SUBMIT LOADING SPINNER
    document.getElementById('login-form').addEventListener('submit', function(e) {
      const verifyInput = document.getElementById('security_verified_input');
      if (verifyInput.value !== '1') {
        e.preventDefault();
        alert('Harap klik kotak "Verifikasi Keamanan Kriptografis" terlebih dahulu sebelum masuk.');
        return;
      }

      const submitBtn = document.getElementById('btn-submit');
      submitBtn.style.opacity = '0.85';
      submitBtn.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        <span>Mengautentikasi ke Jaringan...</span>
      `;
    });
  </script>
</body>
</html>
