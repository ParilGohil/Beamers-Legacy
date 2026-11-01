/* ==========================================================================
   BEAMERS LEGACY — PREMIUM CINEMATIC RUNTIME ENGINE (Lenis + GSAP)
   ========================================================================== */

document.addEventListener("DOMContentLoaded", () => {
  initPreloader();
  initLenis();
  initNavigation();
  initMegaMenu();
  initScrollAnimations();
});

/* ==========================================================================
   PRELOADER ENGINE
   ========================================================================== */
function initPreloader() {
  const preloader = document.getElementById("preloader");
  if (!preloader) return;

  document.body.classList.add("preloader-active");

  const progressWrap = preloader.querySelector(".preloader-progress-wrap");
  const progressBar = preloader.querySelector(".preloader-progress-bar");
  const percentageText = preloader.querySelector(".preloader-percentage");
  const logoText = preloader.querySelector(".preloader-logo");

  // Animate preloader entry elements using standard GSAP
  if (window.gsap) {
    gsap.to(logoText, { opacity: 1, y: 0, duration: 0.8, ease: "power2.out", delay: 0.2 });
  } else {
    logoText.style.opacity = 1;
    logoText.style.transform = "translateY(0)";
  }

  let progress = 0;
  const interval = setInterval(() => {
    // Variable increment speed to feel like a real loader loading assets
    progress += Math.floor(Math.random() * 12) + 4;
    if (progress >= 100) {
      progress = 100;
      clearInterval(interval);
      hidePreloader();
    }
    
    if (progressBar) progressBar.style.width = `${progress}%`;
    if (percentageText) percentageText.innerHTML = `${progress}%`;
  }, 100);

  function hidePreloader() {
    setTimeout(() => {
      if (window.gsap) {
        gsap.to(preloader, {
          opacity: 0,
          duration: 0.8,
          ease: "power3.inOut",
          onComplete: () => {
            preloader.style.display = "none";
            document.body.classList.remove("preloader-active");
            
            // Dispatch event for other scripts to start animations
            document.dispatchEvent(new CustomEvent("preloaderComplete"));
          }
        });
      } else {
        preloader.style.opacity = 0;
        setTimeout(() => {
          preloader.style.display = "none";
          document.body.classList.remove("preloader-active");
          document.dispatchEvent(new CustomEvent("preloaderComplete"));
        }, 800);
      }
    }, 200);
  }
}

/* ==========================================================================
   SMOOTH SCROLL ENGINE (LENIS)
   ========================================================================== */
let lenis = null;
function initLenis() {
  // Check if Lenis library exists in script tags
  if (typeof Lenis === 'undefined') {
    console.warn("Lenis library not loaded yet. Retrying shortly...");
    return;
  }

  lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // Custom power4-styled out easing
    direction: 'vertical',
    gestureDirection: 'vertical',
    smooth: true,
    mouseMultiplier: 1,
    smoothTouch: false, // Don't snap on touchscreens
    touchMultiplier: 2,
    infinite: false,
  });

  // Connect Lenis scroll to requestAnimationFrame loop
  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }
  requestAnimationFrame(raf);

  // Sync scroll triggers
  if (window.ScrollTrigger) {
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
      lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);
  }
}

// Helper to disable/enable scroll dynamically (e.g. when menu open)
function setScrollState(active) {
  if (!lenis) return;
  if (active) {
    lenis.start();
  } else {
    lenis.stop();
  }
}

/* ==========================================================================
   NAVIGATION INTERACTIONS (SCROLL EFFECTS & MENUS)
   ========================================================================== */
function initNavigation() {
  const navbar = document.querySelector("nav.navbar");
  
  if (navbar) {
    window.addEventListener("scroll", () => {
      if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
      } else {
        navbar.classList.remove("scrolled");
      }
    });
  }

  // Hamburger Menu Triggers
  const burger = document.querySelector(".burger-menu");
  const fsMenu = document.querySelector(".fs-menu-overlay");
  
  if (burger && fsMenu) {
    burger.addEventListener("click", () => {
      const isOpen = fsMenu.classList.toggle("open");
      burger.classList.toggle("open", isOpen);
      setScrollState(!isOpen);

      // Simple stagger if GSAP loaded
      if (window.gsap && isOpen) {
        gsap.fromTo(
          ".fs-menu-links a",
          { opacity: 0, x: -30 },
          { opacity: 1, x: 0, stagger: 0.1, duration: 0.5, ease: "power2.out", delay: 0.2 }
        );
      }
    });

    // Close menu when clicking links
    fsMenu.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", () => {
        fsMenu.classList.remove("open");
        burger.classList.remove("open");
        setScrollState(true);
      });
    });

    // Hover images preview setup
    const previewImgs = document.querySelectorAll(".fs-menu-preview img");
    const menuLinks = document.querySelectorAll(".fs-menu-links a[data-preview]");

    menuLinks.forEach(link => {
      link.addEventListener("mouseenter", () => {
        const previewId = link.getAttribute("data-preview");
        previewImgs.forEach(img => {
          if (img.id === previewId) {
            img.classList.add("active");
          } else {
            img.classList.remove("active");
          }
        });
      });
    });
  }

  // User Profile dropdown trigger
  const profileDropdowns = document.querySelectorAll(".nav-profile-dropdown");
  profileDropdowns.forEach(profileDropdown => {
    const profileTrigger = profileDropdown.querySelector(".nav-profile-trigger");
    if (!profileTrigger) return;

    profileTrigger.addEventListener("click", (e) => {
      e.stopPropagation();
      const isOpen = profileDropdown.classList.toggle("open");
      profileTrigger.setAttribute("aria-expanded", String(isOpen));
    });
  });

  document.addEventListener("click", (e) => {
    profileDropdowns.forEach(profileDropdown => {
      if (!profileDropdown.contains(e.target)) {
        profileDropdown.classList.remove("open");
        profileDropdown.querySelector(".nav-profile-trigger")?.setAttribute("aria-expanded", "false");
      }
    });
  });
}

/* ==========================================================================
   MODELS MEGA-MENU
   ========================================================================== */
function initMegaMenu() {
  const navItem = document.querySelector(".nav-item-mega");
  if (!navItem) return;

  const trigger = navItem.querySelector(".nav-mega-trigger");
  const panel = navItem.querySelector(".mega-menu");
  const tabs = navItem.querySelectorAll(".mega-group-tab");
  const panels = navItem.querySelectorAll(".mega-group-panel");
  let closeTimer = null;

  function open() {
    clearTimeout(closeTimer);
    navItem.classList.add("open");
  }

  function scheduleClose() {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => navItem.classList.remove("open"), 150);
  }

  function closeNow() {
    clearTimeout(closeTimer);
    navItem.classList.remove("open");
  }

  function activateGroup(groupKey) {
    tabs.forEach(tab => {
      const isActive = tab.getAttribute("data-group") === groupKey;
      tab.classList.toggle("active", isActive);
      tab.setAttribute("aria-selected", isActive ? "true" : "false");
    });
    panels.forEach(p => {
      p.classList.toggle("active", p.getAttribute("data-group") === groupKey);
    });
  }

  // Hover to open/close on non-touch devices; the trigger link still
  // navigates to models.php normally on click/tap.
  navItem.addEventListener("mouseenter", open);
  navItem.addEventListener("mouseleave", scheduleClose);
  if (panel) {
    panel.addEventListener("mouseenter", open);
    panel.addEventListener("mouseleave", scheduleClose);
  }

  // Keyboard / touch: clicking the chevron area toggles instead of navigating.
  trigger.addEventListener("click", (e) => {
    if (navItem.classList.contains("open")) {
      // Already open — let the click through to navigate to models.php.
      return;
    }
    if (window.matchMedia("(hover: none)").matches) {
      e.preventDefault();
      open();
    }
  });

  tabs.forEach(tab => {
    tab.addEventListener("mouseenter", () => activateGroup(tab.getAttribute("data-group")));
    tab.addEventListener("click", () => activateGroup(tab.getAttribute("data-group")));
  });

  document.addEventListener("click", (e) => {
    if (!navItem.contains(e.target)) closeNow();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeNow();
  });
}

/* ==========================================================================
   SCROLL REVEALS & UTILITIES
   ========================================================================== */
function initScrollAnimations() {
  if (!window.gsap || !window.ScrollTrigger) {
    console.warn("GSAP / ScrollTrigger not loaded. Standard css animations will fall back.");
    return;
  }

  // General Fade Up Reveal elements on scroll
  const revealElements = document.querySelectorAll(".scroll-reveal");
  revealElements.forEach(el => {
    gsap.from(el, {
      scrollTrigger: {
        trigger: el,
        start: "top 85%",
        toggleActions: "play none none none"
      },
      opacity: 0,
      y: 40,
      duration: 0.8,
      ease: "power2.out"
    });
  });

  // Parallax background items
  const parallaxBg = document.querySelectorAll(".parallax-bg");
  parallaxBg.forEach(bg => {
    gsap.to(bg, {
      scrollTrigger: {
        trigger: bg.parentElement,
        start: "top bottom",
        end: "bottom top",
        scrub: true
      },
      yPercent: 15,
      ease: "none"
    });
  });
}
