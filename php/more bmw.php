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
  <title>Bavarian Extras Directory | Beamers Legacy</title>
  <meta name="description" content="Access extra dealership portal items: schedule a model demonstration, register feedback, or explore our media sliders." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    /* More BMW specific custom styles */
    .more-hero {
      padding: 180px 0 60px;
      text-align: center;
      background-color: var(--bg-primary);
    }
    
    .more-slider-container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      height: 60vh;
      min-height: 400px;
      position: relative;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    
    .more-slider-track {
      display: flex;
      width: 100%;
      height: 100%;
      transition: transform 0.8s cubic-bezier(0.77, 0, 0.175, 1);
    }
    
    .more-slider-item {
      flex-shrink: 0;
      width: 100%;
      height: 100%;
      position: relative;
    }
    
    .more-slider-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      filter: brightness(0.65);
    }
    
    .slider-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(5, 5, 5, 0.6);
      border: 1px solid var(--border-color);
      color: #fff;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      cursor: pointer;
      z-index: 20;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background var(--transition-fast), border var(--transition-fast);
    }
    
    .slider-arrow:hover {
      background-color: var(--bmw-blue);
      border-color: var(--bmw-blue);
    }
    
    .slider-arrow.left {
      left: 30px;
    }
    
    .slider-arrow.right {
      right: 30px;
    }
    
    /* Big action links bottom of slider */
    .more-actions-row {
      display: flex;
      justify-content: center;
      gap: 30px;
      padding: 85px 0;
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .more-btn-card {
      flex: 1;
      max-width: 450px;
      border: 1px solid var(--border-color);
      background-color: var(--bg-secondary);
      padding: 40px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
      transition: border var(--transition-medium);
    }
    
    .more-btn-card:hover {
      border-color: var(--border-highlight);
    }
    
    .more-btn-card i {
      font-size: 2.2rem;
      color: var(--bmw-blue);
      margin-bottom: 10px;
    }
    
    .more-btn-card h3 {
      font-size: 1.3rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.01em;
    }
    
    .more-btn-card p {
      color: var(--text-muted);
      font-size: 0.85rem;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    @media (max-width: 768px) {
      .more-actions-row {
        flex-direction: column;
        align-items: center;
        gap: 20px;
      }
      .more-btn-card {
        width: 100%;
      }
      .slider-arrow {
        width: 40px;
        height: 40px;
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
       MORE HERO SECTION
       ========================================================================== */ -->
  <div class="more-hero">
    <span class="section-subtitle">THE ESSENCE</span>
    <h1 class="section-title text-gradient">EXTRAORDINARY PATHS</h1>
  </div>

  <!-- ==========================================================================
       MEDIA SLIDER CAROUSEL
       ========================================================================== */ -->
  <div class="more-slider-container">
    <div class="more-slider-track">
      <div class="more-slider-item">
        <img src="../images/bmw 1.webp" alt="BMW track driving 1">
      </div>
      <div class="more-slider-item">
        <img src="../images/bmw 2.webp" alt="BMW road layout 2">
      </div>
      <div class="more-slider-item">
        <img src="../images/bmw 3.webp" alt="BMW steering detail 3">
      </div>
      <div class="more-slider-item">
        <img src="../images/bmw 4.jpg" alt="BMW front grille 4">
      </div>
    </div>
    <button class="slider-arrow left" aria-label="Previous image"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-arrow right" aria-label="Next image"><i class="fas fa-chevron-right"></i></button>
  </div>

  <!-- ==========================================================================
       BIG CTA LINKS ROWS
       ========================================================================== */ -->
  <div class="more-actions-row">
    <!-- Card 1: Test Drive -->
    <div class="more-btn-card">
      <i class="fas fa-car-side"></i>
      <h3>SCHEDULE DURATION DRIVE</h3>
      <p>Configure and book your slot. Drive our high-performance models locally under factory guidance.</p>
      <a href="testdrive.php" class="btn-blue" style="width: 100%; text-align: center;">Book Test Drive</a>
    </div>

    <!-- Card 2: Feedback -->
    <div class="more-btn-card">
      <i class="far fa-comment-dots"></i>
      <h3>SUBMIT VISITOR FEEDBACK</h3>
      <p>We are constantly refining operations to match German engineering standards. Share your dealership feedback.</p>
      <a href="feedback.php" class="btn-primary" style="width: 100%; text-align: center;">Submit Feedback</a>
    </div>
  </div>

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

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const track = document.querySelector(".more-slider-track");
      const slides = document.querySelectorAll(".more-slider-item");
      const prevBtn = document.querySelector(".slider-arrow.left");
      const nextBtn = document.querySelector(".slider-arrow.right");
      
      let index = 0;
      const total = slides.length;
      
      function showSlide(i) {
        index = (i + total) % total;
        track.style.transform = `translateX(-${index * 100}%)`;
      }
      
      function nextSlide() {
        showSlide(index + 1);
      }
      
      function prevSlide() {
        showSlide(index - 1);
      }
      
      if (nextBtn) nextBtn.addEventListener("click", () => { nextSlide(); resetTimer(); });
      if (prevBtn) prevBtn.addEventListener("click", () => { prevSlide(); resetTimer(); });
      
      let autoTimer = setInterval(nextSlide, 4500);
      function resetTimer() {
        clearInterval(autoTimer);
        autoTimer = setInterval(nextSlide, 4500);
      }
    });
  </script>

</body>

</html>