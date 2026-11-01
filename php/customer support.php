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
  <title>Customer Resolution Support | Beamers Legacy</title>
  <meta name="description" content="Get customer support at Beamers Legacy. Review our three-step resolution pathway: Dealership point, Customer Interaction Centre, or Grievance Officer." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* Support Page Specific Custom Styles */
    .support-hero-section {
      position: relative;
      width: 100%;
      height: 75vh;
      min-height: 520px;
      overflow: hidden;
      display: flex;
      align-items: center;
      background-color: #000;
    }
    
    .support-hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }
    
    .support-hero-bg img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      filter: brightness(0.4);
    }
    
    .support-hero-content {
      position: relative;
      z-index: 5;
      width: 90%;
      max-width: 600px;
      margin-left: 10%;
    }
    
    .support-hero-content h1 {
      font-size: clamp(2.2rem, 4vw, 4rem);
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      margin-bottom: 20px;
      line-height: 1.1;
    }
    
    .support-pathways-section {
      padding: 100px 0;
      background-color: var(--bg-primary);
    }
    
    .support-container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .pathway-row {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 80px;
      align-items: center;
      padding: 80px 0;
      border-bottom: 1px solid var(--border-color);
    }
    
    .pathway-row.reverse {
      grid-template-columns: 1.2fr 1fr;
    }
    
    .pathway-row:last-child {
      border-bottom: none;
    }
    
    .pathway-img-wrap {
      border: 1px solid var(--border-color);
      overflow: hidden;
      background-color: var(--bg-secondary);
    }
    
    .pathway-img-wrap img {
      width: 100%;
      height: 480px;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
    }
    
    .pathway-row:hover .pathway-img-wrap img {
      transform: scale(1.03);
    }
    
    .pathway-info h2 {
      font-size: 1.8rem;
      font-weight: 800;
      text-transform: uppercase;
      margin-bottom: 20px;
    }
    
    .pathway-info p {
      color: var(--text-secondary);
      font-size: 1.05rem;
      line-height: 1.6;
      font-weight: 300;
      margin-bottom: 30px;
    }

    @media (max-width: 900px) {
      .pathway-row, .pathway-row.reverse {
        grid-template-columns: 1fr;
        gap: 40px;
        padding: 50px 0;
      }
      .pathway-row.reverse .pathway-info {
        order: 2;
      }
      .pathway-row.reverse .pathway-img-wrap {
        order: 1;
      }
      .pathway-img-wrap img {
        height: 300px;
      }
      .support-hero-content {
        margin-left: 5%;
        width: 90%;
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
       HERO COVER SECTION
       ========================================================================== */ -->
  <div class="support-hero-section">
    <div class="support-hero-bg">
      <img src="../images/contact 1.webp" alt="Customer Care Support" />
    </div>
    
    <div class="support-hero-content">
      <span class="section-subtitle">OUR COMPACT CORE COMMITMENT</span>
      <h1 class="text-gradient">RELAX.<br />WE SERVICE EVERY INCH.</h1>
      <p style="color: var(--text-secondary); line-height: 1.5; font-weight: 300;">
        At Beamers Legacy, we're committed to delivering a superior experience, both on and off the road. Your peace of mind is our absolute priority, and we are ready to respond to any queries regarding your vehicle.
      </p>
    </div>
  </div>

  <!-- ==========================================================================
       THREE STEP RESOLUTION SECTION
       ========================================================================== */ -->
  <section class="support-pathways-section">
    <div class="support-container">
      
      <div style="text-align: center; margin-bottom: 60px;">
        <span class="section-subtitle">THE BLUEPRINT</span>
        <h2 style="font-weight: 800; font-size: 2.2rem; text-transform: uppercase;">THREE-STEP SYSTEM PORTAL</h2>
      </div>

      <!-- Step 1 -->
      <div class="pathway-row">
        <div class="pathway-img-wrap">
          <img src="../images/contact 2.webp" alt="Local Showroom Dealership" />
        </div>
        <div class="pathway-info">
          <span class="section-subtitle" style="color: var(--bmw-blue);">STEP 01</span>
          <h2>Your BMW Dealership Portal</h2>
          <p>
            Your local BMW Dealership is your primary point of contact for service queries, parts replenishment, and technical advisor scheduling. Contact our team to open a unique ticket identifier tracking code. A dedicated service case manager will follow your progress until complete.
          </p>
          <a href="carservice.php" class="btn-primary">Request Car Service</a>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="pathway-row reverse">
        <div class="pathway-info">
          <span class="section-subtitle" style="color: var(--bmw-blue);">STEP 02</span>
          <h2>Interaction Escalations Centre</h2>
          <p>
            If you need further specialized assistance on complex configurations or factory delays, our regional Customer Interaction Centre is ready to step in. Please aggregate your vehicle identification card (VIN), dealer quote invoices, and service tickets. 
          </p>
          <p style="font-weight: 700; font-size: 1rem; color: var(--text-primary);">
            Hotline: 1800 102 0098 (Mon-Sat, 9:00 - 18:30)<br>
            Email: contact.beamers@legacy.in
          </p>
        </div>
        <div class="pathway-img-wrap">
          <img src="../images/contact 3.webp" alt="BMW Customer Centre" />
        </div>
      </div>

      <!-- Step 3 -->
      <div class="pathway-row">
        <div class="pathway-img-wrap">
          <img src="../images/contact 4.webp" alt="Grievance Redressal Board" />
        </div>
        <div class="pathway-info">
          <span class="section-subtitle" style="color: var(--bmw-blue);">STEP 03</span>
          <h2>Grievance Redressal Officer</h2>
          <p>
            In case your ticket remains unresolved through standard escalating routes, the Grievance Redressal Officer acts as our ultimate resolution point. A meticulous independent evaluation will occur, balancing factory warranties against dealer files to deliver a swift, equitable verdict.
          </p>
          <a class="btn-blue" href="mailto:contact.beamers@legacy.in?subject=Grievance%20Escalation">Escalate to Grievance Board</a>
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
          <li><a href="carservice.php">Request Car Service</a></li>
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

</body>

</html>
