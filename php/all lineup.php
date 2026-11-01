<?php
require_once __DIR__ . '/config.php';
start_app_session();
$managedCars = [];
try {
    $carConn = db();
    $carResult = $carConn->query("SELECT id, name, series, price, fuel, transmission, image, description FROM cars WHERE status = 'active' ORDER BY id DESC");
    $managedCars = $carResult ? $carResult->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $e) {
    $managedCars = [];
}
function lineup_car_image(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    $path = str_replace('\\', '/', $path);
    if (str_starts_with($path, 'images/')) return '../' . $path;
    if (str_starts_with($path, '../') || str_starts_with($path, '/')) return $path;
    return '../' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BMW Complete Core Lineup | Beamers Legacy</title>
  <meta name="description" content="Browse the complete catalog of legacy BMW models: from Motorsport High-Performance lines, flagship X series SUVs, to fully electric i models." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* Complete Lineup specific custom styles */
    .lineup-hero {
      padding: 180px 0 60px;
      text-align: center;
      background-color: var(--bg-primary);
      border-bottom: 1px solid var(--border-color);
    }
    
    .lineup-grid-section {
      padding: 100px 0;
    }
    
    .lineup-container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .lineup-row-title {
      font-size: 1.6rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.01em;
      border-left: 3px solid var(--bmw-blue);
      padding-left: 15px;
      margin-bottom: 40px;
      color: var(--text-primary);
    }
    
    .lineup-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 25px;
      margin-bottom: 80px;
    }
    
    .lineup-card {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 420px;
      transition: border var(--transition-medium), transform var(--transition-medium);
    }
    
    .lineup-card:hover {
      border-color: var(--border-highlight);
      transform: translateY(-4px);
    }
    
    .lineup-card .lbl {
      font-size: 0.55rem;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.15em;
    }
    
    .lineup-card h3 {
      font-size: 1.15rem;
      font-weight: 800;
      text-transform: uppercase;
      margin-top: 4px;
      margin-bottom: 20px;
    }
    
    .lineup-card .img-wrap {
      width: 100%;
      height: 140px;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .lineup-card .img-wrap img {
      max-width: 110%;
      max-height: 100%;
      object-fit: contain;
      pointer-events: none;
      transition: transform 0.5s ease;
    }
    
    .lineup-card:hover .img-wrap img {
      transform: scale(1.04);
    }
    
    .lineup-card .spec-row {
      border-top: 1px solid var(--border-color);
      padding-top: 15px;
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    
    .lineup-card .spec-cell {
      display: flex;
      flex-direction: column;
    }
    
    .lineup-card .spec-cell b {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-primary);
    }
    
    .lineup-card .spec-cell span {
      font-size: 0.55rem;
      font-weight: 600;
      text-transform: uppercase;
      color: var(--text-muted);
    }
    
    .lineup-card .btn-wrap {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--border-color);
      padding-top: 15px;
    }
    
    .lineup-card .price {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-primary);
    }
    
    .lineup-card .btn-wrap a {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--bmw-blue);
      transition: color var(--transition-fast);
    }
    
    .lineup-card .btn-wrap a:hover {
      color: var(--text-primary);
    }

    @media (max-width: 1024px) {
      .lineup-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .lineup-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 480px) {
      .lineup-grid {
        grid-template-columns: 1fr;
      }
    }
  
    .managed-catalog-section{padding:100px 0;border-top:1px solid var(--border-color);background:var(--bg-secondary)}
    .managed-catalog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:50px}
    .managed-car-card{border:1px solid var(--border-color);background:var(--bg-primary);overflow:hidden;transition:transform .25s ease,border-color .25s ease}
    .managed-car-card:hover{transform:translateY(-4px);border-color:var(--border-highlight)}
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
       HERO LINEUP SHOWCASE
       ========================================================================== */ -->
  <div class="lineup-hero">
    <span class="section-subtitle">THE FULL SHOWROOM</span>
    <h1 class="section-title text-gradient">ALL BMW MODEL LINES</h1>
  </div>

  <!-- ==========================================================================
       LINEUP CATALOG GROUPS
       ========================================================================== */ -->
  <section class="lineup-grid-section">
    <div class="lineup-container">
      
      <!-- Group 1: M Performance -->
      <h2 class="lineup-row-title">BMW Motorsport (M Range)</h2>
      <div class="lineup-grid">
        <!-- M2 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">M division</span>
            <h3>BMW M2</h3>
            <div class="img-wrap">
              <img src="../images/m2.png" alt="BMW M2" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>460</b><span>HP</span></div>
              <div class="spec-cell"><b>4.1s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$63,200</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- M4 CS -->
        <div class="lineup-card">
          <div>
            <span class="lbl">M division</span>
            <h3>BMW M4 CS</h3>
            <div class="img-wrap">
              <img src="../images/m4 cs.png" alt="BMW M4 CS" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>550</b><span>HP</span></div>
              <div class="spec-cell"><b>3.4s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$123,500</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- M4 Compi -->
        <div class="lineup-card">
          <div>
            <span class="lbl">M division</span>
            <h3>BMW M4 Comp</h3>
            <div class="img-wrap">
              <img src="../images/m4 compi.png" alt="BMW M4 Competition" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>510</b><span>HP</span></div>
              <div class="spec-cell"><b>3.5s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$82,200</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- M8 Compi -->
        <div class="lineup-card">
          <div>
            <span class="lbl">M division</span>
            <h3>BMW M8 Comp</h3>
            <div class="img-wrap">
              <img src="../images/M8 compi.png" alt="BMW M8 Competition" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>625</b><span>HP</span></div>
              <div class="spec-cell"><b>3.2s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$134,850</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Group 2: i Electric Series -->
      <h2 class="lineup-row-title">BMW i Core (100% Electric)</h2>
      <div class="lineup-grid">
        <!-- i4 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">i series</span>
            <h3>BMW i4</h3>
            <div class="img-wrap">
              <img src="../images/i4.png" alt="BMW i4" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>340</b><span>HP</span></div>
              <div class="spec-cell"><b>301m</b><span>Range</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$57,300</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- i5 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">i series</span>
            <h3>BMW i5</h3>
            <div class="img-wrap">
              <img src="../images/i5.png" alt="BMW i5" style="transform: scale(0.95);" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>340</b><span>HP</span></div>
              <div class="spec-cell"><b>295m</b><span>Range</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$66,800</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- i7-m70 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">i series</span>
            <h3>BMW i7 M70</h3>
            <div class="img-wrap">
              <img src="../images/i7-m70-760x360.png" alt="BMW i7 M70" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>659</b><span>HP</span></div>
              <div class="spec-cell"><b>291m</b><span>Range</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$168,500</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- ix drive50 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">i series</span>
            <h3>BMW iX 50</h3>
            <div class="img-wrap">
              <img src="../images/ix drive50.png" alt="BMW iX" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>523</b><span>HP</span></div>
              <div class="spec-cell"><b>307m</b><span>Range</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$87,100</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Group 3: SUV X Range -->
      <h2 class="lineup-row-title">BMW Luxury SAV (X Range)</h2>
      <div class="lineup-grid">
        <!-- X1 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">X series</span>
            <h3>BMW X1</h3>
            <div class="img-wrap">
              <img src="../images/X1.png" alt="BMW X1" style="transform: scale(0.95);" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>241</b><span>HP</span></div>
              <div class="spec-cell"><b>6.2s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$39,100</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- X5 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">X series</span>
            <h3>BMW X5</h3>
            <div class="img-wrap">
              <img src="../images/x5.png" alt="BMW X5" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>375</b><span>HP</span></div>
              <div class="spec-cell"><b>5.3s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$65,200</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- X7 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">X series</span>
            <h3>BMW X7</h3>
            <div class="img-wrap">
              <img src="../images/x7.png" alt="BMW X7" style="transform: scale(0.95);" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>375</b><span>HP</span></div>
              <div class="spec-cell"><b>5.6s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$81,900</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>

        <!-- Z4 -->
        <div class="lineup-card">
          <div>
            <span class="lbl">Z series</span>
            <h3>BMW Z4</h3>
            <div class="img-wrap">
              <img src="../images/z4.png" alt="BMW Z4" />
            </div>
          </div>
          <div>
            <div class="spec-row">
              <div class="spec-cell"><b>340</b><span>HP</span></div>
              <div class="spec-cell"><b>4.5s</b><span>0-100</span></div>
            </div>
            <div class="btn-wrap">
              <span class="price">$53,600</span>
              <a href="configrator.php">Configure</a>
            </div>
          </div>
        </div>
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
       EXTERNAL ENGINE SCRIPTS
       ========================================================================== */ -->
  <!-- GSAP Core -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  
  <!-- Lenis Smooth scroll -->
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.36/dist/lenis.min.js"></script>
  
  <!-- Core script engines -->
  <script src="../js/premium.js"></script>


  <?php if ($managedCars): ?>
  <section class="managed-catalog-section" aria-labelledby="managed-lineup-title">
    <div class="lineup-container">
      <p class="section-subtitle">LIVE SHOWROOM INVENTORY</p>
      <h2 id="managed-lineup-title" class="section-title">LATEST ADMIN-MANAGED MODELS</h2>
      <div class="managed-catalog-grid">
        <?php foreach ($managedCars as $car): ?>
          <article class="managed-car-card">
            <div class="managed-car-image">
              <?php if (!empty($car['image'])): ?><img src="<?= e(lineup_car_image((string)$car['image'])) ?>" alt="<?= e((string)$car['name']) ?>" loading="lazy"><?php else: ?><div class="managed-car-placeholder"><i class="fa-solid fa-car-side"></i></div><?php endif; ?>
            </div>
            <div class="managed-car-content">
              <span><?= e((string)$car['series']) ?> · <?= e((string)($car['fuel'] ?: 'BMW')) ?></span>
              <h3><?= e((string)$car['name']) ?></h3>
              <?php if (!empty($car['description'])): ?><p><?= e((string)$car['description']) ?></p><?php endif; ?>
              <div class="managed-car-meta"><strong><?= e((string)($car['price'] ?: 'Price on request')) ?></strong><?php if (!empty($car['transmission'])): ?><small><?= e((string)$car['transmission']) ?></small><?php endif; ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</body>

</html>
