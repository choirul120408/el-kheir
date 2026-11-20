<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$profile = [
    'school_name' => 'SMK Bangun Nusa Bangsa',
    'location' => 'Karadenan · Bogor',
    'description' => 'Selamat datang di SMK Bangun Nusa Bangsa, Karadenan. Tempat belajar untuk membangun keterampilan, karakter, dan keberanian meraih masa depan.',
    'about_primary' => 'SMK Bangun Nusa Bangsa hadir di Karadenan, Bogor sebagai ruang bagi generasi muda untuk terus belajar dan mempersiapkan diri menghadapi dunia yang terus berubah.',
    'about_secondary' => 'Kami percaya pendidikan kejuruan yang kuat tumbuh dari perpaduan pengetahuan, pengalaman praktik, karakter baik, dan semangat untuk terus berkembang.',
    'map_query' => 'SMK Bangun Nusa Bangsa Karadenan',
];
$features = [
    ['icon' => '⌘', 'title' => 'Keterampilan nyata', 'description' => 'Menghubungkan proses belajar dengan keterampilan yang berguna untuk menghadapi tantangan masa depan.'],
    ['icon' => '✳', 'title' => 'Karakter bertumbuh', 'description' => 'Menumbuhkan rasa tanggung jawab, rasa ingin tahu, dan keyakinan untuk terus mengembangkan diri.'],
    ['icon' => '⤴', 'title' => 'Berani berkarya', 'description' => 'Mendorong kreativitas dan kolaborasi agar ide baik dapat berkembang menjadi karya yang bermanfaat.'],
];
$pdo = null;
$databaseWarning = null;

try {
    $pdo = dbConnection();
    $profileRow = $pdo->query('SELECT school_name, location, description, about_primary, about_secondary, map_query FROM school_profile WHERE id = 1')->fetch();
    if ($profileRow) {
        $profile = array_merge($profile, $profileRow);
    } else {
        $databaseWarning = 'Data profil belum tersedia. Impor database.sql untuk mengisi data awal.';
    }

    $featureRows = $pdo->query('SELECT icon, title, description FROM school_features ORDER BY sort_order, id')->fetchAll();
    if ($featureRows) {
        $features = $featureRows;
    } else {
        $databaseWarning = 'Data profil belum tersedia. Impor database.sql untuk mengisi data awal.';
    }
} catch (PDOException $exception) {
    error_log('Database connection/query failed: ' . $exception->getMessage());
    $databaseWarning = 'Database belum terhubung. Impor database.sql dan periksa pengaturan koneksi pada config/database.php.';
}

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
$formError = null;
$formSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if ($pdo === null) {
        $formError = 'Pesan belum dapat dikirim karena database belum terhubung.';
    } elseif (!hash_equals($csrfToken, $submittedToken)) {
        $formError = 'Sesi formulir tidak valid. Muat ulang halaman, lalu coba kembali.';
    } elseif ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $subject === '' || strlen($subject) > 160 || $message === '' || strlen($message) > 5000) {
        $formError = 'Periksa kembali nama, email, topik, dan pesan. Pesan maksimal 5.000 karakter.';
    } else {
        try {
            $statement = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)');
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ]);
            $_SESSION['flash_success'] = 'Terima kasih. Pesan Anda sudah tersimpan.';
            header('Location: index.php#kontak');
            exit;
        } catch (PDOException $exception) {
            error_log('Contact message insert failed: ' . $exception->getMessage());
            $formError = 'Pesan belum berhasil disimpan. Silakan coba beberapa saat lagi.';
        }
    }
}

$mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($profile['map_query']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#071311">
  <meta name="description" content="<?= e($profile['description']) ?>">
  <title><?= e($profile['school_name']) ?> — Tumbuh, Berkarya, Berdampak</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: dark;
      --ink: #f3fbf7;
      --muted: #a4b7b0;
      --bg: #071311;
      --panel: #0c1c18;
      --line: rgba(192, 255, 227, .13);
      --mint: #8df5c5;
      --cyan: #62dce1;
      --violet: #9c8bff;
      --font-display: "Space Grotesk", sans-serif;
      --font-body: "DM Sans", sans-serif;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; scroll-padding-top: 88px; }
    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink);
      font-family: var(--font-body);
      overflow-x: hidden;
    }
    body::before {
      position: fixed;
      z-index: -1;
      inset: 0;
      background:
        radial-gradient(ellipse at 70% 8%, rgba(35, 136, 109, .14), transparent 34%),
        radial-gradient(ellipse at 0% 40%, rgba(74, 73, 158, .1), transparent 36%);
      content: "";
      pointer-events: none;
    }
    a { color: inherit; text-decoration: none; }
    button { font: inherit; }
    .shell { width: min(1160px, calc(100% - 48px)); margin-inline: auto; }
    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      color: var(--mint);
      font-size: .73rem;
      font-weight: 700;
      letter-spacing: .16em;
      text-transform: uppercase;
    }
    .eyebrow::before { width: 25px; height: 1px; background: currentColor; content: ""; }

    .header {
      position: sticky;
      z-index: 20;
      top: 0;
      border-bottom: 1px solid rgba(192, 255, 227, .08);
      background: rgba(7, 19, 17, .78);
      backdrop-filter: blur(18px);
    }
    .nav { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
    .brand { display: inline-flex; align-items: center; gap: 12px; }
    .brand-mark {
      display: grid;
      width: 42px;
      height: 42px;
      place-items: center;
      border: 1px solid rgba(141, 245, 197, .5);
      border-radius: 14px;
      background: linear-gradient(145deg, rgba(141,245,197,.2), rgba(98,220,225,.04));
      box-shadow: inset 0 0 20px rgba(141,245,197,.08), 0 0 24px rgba(98,220,225,.1);
      color: var(--mint);
      font-family: var(--font-display);
      font-size: .9rem;
      font-weight: 700;
    }
    .brand-name { display: grid; gap: 2px; font-size: .86rem; font-weight: 700; line-height: 1.15; }
    .brand-name small { color: var(--muted); font-size: .62rem; font-weight: 500; letter-spacing: .12em; text-transform: uppercase; }
    .nav-links { display: flex; align-items: center; gap: 30px; color: #c4d2cc; font-size: .86rem; }
    .nav-links a { transition: color .2s ease; }
    .nav-links a:hover { color: var(--mint); }
    .nav-cta, .button {
      display: inline-flex;
      min-height: 47px;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 0 19px;
      border: 1px solid rgba(141,245,197,.38);
      border-radius: 999px;
      background: rgba(141,245,197,.1);
      color: var(--mint);
      font-size: .83rem;
      font-weight: 700;
      transition: transform .2s ease, background .2s ease, border-color .2s ease;
    }
    .nav-cta:hover, .button:hover { transform: translateY(-2px); border-color: var(--mint); background: rgba(141,245,197,.16); }
    .button-primary { border-color: var(--mint); background: var(--mint); color: #082018; box-shadow: 0 8px 30px rgba(90,227,163,.16); }
    .button-primary:hover { background: #b0fbd5; }
    .menu-toggle { display: none; width: 42px; height: 42px; border: 1px solid var(--line); border-radius: 12px; background: transparent; color: var(--ink); }

    .hero { position: relative; min-height: 690px; display: grid; align-items: center; padding: 86px 0 96px; }
    .hero::after {
      position: absolute;
      z-index: -1;
      top: 28%;
      left: 34%;
      width: 54vw;
      height: 400px;
      border: 1px solid rgba(100, 235, 190, .08);
      border-radius: 50%;
      content: "";
      transform: rotate(-15deg);
      box-shadow: 0 0 100px rgba(50, 180, 141, .05);
    }
    .hero-grid { display: grid; grid-template-columns: 1.04fr .96fr; align-items: center; gap: 54px; }
    .hero-copy { position: relative; z-index: 2; }
    .hero h1 {
      max-width: 650px;
      margin: 22px 0 20px;
      font-family: var(--font-display);
      font-size: clamp(3rem, 6vw, 5.2rem);
      font-weight: 600;
      letter-spacing: -.075em;
      line-height: .99;
    }
    .hero h1 span { color: var(--mint); }
    .hero-copy > p { max-width: 480px; margin: 0; color: var(--muted); font-size: 1.05rem; line-height: 1.8; }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 31px; }
    .text-link { display: inline-flex; align-items: center; gap: 9px; color: #d3e2dc; font-size: .88rem; font-weight: 600; }
    .text-link span { color: var(--mint); transition: transform .2s ease; }
    .text-link:hover span { transform: translateX(4px); }
    .hero-note { display: flex; align-items: center; gap: 12px; margin-top: 44px; color: #8fa69d; font-size: .77rem; }
    .live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--mint); box-shadow: 0 0 12px var(--mint); }
    .hero-art { position: relative; min-height: 465px; display: grid; place-items: center; perspective: 900px; }
    .orbital {
      position: absolute;
      width: min(430px, 100%);
      aspect-ratio: 1;
      border: 1px solid rgba(141,245,197,.14);
      border-radius: 50%;
      animation: orbit 28s linear infinite;
    }
    .orbital::before, .orbital::after { position: absolute; border: 1px solid rgba(98,220,225,.12); border-radius: 50%; content: ""; }
    .orbital::before { inset: 10%; transform: rotateX(68deg) rotateZ(-20deg); }
    .orbital::after { inset: 22%; transform: rotateY(68deg) rotateZ(25deg); }
    .orbit-dot { position: absolute; top: 15%; left: 11%; width: 9px; height: 9px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 18px var(--cyan); }
    .campus-card {
      position: relative;
      width: min(360px, 84%);
      height: 405px;
      overflow: hidden;
      border: 1px solid rgba(210,255,235,.24);
      border-radius: 28px;
      background: linear-gradient(145deg, #19352c, #10221e 64%, #1b2940);
      box-shadow: 0 35px 100px rgba(0,0,0,.4), 0 0 55px rgba(88,225,171,.11);
      transform: rotateY(-9deg) rotateX(3deg);
      transition: transform .5s ease;
    }
    .campus-card:hover { transform: rotateY(0) rotateX(0) translateY(-6px); }
    .campus-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .73; }
    .campus-card::after {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(5,15,13,.08) 8%, rgba(5,15,13,.15) 42%, #081511 100%);
      content: "";
    }
    .image-caption {
      position: absolute;
      z-index: 1;
      right: 16px;
      bottom: 14px;
      left: 16px;
      color: rgba(242,251,247,.7);
      font-size: .64rem;
      line-height: 1.5;
    }
    .campus-label {
      position: absolute;
      z-index: 2;
      top: 18px;
      left: 18px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 12px;
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 999px;
      background: rgba(8,21,17,.6);
      backdrop-filter: blur(10px);
      color: #edfff6;
      font-size: .7rem;
    }
    .campus-label::before { width: 6px; height: 6px; border-radius: 50%; background: var(--mint); content: ""; }
    .float-chip {
      position: absolute;
      z-index: 3;
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 13px 15px;
      border: 1px solid rgba(196,255,226,.18);
      border-radius: 16px;
      background: rgba(12,30,24,.82);
      box-shadow: 0 14px 40px rgba(0,0,0,.2);
      backdrop-filter: blur(14px);
      animation: float 5s ease-in-out infinite;
    }
    .float-chip strong { display: block; font-size: .77rem; }
    .float-chip small { display: block; margin-top: 3px; color: var(--muted); font-size: .65rem; }
    .chip-icon { display: grid; width: 35px; height: 35px; place-items: center; border-radius: 11px; background: rgba(141,245,197,.12); color: var(--mint); }
    .chip-one { top: 13%; right: -2%; }
    .chip-two { bottom: 14%; left: -3%; animation-delay: -2s; }
    .scroll-cue { position: absolute; bottom: 30px; left: 50%; display: flex; align-items: center; gap: 10px; color: #718880; font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; transform: translateX(-50%); }
    .scroll-cue span { width: 1px; height: 27px; background: linear-gradient(var(--mint), transparent); }

    .ticker { overflow: hidden; border-block: 1px solid var(--line); background: rgba(141,245,197,.025); }
    .ticker-track { display: flex; width: max-content; gap: 40px; padding: 18px 0; animation: marquee 32s linear infinite; }
    .ticker-item { display: flex; align-items: center; gap: 13px; color: #bdcec6; font-family: var(--font-display); font-size: .78rem; letter-spacing: .11em; text-transform: uppercase; white-space: nowrap; }
    .ticker-item b { color: var(--mint); font-weight: 500; }
    .ticker-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 10px var(--cyan); }

    section.content-section { padding: 112px 0; }
    .section-heading { max-width: 680px; margin-bottom: 43px; }
    .section-heading h2, .about-copy h2, .cta-box h2 { margin: 17px 0 14px; font-family: var(--font-display); font-size: clamp(2.2rem, 4vw, 3.45rem); font-weight: 500; letter-spacing: -.06em; line-height: 1.06; }
    .section-heading p, .about-copy p { margin: 0; color: var(--muted); font-size: .98rem; line-height: 1.8; }
    .about-grid { display: grid; grid-template-columns: .9fr 1.1fr; gap: 80px; align-items: center; }
    .about-visual {
      position: relative;
      min-height: 390px;
      overflow: hidden;
      border: 1px solid rgba(141,245,197,.14);
      border-radius: 28px;
      background:
        linear-gradient(145deg, rgba(141,245,197,.12), transparent 50%),
        repeating-linear-gradient(135deg, rgba(141,245,197,.035) 0 1px, transparent 1px 18px),
        #0c1c18;
    }
    .about-visual::before { position: absolute; top: 50%; left: 50%; width: 240px; height: 240px; border: 1px solid rgba(141,245,197,.22); border-radius: 50%; content: ""; transform: translate(-50%,-50%); box-shadow: 0 0 75px rgba(75,222,168,.1), inset 0 0 75px rgba(75,222,168,.05); }
    .about-visual::after { position: absolute; top: 50%; left: 50%; width: 175px; height: 175px; border: 1px solid rgba(98,220,225,.36); border-radius: 50%; content: ""; transform: translate(-50%,-50%) rotateX(65deg); }
    .visual-core { position: absolute; z-index: 1; top: 50%; left: 50%; display: grid; width: 110px; height: 110px; place-items: center; border: 1px solid rgba(141,245,197,.5); border-radius: 30px; background: linear-gradient(145deg, rgba(141,245,197,.24), rgba(98,220,225,.08)); box-shadow: 0 0 55px rgba(141,245,197,.17); color: var(--mint); font: 700 1.6rem var(--font-display); transform: translate(-50%,-50%) rotate(-8deg); }
    .visual-orbit { position: absolute; top: 50%; left: 50%; width: 310px; height: 100px; border: 1px solid rgba(156,139,255,.3); border-radius: 50%; transform: translate(-50%,-50%) rotate(35deg); animation: orbit 20s linear infinite; }
    .visual-orbit::after { position: absolute; top: 10px; right: 30px; width: 8px; height: 8px; border-radius: 50%; background: var(--violet); box-shadow: 0 0 15px var(--violet); content: ""; }
    .about-copy h2 { margin-top: 18px; }
    .about-copy p + p { margin-top: 15px; }
    .about-points { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 30px; }
    .point { display: flex; align-items: center; gap: 11px; color: #d5e5dd; font-size: .84rem; }
    .point i { display: grid; width: 27px; height: 27px; place-items: center; border: 1px solid rgba(141,245,197,.25); border-radius: 9px; color: var(--mint); font-size: .72rem; font-style: normal; }

    .program-section { background: linear-gradient(180deg, transparent, rgba(12,28,24,.58), transparent); }
    .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 17px; }
    .feature-card {
      position: relative;
      min-height: 250px;
      padding: 27px;
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: 22px;
      background: linear-gradient(145deg, rgba(17,39,32,.92), rgba(10,25,21,.72));
      transition: transform .25s ease, border-color .25s ease;
    }
    .feature-card:hover { transform: translateY(-6px); border-color: rgba(141,245,197,.4); }
    .feature-card::after { position: absolute; right: -52px; bottom: -96px; width: 190px; height: 190px; border: 1px solid rgba(141,245,197,.1); border-radius: 50%; content: ""; box-shadow: 0 0 0 23px rgba(141,245,197,.025), 0 0 0 48px rgba(141,245,197,.02); }
    .feature-icon { display: grid; width: 47px; height: 47px; place-items: center; border: 1px solid rgba(141,245,197,.2); border-radius: 15px; background: rgba(141,245,197,.08); color: var(--mint); font-size: 1.1rem; }
    .feature-card h3 { margin: 25px 0 10px; font: 600 1.22rem var(--font-display); letter-spacing: -.03em; }
    .feature-card p { max-width: 290px; margin: 0; color: var(--muted); font-size: .88rem; line-height: 1.7; }
    .card-index { position: absolute; top: 29px; right: 28px; color: #6b837a; font: .75rem var(--font-display); }

    .location-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 54px; align-items: stretch; }
    .location-card { display: flex; flex-direction: column; justify-content: space-between; padding: 35px; border: 1px solid var(--line); border-radius: 24px; background: var(--panel); }
    .location-card h2 { margin: 18px 0 12px; font: 500 clamp(2rem, 3.5vw, 2.8rem) var(--font-display); letter-spacing: -.06em; }
    .location-card p { max-width: 440px; margin: 0; color: var(--muted); line-height: 1.8; }
    .location-tag { display: inline-flex; align-items: center; gap: 10px; margin-top: 28px; padding: 10px 13px; border: 1px solid var(--line); border-radius: 999px; color: #d8e8e0; font-size: .8rem; }
    .location-tag span { color: var(--mint); }
    .map-visual {
      position: relative;
      min-height: 320px;
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: 24px;
      background:
        linear-gradient(35deg, transparent 46%, rgba(98,220,225,.12) 47%, rgba(98,220,225,.12) 48%, transparent 49%),
        linear-gradient(145deg, transparent 46%, rgba(141,245,197,.13) 47%, rgba(141,245,197,.13) 48%, transparent 49%),
        repeating-linear-gradient(0deg, transparent 0 42px, rgba(205,255,231,.045) 43px 44px),
        repeating-linear-gradient(90deg, transparent 0 55px, rgba(205,255,231,.045) 56px 57px),
        #0a1916;
    }
    .map-visual::before, .map-visual::after { position: absolute; width: 200%; height: 1px; background: rgba(141,245,197,.08); content: ""; transform: rotate(-29deg); }
    .map-visual::before { top: 35%; left: -35%; }
    .map-visual::after { top: 70%; left: -20%; transform: rotate(20deg); }
    .map-pin { position: absolute; top: 50%; left: 50%; display: grid; width: 57px; height: 57px; place-items: center; border: 1px solid rgba(141,245,197,.58); border-radius: 19px 19px 19px 4px; background: rgba(141,245,197,.12); color: var(--mint); font-size: 1.3rem; box-shadow: 0 0 40px rgba(141,245,197,.2); transform: translate(-50%,-65%) rotate(-45deg); }
    .map-pin span { transform: rotate(45deg); }
    .map-label { position: absolute; top: calc(50% + 45px); left: 50%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; background: rgba(7,19,17,.88); color: #e8f7ef; font-size: .77rem; white-space: nowrap; transform: translateX(-50%); }

    .cta-section { padding: 30px 0 105px !important; }
    .cta-box { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 30px; padding: 44px 50px; overflow: hidden; border: 1px solid rgba(141,245,197,.25); border-radius: 26px; background: radial-gradient(ellipse at 90% 50%, rgba(141,245,197,.14), transparent 35%), linear-gradient(110deg, #11271f, #0b1c17 72%); }
    .cta-box::after { position: absolute; top: -90px; right: 14%; width: 240px; height: 240px; border: 1px solid rgba(141,245,197,.14); border-radius: 50%; content: ""; box-shadow: 0 0 0 30px rgba(141,245,197,.025), 0 0 0 60px rgba(141,245,197,.018); }
    .cta-box h2 { max-width: 540px; margin-bottom: 0; font-size: clamp(1.8rem, 3vw, 2.6rem); }
    .cta-box .button { position: relative; z-index: 1; flex-shrink: 0; }
    .database-notice { padding: 12px 20px; border-bottom: 1px solid rgba(255,199,112,.25); background: rgba(91,59,19,.35); color: #ffdda4; text-align: center; font-size: .82rem; }
    .contact-grid { display: grid; grid-template-columns: .8fr 1.2fr; gap: 20px; align-items: stretch; }
    .contact-intro { display: flex; flex-direction: column; align-items: flex-start; justify-content: center; }
    .contact-intro h2 { max-width: 420px; }
    .contact-intro p { max-width: 400px; margin: 14px 0 0; color: var(--muted); line-height: 1.75; }
    .contact-form { padding: 32px; border: 1px solid var(--line); border-radius: 24px; background: var(--panel); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-field { display: grid; gap: 8px; margin-bottom: 16px; }
    .form-field label { color: #d7e8df; font-size: .79rem; font-weight: 600; }
    .form-field input, .form-field textarea { width: 100%; padding: 13px 14px; border: 1px solid var(--line); border-radius: 12px; outline: none; background: #081713; color: var(--ink); font: inherit; font-size: .86rem; transition: border-color .2s ease, box-shadow .2s ease; }
    .form-field textarea { min-height: 125px; resize: vertical; }
    .form-field input:focus, .form-field textarea:focus { border-color: rgba(141,245,197,.65); box-shadow: 0 0 0 3px rgba(141,245,197,.08); }
    .form-feedback { margin: 0 0 16px; padding: 12px 14px; border: 1px solid rgba(141,245,197,.25); border-radius: 12px; color: var(--mint); font-size: .83rem; }
    .form-feedback.error { border-color: rgba(255,151,151,.3); color: #ffb9b9; }

    footer { border-top: 1px solid var(--line); }
    .footer-inner { min-height: 98px; display: flex; align-items: center; justify-content: space-between; gap: 20px; color: #83988f; font-size: .76rem; }
    .footer-inner strong { color: #cfe0d8; font-weight: 600; }
    .footer-links { display: flex; gap: 20px; }
    .footer-links a:hover { color: var(--mint); }
    .reveal { opacity: 0; transform: translateY(22px); transition: opacity .7s ease, transform .7s cubic-bezier(.2,.7,.2,1); }
    .reveal.is-visible { opacity: 1; transform: translateY(0); }

    @keyframes orbit { to { transform: rotate(345deg); } }
    @keyframes float { 0%, 100% { translate: 0 0; } 50% { translate: 0 -11px; } }
    @keyframes marquee { to { transform: translateX(-50%); } }

    @media (max-width: 900px) {
      .nav-links { gap: 18px; }
      .hero-grid { gap: 22px; }
      .hero-art { min-height: 410px; }
      .campus-card { height: 360px; }
      .about-grid { gap: 45px; }
      .location-grid { gap: 24px; }
    }
    @media (max-width: 700px) {
      .shell { width: min(100% - 36px, 540px); }
      .nav { min-height: 70px; }
      .menu-toggle { display: grid; place-items: center; }
      .nav-cta { display: none; }
      .nav-links {
        position: absolute;
        top: 70px;
        right: 0;
        left: 0;
        display: none;
        align-items: stretch;
        gap: 0;
        padding: 10px 18px 18px;
        border-bottom: 1px solid var(--line);
        background: rgba(7,19,17,.97);
      }
      .nav-links.is-open { display: flex; flex-direction: column; }
      .nav-links a { padding: 13px 8px; }
      .hero { min-height: auto; padding: 76px 0 80px; }
      .hero-grid { grid-template-columns: 1fr; gap: 25px; }
      .hero h1 { max-width: 530px; font-size: clamp(3.2rem, 14vw, 5rem); }
      .hero-copy > p { font-size: .98rem; }
      .hero-art { min-height: 400px; margin-top: 4px; }
      .campus-card { width: min(310px, 78%); height: 350px; }
      .orbital { width: min(370px, 100%); }
      .chip-one { right: 0; }
      .chip-two { left: 0; }
      .scroll-cue { display: none; }
      section.content-section { padding: 78px 0; }
      .about-grid, .location-grid { grid-template-columns: 1fr; gap: 30px; }
      .about-visual { min-height: 300px; order: 2; }
      .cards { grid-template-columns: 1fr; }
      .feature-card { min-height: 220px; }
      .location-card { min-height: 270px; padding: 27px; }
      .map-visual { min-height: 270px; }
      .cta-section { padding: 10px 0 72px !important; }
      .cta-box { align-items: flex-start; flex-direction: column; padding: 30px 26px; }
      .cta-box::after { right: -10%; }
      .contact-grid { grid-template-columns: 1fr; }
      .contact-intro { padding: 30px 26px; }
      .contact-form { padding: 24px 20px; }
      .form-row { grid-template-columns: 1fr; gap: 0; }
      .footer-inner { min-height: 115px; align-items: flex-start; flex-direction: column; justify-content: center; gap: 10px; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
      .reveal { opacity: 1; transform: none; }
    }
  </style>
</head>
<body>
  <header class="header">
    <nav class="nav shell" aria-label="Navigasi utama">
      <a class="brand" href="#beranda" aria-label="SMK Bangun Nusa Bangsa, beranda">
        <span class="brand-mark">BNB</span>
        <span class="brand-name"><?= e($profile['school_name']) ?><small><?= e($profile['location']) ?></small></span>
      </a>
      <div class="nav-links" id="nav-links">
        <a href="#profil">Profil</a>
        <a href="#keunggulan">Keunggulan</a>
        <a href="#lokasi">Lokasi</a>
        <a href="#kontak">Kontak</a>
        <a href="login.php" style="color: var(--cyan); font-weight: 600;">Portal Siswa ↗</a>
      </div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <a class="nav-cta" href="login.php" style="background: linear-gradient(135deg, #0066f5, #00c2ff); color: #ffffff; border-color: #00c2ff; box-shadow: 0 0 20px rgba(0,194,255,0.35); font-weight: 700;">Masuk Portal <span aria-hidden="true">→</span></a>
        <a class="nav-cta" href="#profil" style="display: none;">Jelajahi sekolah <span aria-hidden="true">↗</span></a>
      </div>
      <button class="menu-toggle" type="button" aria-label="Buka navigasi" aria-expanded="false" aria-controls="nav-links">☰</button>
    </nav>
  </header>
  <?php if ($databaseWarning !== null): ?>
    <div class="database-notice" role="status"><?= e($databaseWarning) ?></div>
  <?php endif; ?>

  <main>
    <section class="hero" id="beranda">
      <div class="hero-grid shell">
        <div class="hero-copy reveal">
          <span class="eyebrow">Masa depan dimulai di sini</span>
          <h1>Tumbuh hari ini.<br><span>Berdampak esok.</span></h1>
          <p><?= e($profile['description']) ?></p>
          <div class="hero-actions">
            <a class="button button-primary" href="#profil">Kenali sekolah <span aria-hidden="true">↘</span></a>
            <a class="button" href="login.php" style="background: linear-gradient(135deg, #0066f5, #00c2ff); color: #ffffff; border: 1px solid #00f0ff; box-shadow: 0 0 25px rgba(0, 194, 255, 0.35); font-weight: 700;">Masuk Portal (Indodax Ed.) <span aria-hidden="true">→</span></a>
            <a class="text-link" href="#keunggulan">Lihat yang kami percaya <span aria-hidden="true">→</span></a>
          </div>
          <div class="hero-note"><span class="live-dot"></span> Pendidikan vokasi · <?= e($profile['location']) ?></div>
        </div>
        <div class="hero-art reveal" aria-label="Visual kampus">
          <div class="orbital"><span class="orbit-dot"></span></div>
          <div class="campus-card">
            <img class="campus-image" src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&amp;fit=crop&amp;w=1000&amp;q=85" alt="Ilustrasi gedung kampus; bukan foto resmi SMK Bangun Nusa Bangsa" fetchpriority="high">
            <span class="campus-label">Karadenan, Bogor</span>
            <span class="image-caption">Visual kampus ilustratif. Ganti dengan foto resmi sekolah untuk menampilkan profil kampus yang sebenarnya.</span>
          </div>
          <div class="float-chip chip-one"><span class="chip-icon" aria-hidden="true">✦</span><span><strong>Belajar bermakna</strong><small>Bekal untuk masa depan</small></span></div>
          <div class="float-chip chip-two"><span class="chip-icon" aria-hidden="true">⌁</span><span><strong>Bangun potensi</strong><small>Berani tumbuh dan berkarya</small></span></div>
        </div>
      </div>
      <div class="scroll-cue"><span></span> Gulir untuk mengenal kami</div>
    </section>

    <div class="ticker" aria-label="Nilai sekolah">
      <div class="ticker-track" aria-hidden="true">
        <div class="ticker-item"><b>01</b> Kompetensi <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>02</b> Karakter <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>03</b> Kreativitas <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>04</b> Kolaborasi <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>01</b> Kompetensi <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>02</b> Karakter <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>03</b> Kreativitas <i class="ticker-dot"></i></div>
        <div class="ticker-item"><b>04</b> Kolaborasi <i class="ticker-dot"></i></div>
      </div>
    </div>

    <section class="content-section" id="profil">
      <div class="about-grid shell">
        <div class="about-visual reveal" aria-hidden="true">
          <div class="visual-orbit"></div>
          <div class="visual-core">BNB</div>
        </div>
        <div class="about-copy reveal">
          <span class="eyebrow">Tentang sekolah</span>
          <h2>Membangun bekal, membuka peluang.</h2>
          <p><?= e($profile['about_primary']) ?></p>
          <p><?= e($profile['about_secondary']) ?></p>
          <div class="about-points">
            <div class="point"><i aria-hidden="true">✦</i> Berorientasi pada masa depan</div>
            <div class="point"><i aria-hidden="true">✦</i> Bertumbuh bersama komunitas</div>
            <div class="point"><i aria-hidden="true">✦</i> Mengembangkan potensi diri</div>
            <div class="point"><i aria-hidden="true">✦</i> Siap belajar sepanjang hayat</div>
          </div>
        </div>
      </div>
    </section>

    <section class="content-section program-section" id="keunggulan">
      <div class="shell">
        <div class="section-heading reveal">
          <span class="eyebrow">Bekal untuk melangkah</span>
          <h2>Belajar lebih dari sekadar teori.</h2>
          <p>Pengalaman sekolah yang baik membantu siswa mengenali kekuatan diri, mempraktikkan pengetahuan, dan bertumbuh bersama.</p>
        </div>
        <div class="cards">
          <?php foreach ($features as $index => $feature): ?>
            <article class="feature-card reveal">
              <span class="card-index"><?= e(sprintf('%02d', $index + 1)) ?> / <?= e(sprintf('%02d', count($features))) ?></span>
              <span class="feature-icon" aria-hidden="true"><?= e($feature['icon']) ?></span>
              <h3><?= e($feature['title']) ?></h3>
              <p><?= e($feature['description']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="content-section" id="lokasi">
      <div class="location-grid shell">
        <div class="location-card reveal">
          <div>
            <span class="eyebrow">Temukan kami</span>
            <h2>Dari Karadenan,<br>untuk masa depan.</h2>
            <p><?= e($profile['school_name']) ?> berlokasi di <?= e($profile['location']) ?>. Buka peta untuk mencari titik lokasi dan petunjuk perjalanan.</p>
            <span class="location-tag"><span aria-hidden="true">⌖</span> <?= e($profile['location']) ?></span>
          </div>
          <div style="margin-top: 28px">
            <a class="text-link" href="<?= e($mapUrl) ?>" target="_blank" rel="noopener noreferrer">Cari di Google Maps <span aria-hidden="true">↗</span></a>
          </div>
        </div>
        <a class="map-visual reveal" href="<?= e($mapUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Cari lokasi <?= e($profile['school_name']) ?> di Google Maps">
          <span class="map-pin"><span aria-hidden="true">⌖</span></span>
          <span class="map-label"><?= e($profile['school_name']) ?> · <?= e($profile['location']) ?></span>
        </a>
      </div>
    </section>

    <section class="content-section cta-section" id="kontak">
      <div class="contact-grid shell reveal">
        <div class="cta-box contact-intro">
          <span class="eyebrow">Terhubung dengan kami</span>
          <h2>Temukan ruang untuk tumbuh dan berkarya.</h2>
          <p>Kirim pertanyaan kepada sekolah. Pesan akan tersimpan di database untuk ditindaklanjuti.</p>
        </div>
        <form class="contact-form" method="post" action="#kontak">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <?php if ($formSuccess !== null): ?>
            <p class="form-feedback" role="status"><?= e($formSuccess) ?></p>
          <?php endif; ?>
          <?php if ($formError !== null): ?>
            <p class="form-feedback error" role="alert"><?= e($formError) ?></p>
          <?php endif; ?>
          <div class="form-row">
            <div class="form-field">
              <label for="contact-name">Nama</label>
              <input id="contact-name" name="name" type="text" maxlength="100" autocomplete="name" required>
            </div>
            <div class="form-field">
              <label for="contact-email">Email</label>
              <input id="contact-email" name="email" type="email" maxlength="254" autocomplete="email" required>
            </div>
          </div>
          <div class="form-field">
            <label for="contact-subject">Topik</label>
            <input id="contact-subject" name="subject" type="text" maxlength="160" required>
          </div>
          <div class="form-field">
            <label for="contact-message">Pesan</label>
            <textarea id="contact-message" name="message" maxlength="5000" required></textarea>
          </div>
          <button class="button button-primary" type="submit" <?= $pdo === null ? 'disabled' : '' ?>>Kirim pesan <span aria-hidden="true">↗</span></button>
        </form>
      </div>
    </section>
  </main>

  <footer>
    <div class="footer-inner shell">
      <div><strong><?= e($profile['school_name']) ?></strong> <span>· <?= e($profile['location']) ?></span></div>
      <div class="footer-links"><a href="#profil">Profil</a><a href="#keunggulan">Keunggulan</a><a href="#lokasi">Lokasi</a><a href="#kontak">Kontak</a><a href="login.php" style="color: var(--cyan); font-weight: 600;">Portal Login</a></div>
      <div>© <span id="year"></span> <?= e($profile['school_name']) ?></div>
    </div>
  </footer>

  <script>
    const menuButton = document.querySelector(".menu-toggle");
    const navLinks = document.querySelector(".nav-links");

    menuButton.addEventListener("click", () => {
      const isOpen = menuButton.getAttribute("aria-expanded") === "true";
      menuButton.setAttribute("aria-expanded", String(!isOpen));
      menuButton.setAttribute("aria-label", isOpen ? "Buka navigasi" : "Tutup navigasi");
      navLinks.classList.toggle("is-open", !isOpen);
    });

    navLinks.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        navLinks.classList.remove("is-open");
        menuButton.setAttribute("aria-expanded", "false");
        menuButton.setAttribute("aria-label", "Buka navigasi");
      });
    });

    document.querySelector("#year").textContent = new Date().getFullYear();

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            currentObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.14 });

      document.querySelectorAll(".reveal").forEach((element) => observer.observe(element));
    } else {
      document.querySelectorAll(".reveal").forEach((element) => element.classList.add("is-visible"));
    }
  </script>
</body>
</html>
