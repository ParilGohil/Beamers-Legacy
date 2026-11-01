<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Privacy Policy | Beamers Legacy</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
<style>
  .legal-page{background:var(--bg-primary);color:var(--text-primary);min-height:100vh;padding:170px 20px 100px}
  .legal-page > div{max-width:820px;margin:0 auto}
  .legal-page h1{font-size:clamp(2rem,4vw,2.8rem);font-weight:800;letter-spacing:-0.02em;margin:10px 0 30px}
  .legal-page h2{font-size:1.1rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin:36px 0 12px;color:var(--text-primary)}
  .legal-page p{color:var(--text-muted);line-height:1.8;font-size:0.95rem}
  .legal-page .section-subtitle{color:var(--bmw-blue);font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.3em}
  .legal-page a.back-link{display:inline-block;margin-top:40px;color:var(--bmw-blue);font-size:0.85rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em}
</style>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="legal-page">
  <div>
    <p class="section-subtitle">Beamers Legacy</p>
    <h1>Privacy Policy</h1>
    <p>Beamers Legacy uses information submitted through account, test-drive and feedback forms to provide requested showroom services and maintain customer records.</p>
    <h2>Information we collect</h2>
    <p>Depending on the service, this may include your name, email address, phone number, city, selected vehicle, finance interest and feedback. Passwords are stored as secure hashes for local accounts.</p>
    <h2>Your information</h2>
    <p>Do not submit information that is not necessary for the requested service. Contact the showroom administrator if you need a record corrected or removed.</p>
    <a class="back-link" href="home.php">&larr; Return to showroom</a>
  </div>
</main>
</body>
</html>
