<?php
require_once __DIR__ . '/config.php';
start_app_session();
$managedCars = [];
$catalogError = '';
try {
    $carConn = db();
    app_ensure_car_catalog_schema($carConn);
    $carResult = $carConn->query("SELECT * FROM cars WHERE status = 'active' ORDER BY id DESC");
    $managedCars = $carResult ? $carResult->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $error) {
    error_log('BMW model catalogue could not be loaded: ' . $error->getMessage());
    $catalogError = 'The vehicle catalogue is temporarily unavailable. Please try again later.';
}
function showroom_car_image(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    $path = str_replace('\\', '/', $path);
    if (str_starts_with($path, 'images/')) return '../' . $path;
    if (str_starts_with($path, '../') || str_starts_with($path, '/')) return $path;
    return '../' . ltrim($path, '/');
}
function showroom_managed_cars(array $cars, string $category): array {
    return array_values(array_filter($cars, static fn(array $car): bool => ($car['category'] ?? 'classic') === $category));
}
function showroom_render_managed_card(array $car): void {
    $categoryClass = ($car['category'] ?? '') === 'm' ? 'm-glow-hover' : (($car['category'] ?? '') === 'electric' ? 'ele-glow-hover' : '');
    $configureUrl = trim((string)($car['configure_url'] ?? '')) ?: 'configrator.php?car_id=' . (int)($car['id'] ?? 0);
    $detailsUrl = 'car-details.php?slug=' . rawurlencode((string)$car['slug']);
    ?>
    <article class="model-product-card <?= e($categoryClass) ?>" id="car-<?= (int)($car['id'] ?? 0) ?>">
      <a class="model-card-details-link" href="<?= e($detailsUrl) ?>" aria-label="View details for <?= e((string)$car['name']) ?>"></a>
      <div>
        <span class="series-lbl"><?= e((string)$car['series']) ?></span>
        <div class="vehicle-title-wrap">
          <h3><?= e((string)$car['name']) ?></h3>
          <span class="vehicle-type"><?= e((string)$car['vehicle_type']) ?></span>
        </div>
        <div class="vehicle-img-wrapper" data-model="<?= e((string)$car['name']) ?>">
          <?php if (!empty($car['image'])): ?>
            <img src="<?= e(showroom_car_image((string)$car['image'])) ?>" alt="<?= e((string)$car['name']) ?>" loading="lazy" />
          <?php else: ?>
            <i class="fa-solid fa-car-side managed-car-placeholder" aria-hidden="true"></i>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <div class="specs-table">
          <div class="spec-desc"><span class="val"><?= e((string)$car['power']) ?></span><span class="lbl">Power (HP)</span></div>
          <div class="spec-desc"><span class="val"><?= e((string)$car['acceleration']) ?></span><span class="lbl">0-100</span></div>
          <div class="spec-desc"><span class="val"><?= e((string)$car['drive_or_range']) ?></span><span class="lbl"><?= e((string)$car['metric_label']) ?></span></div>
        </div>
        <div class="price-row">
          <div class="price-wrap"><span>EST. Starting At</span><b><?= e((string)($car['price'] ?: 'Price on request')) ?></b></div>
          <a href="<?= e($configureUrl) ?>" class="btn-primary model-configure-button">Configure</a>
        </div>
      </div>
    </article>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BMW Showroom Showcase | Beamers Legacy</title>
  <meta name="description" content="Browse the complete catalog of legacy BMW models: from Motorsport High-Performance lines, flagship X series SUVs, to avant-garde fully electric i models." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* Catalog Specific Custom Styles */
    .models-hero-section {
      padding: 180px 0 60px;
      background-color: var(--bg-primary);
      text-align: center;
      border-bottom: 1px solid var(--border-color);
    }
    
    .models-subnav {
      position: sticky;
      top: var(--navbar-height-scrolled);
      width: 100%;
      background: rgba(5, 5, 5, 0.9);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border-color);
      z-index: 800;
      display: flex;
      justify-content: center;
      gap: 20px;
      padding: 18px 0;
    }
    
    .models-subnav a {
      color: var(--text-muted);
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      padding: 8px 18px;
      transition: color var(--transition-fast), border var(--transition-fast);
      border: 1px solid transparent;
    }
    
    .models-subnav a:hover {
      color: var(--text-primary);
    }
    
    .models-subnav a.active {
      color: var(--text-primary);
      border-color: var(--text-primary);
    }
    
    .models-section {
      padding: 100px 0;
      border-bottom: 1px solid var(--border-color);
    }
    
    .models-section-container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .models-grid-layout {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      margin-top: 50px;
    }
    
    .model-product-card {
      position: relative;
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      padding: 40px 15px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 500px;
      transition: border var(--transition-medium), box-shadow var(--transition-medium);
    }

    .model-card-details-link {
      position: absolute;
      inset: 0;
      z-index: 2;
    }
 
    .model-card-details-link:focus-visible {
      outline: 2px solid var(--bmw-blue);
      outline-offset: -4px;
    }

    .catalog-error {
      width: 90%;
      max-width: 1200px;
      margin: 18px auto;
      color: #ffb4b4;
    }

    .model-configure-button {
      position: relative;
      z-index: 3;
    }
    
    .model-product-card:hover {
      border-color: var(--border-highlight);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
    }
    
    .model-product-card .series-lbl {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.25em;
      color: var(--text-muted);
    }
    
    .model-product-card .vehicle-title-wrap {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-top: 5px;
      margin-bottom: 25px;
    }
    
    .model-product-card h3 {
      font-size: 1.4rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.01em;
    }
    
    .model-product-card .vehicle-type {
      font-size: 0.7rem;
      font-weight: 600;
      color: var(--text-dark);
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }
    
    .model-product-card .vehicle-img-wrapper {
      position: relative;
      width: 100%;
      height: 190px;
      flex: 0 0 190px;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 30px;
      overflow: hidden;
    }

    .model-product-card .vehicle-img-wrapper::before {
      content: attr(data-model);
      position: absolute;
      top: 16%;
      left: 50%;
      z-index: 2;
      width: max-content;
      color: rgba(255, 255, 255, 0.18);
      font-size: clamp(2rem, 4vw, 4rem);
      font-weight: 900;
      letter-spacing: -0.08em;
      line-height: 0.8;
      text-transform: uppercase;
      white-space: nowrap;
      pointer-events: none;
      transform: translateX(-50%);
    }
    
    .model-product-card .vehicle-img-wrapper img {
      display: block;
      position: relative;
      z-index: 1;
      width: 100%;
      height: 100%;
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform var(--transition-medium);
    }

    .model-product-card .vehicle-img-wrapper[data-model="BMW M4 CS"] img {
      transform: scale(1.4);
    }
    
    .model-product-card .specs-table {
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
      margin-bottom: 25px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
    }
    
    .model-product-card .spec-desc {
      display: flex;
      flex-direction: column;
    }
    
    .model-product-card .spec-desc .val {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--text-primary);
      line-height: 1.2;
    }
    
    .model-product-card .spec-desc .lbl {
      font-size: 0.6rem;
      font-weight: 600;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.05em;
    }
    
    .model-product-card .price-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
    }

    .model-configure-button {
      padding: 7px 12px;
      font-size: 0.65rem;
      letter-spacing: 0.12em;
    }
    
    .model-product-card .price-wrap {
      display: flex;
      flex-direction: column;
    }
    
    .model-product-card .price-wrap span {
      font-size: 0.55rem;
      font-weight: 700;
      color: var(--text-dark);
      text-transform: uppercase;
      letter-spacing: 0.15em;
    }
    
    .model-product-card .price-wrap b {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--text-primary);
    }

    @media (max-width: 1024px) {
      .models-grid-layout {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .models-grid-layout {
        grid-template-columns: 1fr;
      }
      .models-subnav {
        overflow-x: auto;
        justify-content: flex-start;
        padding: 12px 20px;
        top: 0; /* Fallback for small screen height */
      }
      .models-hero-section {
        padding: 120px 0 40px;
      }
    }
  
    .managed-catalog-section{padding:100px 0;border-top:1px solid var(--border-color);background:var(--bg-secondary)}
    .catalog-container{width:90%;max-width:1200px;margin:0 auto}
    .managed-catalog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:50px}
    .managed-car-card{border:1px solid var(--border-color);background:var(--bg-primary);overflow:hidden;transition:transform .25s ease,border-color .25s ease;scroll-margin-top:calc(var(--navbar-height-scrolled) + 30px)}
    .managed-car-card:hover{transform:translateY(-4px);border-color:var(--border-highlight)}
    .managed-car-card:target{border-color:var(--bmw-blue);box-shadow:0 0 0 1px var(--bmw-blue),0 0 40px var(--bmw-blue-glow)}
    .managed-car-image{height:220px;background:#080808;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .managed-car-image img{width:100%;height:100%;object-fit:cover}
    .managed-car-placeholder{font-size:3rem;color:var(--text-muted)}
    .managed-car-content{padding:24px}.managed-car-content>span{font-size:.65rem;color:var(--bmw-blue);font-weight:800;letter-spacing:.12em;text-transform:uppercase}.managed-car-content h3{font-size:1.5rem;margin:8px 0 12px}.managed-car-content p{color:var(--text-muted);min-height:48px}.managed-car-meta{display:flex;justify-content:space-between;gap:15px;border-top:1px solid var(--border-color);padding-top:18px;margin-top:18px}.managed-car-meta small{color:var(--text-muted)}
    @media(max-width:900px){.managed-catalog-grid{grid-template-columns:1fr 1fr}}@media(max-width:600px){.managed-catalog-grid{grid-template-columns:1fr}}

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
       CATALOG HERO SECTION
       ========================================================================== */ -->
  <div class="models-hero-section">
    <span class="section-subtitle">THE LINEUP</span>
    <h1 class="section-title text-gradient">DISCOVER YOUR MACHINE</h1>
  </div>

  <!-- Sticky segment selectors -->
  <div class="models-subnav">
    <a href="#m-perform" class="active">M Performance</a>
    <a href="#i-elec">i Electric</a>
    <a href="#x-suv">X Range SUVs</a>
    <a href="#heritage-classic">Classic Sedans</a>
  </div>
  <?php if ($catalogError !== ''): ?><p class="catalog-error" role="alert"><?= e($catalogError) ?></p><?php endif; ?>

  <!-- ==========================================================================
       SCENE 01 — MOTORSPORT HIGH-PERFORMANCE DIVISION
       ========================================================================== */ -->
  <section class="models-section" id="m-perform">
    <div class="models-section-container">
      <span class="section-subtitle">BMW M DEPT.</span>
      <h2 style="font-size: 2.2rem; font-weight: 800; text-transform: uppercase;">MOTORSPORT HERITAGE</h2>
      <p style="color: var(--text-muted); max-width: 600px; margin-top: 10px;">
        Designed on racetrack tarmac, fine-tuned on the Nordschleife. Every M model delivers raw engine responses, uncompromising steering chassis, and high agility.
      </p>

      <div class="models-grid-layout">
        <?php foreach (showroom_managed_cars($managedCars, 'm') as $managedCar): ?>
          <?php showroom_render_managed_card($managedCar); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ==========================================================================
       SCENE 02 — THE FUTURE IS ELECTRIC (i DIVISION)
       ========================================================================== */ -->
  <section class="models-section" id="i-elec">
    <div class="models-section-container">
      <span class="section-subtitle">BMW i CORE</span>
      <h2 style="font-size: 2.2rem; font-weight: 800; text-transform: uppercase;">THE ELECTRIC PIONEERS</h2>
      <p style="color: var(--text-muted); max-width: 600px; margin-top: 10px;">
        100% electrified, 100% aerodynamic, zero local tailpipe emissions. The BMW i architecture represents a new chapter of sheer driving joy.
      </p>

      <div class="models-grid-layout">
        <?php foreach (showroom_managed_cars($managedCars, 'electric') as $managedCar): ?>
          <?php showroom_render_managed_card($managedCar); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ==========================================================================
       SCENE 03 — COMMAND OVER SYSTEMS (X DIVISION)
       ========================================================================== */ -->
  <section class="models-section" id="x-suv">
    <div class="models-section-container">
      <span class="section-subtitle">BMW SAV</span>
      <h2 style="font-size: 2.2rem; font-weight: 800; text-transform: uppercase;">COMMAND THE AVENUES</h2>
      <p style="color: var(--text-muted); max-width: 600px; margin-top: 10px;">
        xDrive all-wheel drive, intelligent terrain assistance, and luxury space configurations for families and executives.
      </p>

      <div class="models-grid-layout">
        <?php foreach (showroom_managed_cars($managedCars, 'x') as $managedCar): ?>
          <?php showroom_render_managed_card($managedCar); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ==========================================================================
       SCENE 04 — SEDANS & COUPE CLASSICS
       ========================================================================== */ -->
  <section class="models-section" id="heritage-classic" style="border-bottom: none;">
    <div class="models-section-container">
      <span class="section-subtitle">BMW CORES</span>
      <h2 style="font-size: 2.2rem; font-weight: 800; text-transform: uppercase;">THE DRIVING FOUNDATIONS</h2>
      <p style="color: var(--text-muted); max-width: 600px; margin-top: 10px;">
        Refined interior space, dynamic weight distribution, and comfortable driving ergonomics.
      </p>

      <div class="models-grid-layout">
        <?php foreach (showroom_managed_cars($managedCars, 'classic') as $managedCar): ?>
          <?php showroom_render_managed_card($managedCar); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ==========================================================================
       GLOBAL PREMIUM DEEP FOOTER
       ========================================================================== */ -->
  <footer class="premium-footer">
    <div class="footer-top">
      <div class="footer-info">
        <h2>BEAMERS LEGACY</h2>
        <p>Authentic BMW dealership supplying the ultimate driving machines, precision maintenance, and custom accessories.</p>
      </div>

      <div class="footer-col">
        <h3>Showroom Pages</h3>
        <ul>
          <li><a href="home.php">Home</a></li>
          <li><a href="models.php">All Models</a></li>
          <li><a href="Electric.php">BMW Electric</a></li>
          <li><a href="all lineup.php">Showroom Lineup</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Services</h3>
        <ul>
          <li><a href="configrator.php">Configure &amp; Price</a></li>
          <li><a href="visit online shop.php">Lifestyle Shop</a></li>
          <li><a href="testdrive.php">Book Test Drive</a></li>
          <li><a href="customer support.php">Customer Service</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Department</h3>
        <ul>
          <?php if (!empty($_SESSION['user_id'])): ?>
            <li><a href="account.php">My Profile</a></li>
            <li><a href="logout.php">Sign Out</a></li>
          <?php else: ?>
            <li><a href="loginform.php">User Portal login</a></li>
            <li><a href="register.php">Create Account</a></li>
          <?php endif; ?>
          <li><a href="adminlogin.php">Staff & Admin Access</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2026 Beamers Legacy Dealership. Preserving Bavarian Precision. Built for scroll control.</p>
      <div class="footer-social-links">
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
  </footer>

  <!-- ==========================================================================
       EXTERNAL ENGINE SCRIPTS (CDNs & Custom Bundles)
       ========================================================================== */ -->
  <!-- GSAP Core & ScrollTrigger -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
  
  <!-- Lenis Smooth scroll -->
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.36/dist/lenis.min.js"></script>
  
  <!-- Core script engines -->
  <script src="../js/premium.js"></script>
  <script src="../js/models.js"></script>
</body>

</html>
