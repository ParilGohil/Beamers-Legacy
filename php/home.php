<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beamers Legacy | Premium BMW Dealership Experience</title>
  <meta name="description" content="Welcome to Beamers Legacy. Discover the ultimate driving machines, premium scroll dealership showroom, configuration and test drives." />
  
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Custom Styling -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* Homepage Specific Section Layouts */
    .hero-section {
      position: relative;
      width: 100%;
      height: 100vh;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #000;
    }

    .hero-video-container {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }

    .hero-video-container video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: scale(1);
    }

    .hero-video-container::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(5,5,5,0.4) 0%, rgba(5,5,5,0.7) 100%);
      z-index: 2;
    }

    .hero-content-wrap {
      position: relative;
      z-index: 2;
      text-align: center;
      max-width: 90%;
    }

    .hero-content-wrap h1 {
      margin-bottom: 20px;
      text-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }

    .hero-content-wrap p {
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.4em;
      color: var(--text-muted);
      margin-bottom: 30px;
    }

    /* Section Brand Intro */
    .brand-intro-section {
      background-color: var(--bg-secondary);
      padding: 18vh 0;
      border-bottom: 1px solid var(--border-color);
    }

    .intro-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      align-items: center;
      gap: 100px;
    }

    .intro-left-title {
      font-size: clamp(2rem, 3.5vw, 4rem);
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      line-height: 1.1;
    }

    .intro-right-desc {
      display: flex;
      flex-direction: column;
      gap: 30px;
    }

    .intro-right-desc p {
      font-size: 1.15rem;
      color: var(--text-secondary);
      font-weight: 300;
    }

    /* Section Featured BMW Reveal */
    .featured-reveal-section {
      position: relative;
      width: 100%;
      height: 100vh;
      background-color: #000;
      overflow: hidden;
      display: flex;
      align-items: center;
    }

    .reveal-media-container {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }

    .reveal-vehicle-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .reveal-glow-mask {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 50% 50%, transparent 20%, #000 80%),
                  radial-gradient(circle at 35% 55%, rgba(64, 150, 255, 0.4) 0%, transparent 8%);
      z-index: 2;
      opacity: 0;
    }

    .reveal-dark-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(5, 5, 5, 0.95);
      z-index: 3;
    }

    .reveal-content-left {
      position: relative;
      z-index: 4;
      margin-left: 10%;
      width: 40%;
      max-width: 500px;
    }

    .reveal-content-left h2 {
      font-size: clamp(2rem, 4vw, 4.5rem);
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: -0.03em;
      line-height: 1;
      margin-bottom: 20px;
    }

    .reveal-content-left p {
      color: var(--text-muted);
      margin-bottom: 40px;
    }

    /* Section Horizontal Scroll Showcase */
    .horizontal-scroll-section {
      position: relative;
      width: 100%;
      background-color: var(--bg-primary);
      overflow: hidden;
    }

    .horizontal-wrap {
      display: flex;
      flex-direction: column;
      justify-content: center;
      height: 100%;
      padding: 100px 0;
    }

    .horizontal-header {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto 50px;
    }

    .horizontal-track {
      display: flex;
      gap: 40px;
      padding-left: 10%;
      touch-action: none;
      width: max-content;
    }

    .horizontal-card {
      width: 320px;
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      padding: 30px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 520px;
      transition: transform var(--transition-medium), border var(--transition-medium);
    }

    .horizontal-card:hover {
      transform: translateY(-5px);
      border-color: var(--border-highlight);
    }

    .horizontal-card .card-number {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-dark);
      letter-spacing: 0.1em;
    }

    .horizontal-card img {
      width: 130%;
      margin-left: -15%;
      margin-top: 20px;
      margin-bottom: 20px;
      object-fit: contain;
      pointer-events: none;
      transition: transform 0.5s ease;
    }

    .horizontal-card:hover img {
      transform: scale(1.05);
    }

    .horizontal-card h3 {
      font-size: 1.3rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.01em;
      margin-bottom: 5px;
    }

    .horizontal-card .card-class {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      color: var(--bmw-blue);
      margin-bottom: 20px;
      display: block;
    }

    .horizontal-card .card-specs {
      display: flex;
      justify-content: space-between;
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
      margin-bottom: 25px;
    }

    .horizontal-card .spec-item-sm {
      display: flex;
      flex-direction: column;
    }

    .horizontal-card .spec-item-sm .val {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .horizontal-card .spec-item-sm .lbl {
      font-size: 0.6rem;
      font-weight: 600;
      text-transform: uppercase;
      color: var(--text-muted);
    }

    /* Section Series Previews Grid */
    .sections-previews {
      display: flex;
      width: 100%;
      background-color: var(--bg-primary);
      border-top: 1px solid var(--border-color);
      border-bottom: 1px solid var(--border-color);
    }

    .preview-column {
      flex: 1;
      height: 70vh;
      min-height: 500px;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 50px;
      border-right: 1px solid var(--border-color);
    }

    .preview-column:last-child {
      border-right: none;
    }

    .preview-column-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      transition: transform var(--transition-slow);
    }

    .preview-column-bg img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      filter: brightness(0.4) grayscale(0.2);
      transition: filter var(--transition-medium);
    }

    .preview-column::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
      z-index: 2;
    }

    .preview-column-content {
      position: relative;
      z-index: 3;
      transform: translateY(20px);
      transition: transform var(--transition-medium);
    }

    .preview-column-content .badge-m {
      background: #e3000f;
      color: #fff;
      font-size: 0.6rem;
      font-weight: 900;
      letter-spacing: 0.1em;
      padding: 3px 8px;
      display: inline-block;
      margin-bottom: 12px;
      text-transform: uppercase;
    }

    .preview-column-content h2 {
      font-size: 2rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.01em;
      margin-bottom: 10px;
    }

    .preview-column-content p {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-bottom: 25px;
      max-width: 300px;
      opacity: 0;
      transition: opacity var(--transition-medium);
    }

    /* Preview column hover states */
    .preview-column:hover .preview-column-bg {
      transform: scale(1.05);
    }

    .preview-column:hover .preview-column-bg img {
      filter: brightness(0.5);
    }

    .preview-column:hover .preview-column-content {
      transform: translateY(0);
    }

    .preview-column:hover .preview-column-content p {
      opacity: 1;
    }

    /* Section Immersive CTA */
    .immersive-cta-section {
      padding: 15vh 0;
      background-color: var(--bg-secondary);
      text-align: center;
    }

    .immersive-cta-wrap {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .immersive-cta-wrap h2 {
      margin-bottom: 30px;
    }

    .immersive-cta-btns {
      display: flex;
      justify-content: center;
      gap: 30px;
      margin-top: 40px;
    }

    @media (max-width: 900px) {
      .intro-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .sections-previews {
        flex-direction: column;
      }
      .preview-column {
        height: 50vh;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
      }
      .preview-column-content p {
        opacity: 1;
      }
      .preview-column-content {
        transform: translateY(0);
      }
      .reveal-content-left {
        width: 80%;
      }
      .immersive-cta-btns {
        flex-direction: column;
        align-items: center;
        gap: 15px;
      }
    }
  </style>
</head>

<body>

  <!-- ==========================================================================
       SCENE 01 — PRELOADER
       ========================================================================== */ -->
  <div id="preloader">
    <div class="preloader-logo">BEAMERS LEGACY</div>
    <div class="preloader-progress-wrap">
      <div class="preloader-progress-bar"></div>
    </div>
    <div class="preloader-percentage">0%</div>
  </div>

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
        <!-- Interactive Preview Images -->
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
       SCENE 02 — CINEMATIC CLASSIC HERO VIDEO
       ========================================================================== */ -->
  <div class="hero-section">
    <div class="hero-video-container">
      <video autoplay muted loop playsinline preload="auto">
        <source src="../videos/video-1.mp4" type="video/mp4" />
      </video>
    </div>
    
    <div class="hero-content-wrap">
      <p>WELCOME TO BEAMERS LEGACY</p>
      <h1 class="hero-title text-gradient title-glow">THE ULTIMATE<br />DRIVING<br />EXPERIENCE</h1>
      <a href="models.php" class="btn-primary">Explore Showroom</a>
    </div>

    <!-- Scroll Down Icon -->
    <div class="scroll-indicator">
      <span>Scroll to explore</span>
      <div class="mouse">
        <div class="mouse-wheel"></div>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       SCENE 03 — BRAND / DEALERSHIP INTRO (EDITORIAL)
       ========================================================================== */ -->
  <div class="brand-intro-section">
    <div class="intro-grid">
      <div class="intro-left-title intro-block-txt">
        CRAFTED FOR<br />THE PRECISION<br /><span class="text-gradient">ENTHUSIASTS</span>
      </div>
      <div class="intro-right-desc intro-block-txt">
        <p>
          At Beamers Legacy, we represent the absolute pinnacle of Bavarian engineering. 
          Step into a curated realm where luxury is standard, precision is demanded, and performance is promised.
        </p>
        <p>
          From the roaring combustion lines of the BMW M Series to the pioneering, whisper-quiet charging architectures of the BMW i electric vehicles - we present an interactive, peerless automotive portfolio designed around your driving passion.
        </p>
        <a href="more bmw.php" class="btn-link">Discover Our Philosophy <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       SCENE 04 — FEATURED BMW REVEAL (THE M8 COMPETITION)
       ========================================================================== */ -->
  <div class="featured-reveal-section">
    <div class="reveal-media-container">
      <img src="../images/m4bg.jpg" class="reveal-vehicle-img" alt="BMW M8 Competition" />
      <div class="reveal-glow-mask"></div>
      <div class="reveal-dark-overlay"></div>
    </div>

    <div class="reveal-content-left">
      <span class="section-subtitle">THE SHAPE OF POWER</span>
      <h2>BMW M8<br />COMPETITION</h2>
      <p>
        Luxury meets extreme racetrack performance. The BMW M8 Competition Coupe combines the distinct motorsport bloodlines of the BMW M division with the elite refinements of the luxury class, creating a masterpiece of road engineering.
      </p>

      <!-- Specifications Component -->
      <div class="reveal-spec-grid specs-container" style="border: none; padding: 0; margin-bottom: 40px;">
        <div class="spec-item">
          <span class="value">625</span>
          <span class="label">Horsepower</span>
        </div>
        <div class="spec-item">
          <span class="value">750</span>
          <span class="label">Torque (Nm)</span>
        </div>
        <div class="spec-item">
          <span class="value">3.2s</span>
          <span class="label">0-100 Km/h</span>
        </div>
        <div class="spec-item">
          <span class="value">305</span>
          <span class="label">Top Speed (Km/h)</span>
        </div>
      </div>

      <a href="configrator.php" class="btn-blue">Configure Yours</a>
    </div>
  </div>

  <!-- ==========================================================================
       SCENE 05 — HORIZONTAL MODEL TRACK SHOWCASE
       ========================================================================== */ -->
  <div class="horizontal-scroll-section">
    <div class="horizontal-wrap">
      <div class="horizontal-header">
        <span class="section-subtitle">AVAILABLE VEHICLES</span>
        <h2 class="section-title">THE SHOWROOM COLLECTION</h2>
      </div>

      <div class="horizontal-track">
        <!-- Card 1 -->
        <div class="horizontal-card">
          <div>
            <span class="card-number">01</span>
            <img src="../images/m2.png" alt="BMW M2" />
            <h3>BMW M2</h3>
            <span class="card-class">Coupé</span>
          </div>
          <div>
            <div class="card-specs">
              <div class="spec-item-sm">
                <span class="val">460 HP</span>
                <span class="lbl">Power</span>
              </div>
              <div class="spec-item-sm">
                <span class="val">4.1s</span>
                <span class="lbl font-sans">0-100</span>
              </div>
            </div>
            <a href="configrator.php" class="btn-primary" style="width: 100%;">Configure</a>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="horizontal-card">
          <div>
            <span class="card-number">02</span>
            <img src="../images/m4 cs.png" alt="BMW M4 CS" />
            <h3>BMW M4 CS</h3>
            <span class="card-class">Performance Coupé</span>
          </div>
          <div>
            <div class="card-specs">
              <div class="spec-item-sm">
                <span class="val">550 HP</span>
                <span class="lbl">Power</span>
              </div>
              <div class="spec-item-sm">
                <span class="val">3.4s</span>
                <span class="lbl">0-100</span>
              </div>
            </div>
            <a href="configrator.php" class="btn-primary" style="width: 100%;">Configure</a>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="horizontal-card">
          <div>
            <span class="card-number">03</span>
            <img src="../images/M8 compi.png" alt="BMW M8" />
            <h3>BMW M8</h3>
            <span class="card-class">Luxury Performance</span>
          </div>
          <div>
            <div class="card-specs">
              <div class="spec-item-sm">
                <span class="val">625 HP</span>
                <span class="lbl">Power</span>
              </div>
              <div class="spec-item-sm">
                <span class="val">3.2s</span>
                <span class="lbl">0-100</span>
              </div>
            </div>
            <a href="configrator.php" class="btn-primary" style="width: 100%;">Configure</a>
          </div>
        </div>

        <!-- Card 4 -->
        <div class="horizontal-card">
          <div>
            <span class="card-number">04</span>
            <img src="../images/z4.png" alt="BMW Z4" />
            <h3>BMW Z4</h3>
            <span class="card-class">Roadster</span>
          </div>
          <div>
            <div class="card-specs">
              <div class="spec-item-sm">
                <span class="val">340 HP</span>
                <span class="lbl">Power</span>
              </div>
              <div class="spec-item-sm">
                <span class="val">4.5s</span>
                <span class="lbl">0-100</span>
              </div>
            </div>
            <a href="configrator.php" class="btn-primary" style="width: 100%;">Configure</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       SCENE 06-09 — SPECIFIC SERIES PREVIEWS COLUMN SHOWCASE
       ========================================================================== */ -->
  <div class="sections-previews">
    <div class="preview-column">
      <div class="preview-column-bg">
        <img src="../images/x5.png" alt="BMW X Range" style="object-fit: contain; margin-top:-50px;" />
      </div>
      <div class="preview-column-content">
        <h2>THE X RANGE</h2>
        <p>Command presence. Unparalleled versatility meets luxury sizing in the flagship BMW SUV collection.</p>
        <a href="models.php#X" class="btn-primary">Discover SUVs</a>
      </div>
    </div>

    <div class="preview-column">
      <div class="preview-column-bg">
        <img src="../images/ix drive50.png" alt="BMW Electric" style="object-fit: contain; margin-top:-50px;" />
      </div>
      <div class="preview-column-content">
        <h2>BMW ELECTRIC</h2>
        <p>The Future is Electric. Glide silently through daily explorations with the flagship BMW i models.</p>
        <a href="Electric.php" class="btn-primary">Explore i Series</a>
      </div>
    </div>

    <div class="preview-column">
      <div class="preview-column-bg">
        <div class="preview-column-bg-shader" style="background-color:rgba(227, 0, 15, 0.05); position:absolute; top:0; left:0; width:100%; height:100%; z-index:2;"></div>
        <img src="../images/m2.png" alt="BMW M Performance" style="object-fit: contain; margin-top:-50px;" />
      </div>
      <div class="preview-column-content">
        <div class="badge-m">BORN TO PERFORM</div>
        <h2>BMW M SERIES</h2>
        <p>Pure racetrack DNA. Fast acceleration, aggressive styling, and motorsport steering calibrations.</p>
        <a href="models.php#M" class="btn-primary">View M Range</a>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       SCENE 10-12 — TECHNOLOGY / EXPERIENCE CTA
       ========================================================================== */ -->
  <div class="immersive-cta-section">
    <div class="immersive-cta-wrap">
      <span class="section-subtitle">YOUR INVITATION</span>
      <h2 class="section-title">THE STEERING WHEEL IS WAITING</h2>
      <p>
        No amount of editorial reviews compare with the direct visceral experience of starting a BMW engine. Confirm your appointment, book a personal slot, or configure your dream specifications online.
      </p>
      
      <div class="immersive-cta-btns">
        <a href="testdrive.php" class="btn-blue" style="padding: 18px 45px;">Book a Test Drive</a>
        <a href="configrator.php" class="btn-primary" style="padding: 18px 45px;">Personalize Model</a>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       GLOBAL PREMIUM DEEP FOOTER
       ========================================================================== */ -->
  <footer class="premium-footer">
    <div class="footer-top">
      <div class="footer-info">
        <h2>BEAMERS LEGACY</h2>
        <p>Authentic BMW dealership, supplying the ultimate driving machines, precision maintenance, and custom accessories.</p>
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
  <script src="../js/home.js"></script>

</body>

</html>