/* ==========================================================================
   BEAMERS LEGACY — HOMEPAGE CINEMATIC SCROLL ANIMATIONS (home.js)
   ========================================================================== */

document.addEventListener("preloaderComplete", () => {
  initHeroTimeline();
  initIntroSequence();
  initVehicleReveal();
  initHorizontalScroll();
});

/* ==========================================================================
   SCENE 02 — HERO GSAP ZOOM
   ========================================================================== */
function initHeroTimeline() {
  const heroSection = document.querySelector(".hero-section");
  const heroVideo = document.querySelector(".hero-video-container video");
  const heroTitle = document.querySelector(".hero-content-wrap h1");
  const heroSub = document.querySelector(".hero-content-wrap p");
  const heroScroll = document.querySelector(".scroll-indicator");

  if (!heroSection || !window.gsap) return;

  // Gentle entry of hero titles on load (triggered after preloader)
  gsap.fromTo(heroTitle, 
    { y: 50, opacity: 0 }, 
    { y: 0, opacity: 1, duration: 1.5, ease: "power4.out" }
  );
  gsap.fromTo(heroSub, 
    { y: 20, opacity: 0 }, 
    { y: 0, opacity: 1, duration: 1.2, ease: "power3.out", delay: 0.3 }
  );
  gsap.fromTo(heroScroll, 
    { opacity: 0 }, 
    { opacity: 1, duration: 1.5, delay: 0.8 }
  );

  // Cinematic scroll scrubbing: Zoom video, slide up text, fade out
  const heroTimeline = gsap.timeline({
    scrollTrigger: {
      trigger: heroSection,
      start: "top top",
      end: "+=150%",
      scrub: true,
      pin: true,
      anticipatePin: 1
    }
  });

  heroTimeline
    .to(heroVideo, { scale: 1.25, duration: 2 }, 0)
    .to(heroTitle, { y: -80, opacity: 0, duration: 1.2 }, 0)
    .to(heroSub, { y: -50, opacity: 0, duration: 1.2 }, 0)
    .to(heroScroll, { opacity: 0, duration: 0.5 }, 0);
}

/* ==========================================================================
   SCENE 03 — DEALERSHIP / BRAND INTRO
   ========================================================================== */
function initIntroSequence() {
  const introSection = document.querySelector(".brand-intro-section");
  if (!introSection || !window.gsap) return;

  gsap.from(".intro-block-txt", {
    scrollTrigger: {
      trigger: introSection,
      start: "top 75%",
      toggleActions: "play none none none"
    },
    opacity: 0,
    y: 50,
    stagger: 0.2,
    duration: 1,
    ease: "power3.out"
  });
}

/* ==========================================================================
   SCENE 04 — FEATURED BMW REVEAL & SPECS
   ========================================================================== */
function initVehicleReveal() {
  const revealSection = document.querySelector(".featured-reveal-section");
  if (!revealSection || !window.gsap) return;

  const revealTimeline = gsap.timeline({
    scrollTrigger: {
      trigger: revealSection,
      start: "top top",
      end: "+=220%",
      scrub: true,
      pin: true
    }
  });

  // Reveal stages: solid black -> headlights glow -> vehicle highlights -> full specs
  revealTimeline
    .to(".reveal-dark-overlay", { background: "rgba(5, 5, 5, 0.4)", duration: 1.5 })
    .to(".reveal-glow-mask", { opacity: 1, duration: 1.0 }, "-=0.8")
    .fromTo(".reveal-vehicle-img", { scale: 1.06, filter: "brightness(0)" }, { scale: 1, filter: "brightness(0.9)", duration: 2.0 }, "-=1.0")
    .from(".reveal-content-left", { x: -50, opacity: 0, duration: 1.0 }, "-=0.8")
    .from(".reveal-spec-grid .spec-item", { y: 20, opacity: 0, stagger: 0.15, duration: 1.0 }, "-=0.6");
}

/* ==========================================================================
   SCENE 05 — HORIZONTAL MODEL TIMELINE
   ========================================================================== */
function initHorizontalScroll() {
  const horizontalSection = document.querySelector(".horizontal-scroll-section");
  const track = document.querySelector(".horizontal-track");
  
  if (!horizontalSection || !track || !window.gsap) return;

  const calculateTranslate = () => {
    return -(track.scrollWidth - window.innerWidth + 100);
  };

  // Convert vertical scroll wheel to horizontal slide
  gsap.to(track, {
    x: calculateTranslate,
    ease: "none",
    scrollTrigger: {
      trigger: horizontalSection,
      pin: true,
      scrub: true,
      start: "top top",
      end: () => `+=${track.scrollWidth - window.innerWidth + 300}`,
      invalidateOnRefresh: true
    }
  });

  // Slide-in numbers and items inside horizontal slider
  gsap.from(".horizontal-card img", {
    scrollTrigger: {
      trigger: horizontalSection,
      start: "top 80%",
    },
    scale: 0.9,
    opacity: 0,
    stagger: 0.1,
    duration: 1.2,
    ease: "power2.out"
  });
}
