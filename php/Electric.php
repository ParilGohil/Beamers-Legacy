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
  <title>BMW Electric Driving | Beamers Legacy</title>
  <meta name="description" content="Explore BMW Electric vehicles: the flagship i Series i4, i5, i7, iX1 LWB and iX SUVs. 100% electric, zero local emissions, sheer driving pleasure." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* Electric Showcase Page Custom Styles */
    .ele-hero-section {
      padding: 180px 0 100px;
      background: radial-gradient(circle at 50% 20%, rgba(28, 105, 212, 0.1) 0%, var(--bg-primary) 70%);
      text-align: center;
      border-bottom: 1px solid var(--border-color);
    }
    
    .ele-hero-banner {
      width: 90%;
      max-width: 1200px;
      height: 500px;
      margin: 50px auto 0;
      border: 1px solid var(--border-color);
      overflow: hidden;
      position: relative;
    }
    
    .ele-hero-banner img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      filter: brightness(0.65);
    }
    
    .ele-showcase-section {
      padding: 100px 0;
    }
    
    .ele-container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .ele-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 120px;
      margin-top: 80px;
    }
    
    .ele-vehicle-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }
    
    .ele-vehicle-row.reverse {
      grid-template-columns: 1fr 1fr;
    }
    
    .ele-vehicle-media {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      padding: 40px;
      display: flex;
      justify-content: center;
      align-items: center;
      transition: border var(--transition-medium);
      overflow: hidden;
    }
    
    .ele-vehicle-media img {
      max-width: 110%;
      height: auto;
      object-fit: contain;
      transition: transform 0.5s ease;
    }
    
    .ele-vehicle-row:hover .ele-vehicle-media {
      border-color: rgba(28, 105, 212, 0.3);
    }
    
    .ele-vehicle-row:hover .ele-vehicle-media img {
      transform: scale(1.03);
    }
    
    .ele-vehicle-info h2 {
      font-size: clamp(2rem, 3vw, 3.2rem);
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      margin-bottom: 20px;
      line-height: 1.1;
    }
    
    .ele-vehicle-info p {
      color: var(--text-muted);
      margin-bottom: 40px;
      font-size: 1.05rem;
      font-weight: 300;
    }
    
    @media (max-width: 900px) {
      .ele-vehicle-row, .ele-vehicle-row.reverse {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .ele-vehicle-row.reverse .ele-vehicle-info {
        order: 2;
      }
      .ele-vehicle-row.reverse .ele-vehicle-media {
        order: 1;
      }
      .ele-hero-banner {
        height: 300px;
      }
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
       HERO SHOWCASE SECTION
       ========================================================================== */ -->
  <div class="ele-hero-section">
    <span class="section-subtitle">#BORNELECTRIC</span>
    <h1 class="section-title text-gradient">100% ELECTRIC. 100% DRIVING JOY.</h1>
    <p style="color: var(--text-muted); max-width: 600px; margin: 15px auto 0; font-weight: 300;">
      A quiet revolution of power. Feel instant torque acceleration from standalone markers while protecting local emission layers. Join us to discover the future of BMW i.
    </p>
    
    <div class="ele-hero-banner">
      <img src="../images/ele 2.jpg" alt="BMW Electric Showroom" />
    </div>
  </div>

  <!-- ==========================================================================
       ELECTRIC VEHICLES CATALOG LIST
       ========================================================================== */ -->
  <section class="ele-showcase-section">
    <div class="ele-container">
      
      <div class="ele-grid">
        
        <!-- Row 1: i5 -->
        <div class="ele-vehicle-row">
          <div class="ele-vehicle-media ele-glow-hover">
            <img src="../images/i5.png" alt="THE BMW i5" />
          </div>
          <div class="ele-vehicle-info">
            <span class="section-subtitle">THE ELECTRIC EXECUTIVE</span>
            <h2>THE BMW i5</h2>
            <p>
              Drive into a world where the ingenuity of BMW i and the luxury trim of the 5 Series connect. Featuring 5th generation eDrive system updates delivering seamless silent dynamic power over any business journey.
            </p>
            
            <div class="specs-container" style="border: none; padding: 0; margin-bottom: 40px;">
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">340</span>
                <span class="label">Horsepower</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">5.7s</span>
                <span class="label">0-100 Km/h</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">295m</span>
                <span class="label">Est. Range</span>
              </div>
            </div>
            
            <a href="configrator.php" class="btn-blue">Configure i5</a>
          </div>
        </div>

        <!-- Row 2: iX (Reverse column) -->
        <div class="ele-vehicle-row reverse">
          <div class="ele-vehicle-info">
            <span class="section-subtitle">THE ELECTRIC VISION</span>
            <h2>THE BMW iX</h2>
            <p>
              A pioneering generation of flagship SAV. With fully-electric direct xDrive all-wheel motors, intelligent minimalist modern cabin interfaces, and high-tensile carbon reinforcement grids.
            </p>
            
            <div class="specs-container" style="border: none; padding: 0; margin-bottom: 40px;">
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">523</span>
                <span class="label">Horsepower</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">4.6s</span>
                <span class="label">0-100 Km/h</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">307m</span>
                <span class="label">Est. Range</span>
              </div>
            </div>
            
            <a href="configrator.php" class="btn-blue">Configure iX</a>
          </div>
          <div class="ele-vehicle-media ele-glow-hover">
            <img src="../images/ix drive50.png" alt="THE BMW iX" />
          </div>
        </div>

        <!-- Row 3: i4 -->
        <div class="ele-vehicle-row">
          <div class="ele-vehicle-media ele-glow-hover">
            <img src="../images/i4.png" alt="THE BMW i4" />
          </div>
          <div class="ele-vehicle-info">
            <span class="section-subtitle">THE COMPACT SPORT</span>
            <h2>THE BMW i4</h2>
            <p>
              The first-ever fully-electric Gran Coupé delivers outstanding weight distribution and sport coordinates to make it your ultimate daily driver. Spacious five seats combined with fast charging speeds.
            </p>
            
            <div class="specs-container" style="border: none; padding: 0; margin-bottom: 40px;">
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">340</span>
                <span class="label">Horsepower</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">5.7s</span>
                <span class="label">0-100 Km/h</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">301m</span>
                <span class="label">Est. Range</span>
              </div>
            </div>
            
            <a href="configrator.php" class="btn-blue">Configure i4</a>
          </div>
        </div>

        <!-- Row 4: i7 (Reverse column) -->
        <div class="ele-vehicle-row reverse">
          <div class="ele-vehicle-info">
            <span class="section-subtitle">THE LUXURY ELITE</span>
            <h2>THE BMW i7</h2>
            <p>
              Combining breathtaking electric M-caliber performance with multisensory lounge entertainment packages. Theatre screen configurations in the rear cabins provide theater-like VIP travel coordinates.
            </p>
            
            <div class="specs-container" style="border: none; padding: 0; margin-bottom: 40px;">
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">659</span>
                <span class="label">Horsepower</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">3.7s</span>
                <span class="label">0-100 Km/h</span>
              </div>
              <div class="spec-item">
                <span class="value" style="color: var(--bmw-blue);">291m</span>
                <span class="label">Est. Range</span>
              </div>
            </div>
            
            <a href="configrator.php" class="btn-blue">Configure i7</a>
          </div>
          <div class="ele-vehicle-media ele-glow-hover">
            <img src="../images/i7-m70-760x360.png" alt="THE BMW i7" style="max-width: 95%;" />
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
       EXTERNAL ENGINE SCRIPTS (CDNs & Custom Bundles)
       ========================================================================== */ -->
  <!-- GSAP Core -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  
  <!-- Lenis Smooth scroll -->
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.36/dist/lenis.min.js"></script>
  
  <!-- Core script engines -->
  <script src="../js/premium.js"></script>

</body>

</html>