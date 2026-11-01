/* ==========================================================================
   BEAMERS LEGACY — MODELS CATALOG CONTROLLER (models.js)
   ========================================================================== */

document.addEventListener("DOMContentLoaded", () => {
  initSubNavigation();
  initModelHoverEffects();
});

/* ==========================================================================
   STICKY SUB-NAVIGATION HIGHLIGHT SYNC
   ========================================================================== */
function initSubNavigation() {
  const subNavLinks = document.querySelectorAll(".models-subnav a");
  const sections = document.querySelectorAll(".models-section");

  if (!subNavLinks.length || !sections.length) return;

  // Click smooth scroll coordination
  subNavLinks.forEach(link => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const targetId = link.getAttribute("href");
      const targetSection = document.querySelector(targetId);
      
      if (targetSection) {
        // Calculate offset for fixed header navigation bar (approx 140px combined)
        const offset = 140;
        const targetPosition = targetSection.getBoundingClientRect().top + window.scrollY - offset;
        
        window.scrollTo({
          top: targetPosition,
          behavior: "smooth"
        });
      }
    });
  });

  // Track active scroll segment using GSAP ScrollTrigger
  if (window.ScrollTrigger && window.gsap) {
    sections.forEach(section => {
      const id = section.getAttribute("id");
      const linkingTrigger = document.querySelector(`.models-subnav a[href="#${id}"]`);
      
      if (linkingTrigger) {
        ScrollTrigger.create({
          trigger: section,
          start: "top 160px",
          end: "bottom 160px",
          onToggle: (self) => {
            if (self.isActive) {
              subNavLinks.forEach(btn => btn.classList.remove("active"));
              linkingTrigger.classList.add("active");
            }
          }
        });
      }
    });

    // Reveal animation for model cards inside grid on scroll
    gsap.from(".model-product-card", {
      scrollTrigger: {
        trigger: ".models-grid-layout",
        start: "top 85%",
      },
      opacity: 0,
      y: 35,
      stagger: 0.08,
      duration: 0.8,
      ease: "power2.out"
    });
  }
}

/* ==========================================================================
   MODEL HOVER STATES
   ========================================================================== */
function initModelHoverEffects() {
  const cards = document.querySelectorAll(".model-product-card");

  cards.forEach(card => {
    // Micro interactive movements
    card.addEventListener("mouseenter", () => {
      const img = card.querySelector(".vehicle-img-wrapper img");
      if (img) img.style.transform = "scale(1.04) translateY(-5px)";
    });

    card.addEventListener("mouseleave", () => {
      const img = card.querySelector(".vehicle-img-wrapper img");
      if (img) img.style.transform = "none";
    });
  });
}
