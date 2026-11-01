<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BMW Services | Beamers Legacy</title>
  <meta name="description" content="Book a BMW test drive or request maintenance and repair service at Beamers Legacy.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
  <style>
    .services-page{min-height:100vh;padding:160px 0 100px;background:var(--bg-primary)}.services-holder{width:min(90%,1100px);margin:auto}.services-eyebrow{margin:0 0 16px;color:var(--bmw-blue);font-size:.75rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase}.services-holder h1{max-width:12ch;margin:0 0 14px;font-size:clamp(2.3rem,5vw,4.3rem);line-height:1;letter-spacing:-.04em}.services-intro{max-width:62ch;margin:0 0 44px;color:var(--text-muted);font-size:1.05rem}.services-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}.service-option{display:flex;min-height:320px;flex-direction:column;align-items:flex-start;padding:clamp(28px,4vw,46px);border:1px solid var(--border-color);background:var(--bg-secondary);transition:transform .2s ease,border-color .2s ease}.service-option:hover{transform:translateY(-4px);border-color:var(--bmw-blue)}.service-option i{margin-bottom:48px;color:var(--bmw-blue);font-size:2.2rem}.service-option h2{margin:0 0 12px;font-size:clamp(1.6rem,3vw,2.3rem);letter-spacing:-.03em}.service-option p{margin:0 0 28px;color:var(--text-muted);line-height:1.6}.service-option .btn-primary{margin-top:auto}.service-note{margin:30px 0 0;color:var(--text-dark);font-size:.85rem}@media(max-width:700px){.services-page{padding:130px 0 70px}.services-grid{grid-template-columns:1fr}.service-option{min-height:270px}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="services-page">
  <div class="services-holder">
    <p class="services-eyebrow">Beamers Legacy / Services</p>
    <h1>How can we help?</h1>
    <p class="services-intro">Whether you are considering your next BMW or caring for the one you already own, choose the service you need and our team will take it from there.</p>
    <div class="services-grid">
      <article class="service-option">
        <i class="fa-solid fa-steering-wheel" aria-hidden="true"></i>
        <h2>Book a test drive</h2>
        <p>Choose a BMW, share your preferred time, and experience it with one of our product specialists.</p>
        <a class="btn-primary" href="testdrive.php">Book a test drive <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </article>
      <article class="service-option">
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
        <h2>Request car service</h2>
        <p>Request maintenance, repairs, tyres, brakes, or bodywork for your BMW. We will confirm your service appointment.</p>
        <a class="btn-primary" href="carservice.php">Request car service <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </article>
    </div>
    <p class="service-note">Need help choosing? Visit <a href="customer support.php">Customer Support</a> or call your local Beamers Legacy service team.</p>
  </div>
</main>
<script src="../js/premium.js"></script>
</body>
</html>
