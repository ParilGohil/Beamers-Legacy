<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();

$configCars = [];
$configCatalogError = '';
try {
    $configConn = db();
    app_ensure_car_catalog_schema($configConn);
    $configResult = $configConn->query("SELECT `id`, `name`, `series`, `vehicle_type`, `price`, `power`, `acceleration`, `torque`, `drive_or_range`, `metric_label`, `image` FROM `cars` WHERE `status` = 'active' ORDER BY `id`");
    $configRows = $configResult ? $configResult->fetch_all(MYSQLI_ASSOC) : [];
    foreach ($configRows as $row) {
        $imagePath = trim((string)($row['image'] ?? ''));
        if (preg_match('#^https://#i', $imagePath) && filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $imageUrl = $imagePath;
        } else {
            $imagePath = str_replace('\\', '/', $imagePath);
            $imageUrl = preg_match('#^images/[A-Za-z0-9._ /-]+$#', $imagePath) && !str_contains($imagePath, '..')
                ? '../' . implode('/', array_map('rawurlencode', explode('/', $imagePath)))
                : '';
        }
        $price = trim((string)($row['price'] ?? ''));
        if (preg_match('/^\d+$/', $price)) {
            $digits = ltrim($price, '0');
            $digits = $digits === '' ? '0' : $digits;
            if (strlen($digits) > 3) {
                $leading = substr($digits, 0, -3);
                $lastThree = substr($digits, -3);
                $leading = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $leading) ?? $leading;
                $price = '₹' . $leading . ',' . $lastThree;
            } else {
                $price = '₹' . $digits;
            }
        }
        $configCars[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'series' => (string)$row['series'],
            'vehicleType' => (string)($row['vehicle_type'] ?? ''),
            'price' => $price,
            'power' => trim((string)($row['power'] ?? '')),
            'acceleration' => trim((string)($row['acceleration'] ?? '')),
            'torque' => trim((string)($row['torque'] ?? '')),
            'driveOrRange' => trim((string)($row['drive_or_range'] ?? '')),
            'metricLabel' => trim((string)($row['metric_label'] ?? 'Drive')),
            'image' => $imageUrl,
        ];
    }
} catch (Throwable $error) {
    error_log('BMW configurator catalogue could not be loaded: ' . $error->getMessage());
    $configCatalogError = 'Vehicle specifications are temporarily unavailable. Please try again later.';
}

$requestedCarId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
$selectedConfigCar = null;
foreach ($configCars as $configCar) {
    if ($requestedCarId && $configCar['id'] === $requestedCarId) {
        $selectedConfigCar = $configCar;
        break;
    }
}
$selectedConfigCar = $selectedConfigCar ?? ($configCars[0] ?? null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BMW Configure &amp; Price | Beamers Legacy</title>
  <meta name="description" content="Configure your custom BMW model. Personalize variants, exterior color options, wheels, interior leather elements, and performance packages." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* Configurator Specific Styles */
    .configurator-app {
      padding: 130px 0 100px;
      min-height: 100vh;
      background-color: var(--bg-primary);
    }
    
    .configulator-holder {
      width: 92%;
      max-width: 1350px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1.3fr 1fr;
      gap: 50px;
      align-items: start;
    }
    
    /* Left Column Viewport */
    .config-viewport {
      position: sticky;
      top: 130px;
      display: flex;
      flex-direction: column;
      gap: 30px;
    }
    
    .config-viewport-images {
      width: 100%;
      height: 480px;
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      overflow: hidden;
    }
    
    .config-viewport-images img {
      position: absolute;
      width: 100%;
      max-width: 650px;
      height: auto;
      object-fit: contain;
      pointer-events: none;
      transition: none;
    }
    
    .config-viewport-images #vehicle-img-top {
      z-index: 2;
    }
    
    .config-viewport-images #vehicle-img-bottom {
      z-index: 1;
    }

    /* Color-wash overlay: gives real-time visual feedback for the selected
       paint finish without swapping in an unrelated model's photo when a
       per-color studio shot doesn't exist in the asset library. */
    .config-color-wash {
      position: absolute;
      inset: 0;
      z-index: 3;
      pointer-events: none;
      mix-blend-mode: color;
      opacity: 0.55;
      background: var(--config-wash-color, transparent);
      transition: background-color 0.5s ease, opacity 0.5s ease;
    }

    .config-viewport-images::before {
      content: '';
      position: absolute;
      inset: 0;
      z-index: 4;
      pointer-events: none;
      background: radial-gradient(ellipse at center, transparent 45%, rgba(0,0,0,0.55) 100%);
    }
    
    /* Configurator specs bar bottom of viewport */
    .config-specs-bar {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      border: 1px solid var(--border-color);
      background-color: var(--bg-secondary);
      padding: 20px;
      text-align: center;
    }
    
    .config-specs-bar .spec-box {
      border-right: 1px solid var(--border-color);
    }
    
    .config-specs-bar .spec-box:last-child {
      border-right: none;
    }
    
    .config-specs-bar .spec-box span {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--text-primary);
    }
    
    .config-specs-bar .spec-box p {
      font-size: 0.65rem;
      text-transform: uppercase;
      font-weight: 700;
      color: var(--text-muted);
      letter-spacing: 0.1em;
      margin-top: 5px;
    }
    
    /* Right Column Options Menu */
    .config-options-panel {
      display: flex;
      flex-direction: column;
      gap: 35px;
    }
    
    .config-section-title {
      font-size: 1rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 12px;
      margin-bottom: 20px;
      color: var(--text-primary);
    }
    
    /* Color Swatch Options */
    .swatches-grid {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    
    .swatch-option {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 2px solid transparent;
      padding: 3px;
      cursor: pointer;
      transition: border var(--transition-fast);
      position: relative;
    }
    
    .swatch-option::after {
      content: attr(data-preview-name);
      position: absolute;
      top: -30px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      color: #fff;
      font-size: 0.55rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      padding: 3px 6px;
      text-transform: uppercase;
      white-space: nowrap;
      opacity: 0;
      pointer-events: none;
      transition: opacity var(--transition-fast);
    }
    
    .swatch-option:hover::after {
      opacity: 1;
    }
    
    .swatch-option.active {
      border-color: var(--bmw-blue);
    }
    
    .swatch-color-fill {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.1);
    }
    
    /* Button selectors for wheels/interior */
    .selector-block-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .selection-button {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      width: 100%;
      padding: 16px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      text-align: left;
      transition: border var(--transition-fast), background var(--transition-fast);
    }
    
    .selection-button.active {
      border-color: var(--bmw-blue);
      background-color: rgba(28, 105, 212, 0.04);
    }
    
    .selection-button:hover {
      border-color: var(--border-highlight);
    }
    
    .selection-button-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-primary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .selection-button-desc {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 4px;
    }
    
    .selection-button-price {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-primary);
    }
    
    /* Packages checklists */
    .package-grid {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .package-checkbox-card {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      padding: 20px;
      display: flex;
      gap: 15px;
      align-items: flex-start;
      cursor: pointer;
      transition: border var(--transition-fast);
    }
    
    .package-checkbox-card:hover {
      border-color: var(--border-highlight);
    }
    
    .package-checkbox-card input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--bmw-blue);
      margin-top: 4px;
    }
    
    .package-card-desc h4 {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 4px;
    }
    
    .package-card-desc p {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-bottom: 8px;
    }
    
    .package-card-desc span {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-primary);
    }
    
    /* Bottom Sticky Calculation Bar */
    .config-calculation-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: var(--bg-secondary);
      border-top: 1px solid var(--border-color);
      padding: 20px 50px;
      z-index: 850;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .calculation-wrap {
      display: flex;
      gap: 40px;
    }
    
    .calculation-item {
      display: flex;
      flex-direction: column;
    }
    
    .calculation-item span {
      font-size: 0.6rem;
      text-transform: uppercase;
      font-weight: 700;
      color: var(--text-dark);
      letter-spacing: 0.1em;
    }
    
    .calculation-item b {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--text-primary);
    }
    
    .btn-book-checkout {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 16px 45px;
      border: none;
      background-color: var(--bmw-blue);
      color: #fff;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      cursor: pointer;
      transition: background-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    
    .btn-book-checkout:hover {
      background-color: var(--bmw-blue-hover);
      box-shadow: 0 0 20px var(--bmw-blue-glow);
    }

    @media (max-width: 1024px) {
      .configulator-holder {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .config-viewport {
        position: static;
      }
      .config-calculation-bar {
        padding: 20px;
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      .calculation-wrap {
        justify-content: center;
        gap: 30px;
      }
      .configurator-app {
        padding-bottom: 180px;
      }
    }

    .configurator-app {
      padding: 104px 0 120px;
      min-height: 100vh;
      background: #f5f5f5;
      color: #16191c;
    }
    .config-page-heading {
      width: min(1500px, calc(100% - 64px));
      margin: 0 auto 18px;
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 24px;
    }
    .config-page-heading p {
      margin: 0 0 7px;
      color: #666d73;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .18em;
      text-transform: uppercase;
    }
    .config-page-heading h1 {
      margin: 0;
      color: #111417;
      font-size: clamp(1.65rem, 3vw, 2.6rem);
      font-weight: 400;
      letter-spacing: -.04em;
    }
    .configurator-error {
      width: min(1500px, calc(100% - 64px));
      margin: 18px auto;
      padding: 16px 20px;
      border-left: 3px solid #b42318;
      background: #fff;
      color: #742a24;
    }
    .configulator-holder {
      width: 100%;
      max-width: none;
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .config-viewport {
      position: relative;
      top: auto;
      display: block;
      width: 100%;
    }
    .config-viewport-images {
      height: clamp(470px, 67vh, 760px);
      min-height: 470px;
      border: 0;
      background:
        radial-gradient(ellipse at 49% 63%, rgba(255,255,255,.1) 0, rgba(255,255,255,0) 43%),
        linear-gradient(180deg, #f2f1ef 0%, #e8e6e3 74%, #d9d7d4 75%, #efeeec 75.4%, #f5f5f5 100%);
    }
    .config-viewport-images img {
      width: min(88vw, 1320px);
      max-width: 1320px;
      height: 88%;
      object-fit: contain;
      filter: drop-shadow(0 28px 28px rgba(20, 22, 24, .14));
      pointer-events: none;
      transition: transform .45s ease, opacity .45s ease;
    }
    .config-viewport-images #vehicle-img-bottom { display: none; }
    .config-viewport-images::before {
      background: linear-gradient(180deg, transparent 0 70%, rgba(245,245,245,.12) 100%);
    }
    .config-color-wash {
      mix-blend-mode: multiply;
      opacity: .16;
      -webkit-mask-image: radial-gradient(ellipse 42% 31% at 50% 62%, #000 45%, transparent 100%);
      mask-image: radial-gradient(ellipse 42% 31% at 50% 62%, #000 45%, transparent 100%);
    }
    .config-viewport-images.config-detail-view #vehicle-img-top { transform: scale(1.16); }
    .config-stage-label {
      position: absolute;
      z-index: 5;
      top: clamp(24px, 5vw, 68px);
      left: max(32px, calc((100vw - 1440px) / 2));
      max-width: 360px;
      color: #171a1d;
      pointer-events: none;
    }
    .config-stage-label span {
      display: block;
      margin-bottom: 9px;
      color: #5b6268;
      font-size: .68rem;
      font-weight: 700;
      letter-spacing: .18em;
      text-transform: uppercase;
    }
    .config-stage-label strong {
      display: block;
      font-size: clamp(1.5rem, 3vw, 2.3rem);
      font-weight: 400;
      letter-spacing: -.035em;
    }
    .config-view-toggle {
      position: absolute;
      z-index: 6;
      top: 28px;
      right: max(32px, calc((100vw - 1440px) / 2));
      width: 58px;
      height: 58px;
      border: 1px solid rgba(30,32,34,.22);
      border-radius: 50%;
      background: rgba(255,255,255,.84);
      color: #1a1d20;
      cursor: pointer;
      font-size: .7rem;
      font-weight: 800;
      line-height: 1.1;
    }
    .config-view-toggle:hover,
    .config-view-toggle:focus-visible {
      outline: 2px solid #1769d2;
      outline-offset: 2px;
    }
    .config-specs-bar {
      width: min(1100px, calc(100% - 64px));
      margin: -2px auto 0;
      padding: 18px 12px 22px;
      border: 0;
      border-bottom: 1px solid #d8dadd;
      background: transparent;
      color: #171a1d;
    }
    .config-specs-bar .spec-box { border-color: #d4d6d8; }
    .config-specs-bar .spec-box span {
      color: #16191c;
      font-size: clamp(1.1rem, 2vw, 1.55rem);
    }
    .config-specs-bar .spec-box p { color: #626970; }
    .config-options-panel {
      width: min(1320px, calc(100% - 64px));
      margin: 36px auto 30px;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }
    .config-options-panel > div {
      padding: 24px;
      border: 1px solid #e0e2e4;
      background: #fff;
    }
    .config-section-title {
      margin-bottom: 18px;
      border-color: #e3e5e7;
      color: #202428;
      font-size: .82rem;
      letter-spacing: .12em;
    }
    .configurator-app .form-group-premium label { color: #444a50; }
    .configurator-app select {
      min-height: 48px;
      border: 1px solid #cfd3d6;
      background: #fff;
      color: #16191c;
    }
    .swatches-grid { gap: 18px; }
    .swatch-option {
      width: 48px;
      height: 48px;
      border-color: #d4d7d9;
    }
    .swatch-option.active {
      border-color: #1769d2;
      box-shadow: 0 0 0 2px #fff, 0 0 0 3px #1769d2;
    }
    .selection-button,
    .package-checkbox-card {
      border-color: #e0e2e4;
      background: #fff;
    }
    .selection-button-title,
    .package-card-desc h4 { color: #252a2e; }
    .selection-button-desc,
    .package-card-desc p { color: #636a70; }
    .selection-button-price,
    .package-card-desc span {
      color: #4c545a;
      white-space: nowrap;
    }
    .config-calculation-bar {
      min-height: 88px;
      padding: 12px max(32px, calc((100vw - 1440px) / 2));
      border-top: 1px solid #d8dadd;
      background: rgba(255,255,255,.97);
      color: #171a1d;
      box-shadow: 0 -8px 28px rgba(18,22,26,.06);
      backdrop-filter: blur(16px);
    }
    .calculation-wrap { align-items: center; gap: 0; }
    .calculation-item {
      min-width: 230px;
      padding-right: 28px;
    }
    .calculation-item span {
      color: #626970;
      letter-spacing: .07em;
    }
    .calculation-item b {
      color: #171a1d;
      font-size: 1.28rem;
      font-weight: 500;
    }
    .config-bar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .config-bar-link,
    .btn-book-checkout {
      min-height: 48px;
      padding: 12px 20px;
      border: 1px solid #1769d2;
      background: #1769d2;
      color: #fff;
      font-size: .72rem;
      letter-spacing: .08em;
      text-align: center;
      text-decoration: none;
    }
    .config-bar-link.secondary {
      border-color: #c8cdd1;
      background: #fff;
      color: #171a1d;
    }
    .config-bar-link:hover,
    .btn-book-checkout:hover {
      background: #0d56b3;
      color: #fff;
    }
    .config-save-status {
      position: fixed;
      z-index: 1000;
      right: 24px;
      bottom: 105px;
      padding: 12px 16px;
      border-radius: 3px;
      background: #202428;
      color: #fff;
      font-size: .85rem;
      opacity: 0;
      pointer-events: none;
      transform: translateY(8px);
      transition: opacity .2s ease, transform .2s ease;
    }
    .config-save-status.is-visible {
      opacity: 1;
      transform: translateY(0);
    }
    .config-empty-state {
      width: min(900px, calc(100% - 48px));
      margin: 60px auto;
      padding: 40px;
      background: #fff;
      text-align: center;
    }

    @media (max-width: 1024px) {
      .configurator-app { padding-bottom: 130px; }
    }
    @media (max-width: 680px) {
      .configurator-app { padding-top: 88px; }
      .config-page-heading { width: calc(100% - 36px); margin-bottom: 8px; }
      .config-page-heading p { font-size: .62rem; }
      .config-viewport-images { height: 54vh; min-height: 360px; max-height: 540px; }
      .config-viewport-images img { width: 112vw; height: 76%; }
      .config-stage-label { top: 20px; left: 18px; }
      .config-stage-label span { font-size: .58rem; }
      .config-stage-label strong { font-size: 1.55rem; }
      .config-view-toggle { top: 16px; right: 18px; width: 48px; height: 48px; }
      .config-specs-bar { width: calc(100% - 30px); padding: 12px 0 17px; }
      .config-specs-bar .spec-box span { font-size: 1rem; }
      .config-specs-bar .spec-box p { font-size: .55rem; letter-spacing: .04em; }
      .config-options-panel { width: calc(100% - 30px); margin: 22px auto; grid-template-columns: 1fr; gap: 12px; }
      .config-options-panel > div { padding: 18px 16px; }
      .config-calculation-bar { min-height: auto; padding: 10px 14px; gap: 10px; }
      .calculation-wrap { width: 100%; justify-content: space-between; }
      .calculation-item { min-width: 0; padding-right: 8px; }
      .calculation-item span { font-size: .54rem; }
      .calculation-item b { font-size: 1rem; }
      .config-bar-actions { width: 100%; }
      .config-bar-link, .btn-book-checkout { flex: 1; min-width: 0; min-height: 42px; padding: 10px 8px; font-size: .58rem; }
      .config-save-status { right: 14px; bottom: 115px; }
    }
  </style>
</head>

<body>

  <!-- ==========================================================================
       GLOBAL NAVBAR Header
       ========================================================================== */ -->
  <?php require_once __DIR__ . '/navbar.php'; ?>

  <!-- ==========================================================================
       FULLSCREEN OVERLAY MENU
       ========================================================================== */ -->
  <div class="fs-menu-overlay">
    <div class="fs-menu-container">
      <div class="fs-menu-links">
        <a href="models.php" data-preview="img-m5">BMW Models</a>
        <a href="all lineup.php" data-preview="img-lineup">Showroom Lineup</a>
        <a href="Electric.php" data-preview="img-electric">Electric Driving</a>
        <a href="configrator.php" data-preview="img-configurator">Configure &amp; Price</a>
        <a href="visit online shop.php" data-preview="img-lifestyle">Lifestyle Shop</a>
        <a href="customer support.php" data-preview="img-support">Support Center</a>
        <a href="testdrive.php" data-preview="img-testdrive">Book Test Drive</a>
      </div>
      <div class="fs-menu-preview">
        <img id="img-m5" src="../images/M8 compi.png" class="active" alt="BMW Models" />
        <img id="img-lineup" src="../images/m4 cs.png" alt="Showroom Lineup" />
        <img id="img-electric" src="../images/ix drive50.png" alt="Electric Driving" />
        <img id="img-configurator" src="../images/bmw color 1.jpg" alt="Configurator" />
        <img id="img-lifestyle" src="../images/shop 1.jpg" alt="Lifestyle Shop" />
        <img id="img-support" src="../images/contact 1.webp" alt="Support Center" />
        <img id="img-testdrive" src="../images/m4bg.jpg" alt="Book Test Drive" />
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       CONFIGURATOR CORE APPLICATION
       ========================================================================== */ -->
  <main class="configurator-app">
    <div class="config-page-heading">
      <div>
        <p>Beamers Legacy / Personalisation Studio</p>
        <h1>Make it yours.</h1>
      </div>
    </div>
    <?php if ($configCatalogError !== ''): ?>
      <p class="configurator-error" role="alert"><?= e($configCatalogError) ?></p>
    <?php elseif (!$configCars): ?>
      <div class="config-empty-state">
        <h2>No vehicles are available to configure right now.</h2>
        <p>Please check back later or contact our showroom for assistance.</p>
        <a class="btn-primary" href="customer support.php">Contact the showroom</a>
      </div>
    <?php endif; ?>
    <?php if ($selectedConfigCar): ?>
    <script id="configurator-catalog" type="application/json"><?= json_encode($configCars, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <div class="configulator-holder">
      
      <!-- Left Column Viewport & Specs -->
      <div class="config-viewport">
        <div class="config-viewport-images">
          <img id="vehicle-img-top" src="<?= e((string)$selectedConfigCar['image']) ?>" alt="<?= e($selectedConfigCar['name']) ?> in the vehicle configurator" />
          <img id="vehicle-img-bottom" src="" alt="" aria-hidden="true" />
          <div id="config-color-wash" class="config-color-wash"></div>
          <div class="config-stage-label">
            <span id="config-stage-series"><?= e($selectedConfigCar['series']) ?></span>
            <strong id="config-stage-name"><?= e($selectedConfigCar['name']) ?></strong>
          </div>
          <button class="config-view-toggle" id="config-view-toggle" type="button" aria-pressed="false" aria-label="Zoom vehicle view">DETAIL<br>VIEW</button>
        </div>
        
        <div class="config-specs-bar">
          <div class="spec-box">
            <span id="spec-val-hp"><?= e($selectedConfigCar['power'] ?: '—') ?></span>
            <p>Power</p>
          </div>
          <div class="spec-box">
            <span id="spec-val-acc"><?= e($selectedConfigCar['acceleration'] ?: '—') ?></span>
            <p>0-100 km/h</p>
          </div>
          <div class="spec-box">
            <span id="spec-val-nm"><?= e($selectedConfigCar['torque'] ?: $selectedConfigCar['driveOrRange'] ?: '—') ?></span>
            <p id="spec-label-nm"><?= e($selectedConfigCar['torque'] ? 'Torque' : ($selectedConfigCar['metricLabel'] ?: 'Drive')) ?></p>
          </div>
        </div>
      </div>
      
      <!-- Right Column Controls Panel -->
      <div class="config-options-panel">
        
        <!-- SECTION 1: Choose a vehicle -->
        <div>
          <div class="config-section-title">01 / Choose your BMW</div>
          
          <div class="form-group-premium" style="margin-bottom: 20px;">
            <label for="config-model">Select Model Lineup</label>
            <select id="config-model">
              <?php foreach ($configCars as $configCar): ?>
                <option value="<?= (int)$configCar['id'] ?>"<?= $configCar['id'] === $selectedConfigCar['id'] ? ' selected' : '' ?>><?= e($configCar['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- SECTION 2: Exterior Color Swatches -->
        <div>
          <div class="config-section-title">02 / Exterior paintwork</div>
          <div class="swatches-grid">
            <!-- White Color Button -->
            <button class="swatch-option" type="button" data-color="white" data-preview-name="Alpine White" aria-label="Alpine White" aria-pressed="false">
              <div class="swatch-color-fill" style="background-color: #f7f9fa;"></div>
            </button>
            
            <!-- Toronto Red Color Button -->
            <button class="swatch-option active" type="button" data-color="red" data-preview-name="Toronto Red Met." aria-label="Toronto Red" aria-pressed="true">
              <div class="swatch-color-fill" style="background-color: #b21f24;"></div>
            </button>
            
            <!-- Portimao Blue Color Button -->
            <button class="swatch-option" type="button" data-color="blue" data-preview-name="Portimao Blue Met." aria-label="Portimao Blue" aria-pressed="false">
              <div class="swatch-color-fill" style="background-color: #174291;"></div>
            </button>
            
            <!-- Isle of Man Green Color Button -->
            <button class="swatch-option" type="button" data-color="green" data-preview-name="Isle of Man Green Met." aria-label="Isle of Man Green" aria-pressed="false">
              <div class="swatch-color-fill" style="background-color: #154c3e;"></div>
            </button>
            
            <!-- Black Sapphire Color Button -->
            <button class="swatch-option" type="button" data-color="black" data-preview-name="Black Sapphire Met." aria-label="Black Sapphire" aria-pressed="false">
              <div class="swatch-color-fill" style="background-color: #0b0c0c;"></div>
            </button>
          </div>
        </div>

        <!-- SECTION 3: Wheels Selector -->
        <div>
          <div class="config-section-title">03 / Wheel configurations</div>
          <div class="selector-block-list">
            <!-- Wheel Option 1 -->
            <button class="selection-button wheel-option active" type="button" data-type="m-orbit" aria-pressed="true">
              <div class="selection-button-detail">
                <div class="selection-button-title">19"/20" M Star-spoke Double Line</div>
                <div class="selection-button-desc">Orbit Grey Finished alloy wheel</div>
              </div>
              <div class="selection-button-price">Price on request</div>
            </button>
            
            <!-- Wheel Option 2 -->
            <button class="selection-button wheel-option" type="button" data-type="m-bicolor" aria-pressed="false">
              <div class="selection-button-detail">
                <div class="selection-button-title">20" M Double-spoke Bi-color</div>
                <div class="selection-button-desc">Jet Black finish diamond cut rims</div>
              </div>
              <div class="selection-button-price">Price on request</div>
            </button>
            
            <!-- Wheel Option 3 -->
            <button class="selection-button wheel-option" type="button" data-type="indiv-multi" aria-pressed="false">
              <div class="selection-button-detail">
                <div class="selection-button-title">21" BMW Individual Multi-spoke</div>
                <div class="selection-button-desc">Forged alloy weight-optimized layout</div>
              </div>
              <div class="selection-button-price">Price on request</div>
            </button>
          </div>
        </div>

        <!-- SECTION 4: Interior Leather selection -->
        <div>
          <div class="config-section-title">04 / Upholstery & trims</div>
          <div class="selector-block-list">
            <!-- Interior Option 1 -->
            <button class="selection-button interior-option active" type="button" data-type="leather-black" aria-pressed="true">
              <div class="selection-button-detail">
                <div class="selection-button-title">Extended Merino Leather Black</div>
                <div class="selection-button-desc">Solid anthracite carbon dashboard trims</div>
              </div>
              <div class="selection-button-price">Price on request</div>
            </button>
            
            <!-- Interior Option 2 -->
            <button class="selection-button interior-option" type="button" data-type="leather-blue" aria-pressed="false">
              <div class="selection-button-detail">
                <div class="selection-button-title">Yas Marina Blue / Yellow Accents</div>
                <div class="selection-button-desc">Extended carbon structure trims and bucket layout</div>
              </div>
              <div class="selection-button-price">Price on request</div>
            </button>
            
            <!-- Interior Option 3 -->
            <button class="selection-button interior-option" type="button" data-type="leather-cognac" aria-pressed="false">
              <div class="selection-button-detail">
                <div class="selection-button-title">Silverstone / Cognac Merino Leather</div>
                <div class="selection-button-desc">Alcantara headliners & brushed chrome finishes</div>
              </div>
              <div class="selection-button-price">Price on request</div>
            </button>
          </div>
        </div>

        <!-- SECTION 5: Add Packages -->
        <div>
          <div class="config-section-title">05 / optional packages</div>
          <div class="package-grid">
            <!-- Package Checkbox 1 -->
            <label class="package-checkbox-card">
              <input type="checkbox" class="package-toggle" value="driving-assistance" />
              <div class="package-card-desc">
                <h4>Driving Assistance Prof. Pack</h4>
                <p>Includes Traffic Jam Assist, active cruise steering controls, emergency lane support systems.</p>
                <span>Price on request</span>
              </div>
            </label>
            
            <!-- Package Checkbox 2 -->
            <label class="package-checkbox-card">
              <input type="checkbox" class="package-toggle" value="executive" />
              <div class="package-card-desc">
                <h4>Executive Package Upgrade</h4>
                <p>Includes heated steering wheel, power trunklid, headup display, gestural controls.</p>
                <span>Price on request</span>
              </div>
            </label>
            
            <!-- Package Checkbox 3 -->
            <label class="package-checkbox-card">
              <input type="checkbox" class="package-toggle" value="m-carbon-ceramic-brakes" />
              <div class="package-card-desc">
                <h4>M Carbon Ceramic Brakes</h4>
                <p>Gold calipers with lightweight hybrid composites for fade-free high deceleration.</p>
                <span>Price on request</span>
              </div>
            </label>
          </div>
        </div>

      </div>
    </div>
    <?php endif; ?>
  </main>

  <!-- ==========================================================================
       BOTTOM CALCULATOR STICKY ROW
       ========================================================================== */ -->
  <?php if ($selectedConfigCar): ?>
  <div class="config-calculation-bar">
    <div class="calculation-wrap">
      <div class="calculation-item">
        <span>Starting price · options priced by showroom</span>
        <b id="price-base"><?= e($selectedConfigCar['price'] ?: 'Price on request') ?></b>
      </div>
    </div>
    
    <div class="config-bar-actions">
      <button type="button" id="config-save" class="config-bar-link secondary"><i class="fa-regular fa-bookmark" aria-hidden="true"></i> Save</button>
      <a href="customer support.php" class="config-bar-link secondary">Contact</a>
      <a href="testdrive.php" id="btn-book-config" class="config-bar-link secondary">Book a test drive</a>
      <a href="booking.php?car_id=<?= (int)$selectedConfigCar['id'] ?>" id="btn-enquire-config" class="btn-book-checkout">Enquire to buy</a>
    </div>
  </div>
  <p class="config-save-status" id="config-save-status" role="status" aria-live="polite">Configuration saved on this device.</p>
  <?php endif; ?>

  <!-- ==========================================================================
       EXTERNAL ENGINE SCRIPTS
       ========================================================================== */ -->
  <!-- GSAP Core -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  
  <!-- Lenis Smooth scroll -->
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.36/dist/lenis.min.js"></script>
  
  <!-- Shared code blocks -->
  <script src="../js/premium.js"></script>
  <script src="../js/configurator.js?v=20261025-studio-3"></script>

</body>

</html>