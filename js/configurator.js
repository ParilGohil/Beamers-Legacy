document.addEventListener("DOMContentLoaded", initConfigurator);

function initConfigurator() {
  const catalogElement = document.getElementById("configurator-catalog");
  const modelSelect = document.getElementById("config-model");
  const imgTop = document.getElementById("vehicle-img-top");
  const imgBottom = document.getElementById("vehicle-img-bottom");
  const stage = document.querySelector(".config-viewport-images");
  const colorWash = document.getElementById("config-color-wash");
  const saveButton = document.getElementById("config-save");
  const saveStatus = document.getElementById("config-save-status");
  const testDriveLink = document.getElementById("btn-book-config");
  const enquiryLink = document.getElementById("btn-enquire-config");
  const catalog = catalogElement ? JSON.parse(catalogElement.textContent || "[]") : [];

  if (!modelSelect || !catalog.length) {
    return;
  }

  const modelById = new Map(catalog.map(model => [String(model.id), model]));
  const colorSwatches = {
    white: "#f7f9fa",
    red: "#b21f24",
    blue: "#174291",
    green: "#154c3e",
    black: "#0b0c0c"
  };
  const state = {
    modelId: modelSelect.value,
    color: document.querySelector(".swatch-option.active")?.dataset.color || "red",
    wheels: document.querySelector(".wheel-option.active")?.dataset.type || "m-orbit",
    interior: document.querySelector(".interior-option.active")?.dataset.type || "leather-black",
    packages: []
  };

  function selectionsText(model) {
    const color = document.querySelector(".swatch-option.active")?.dataset.previewName || state.color;
    const wheel = document.querySelector(".wheel-option.active .selection-button-title")?.textContent.trim() || state.wheels;
    const interior = document.querySelector(".interior-option.active .selection-button-title")?.textContent.trim() || state.interior;
    const packages = [...document.querySelectorAll(".package-toggle:checked")]
      .map(option => option.closest(".package-checkbox-card")?.querySelector("h4")?.textContent.trim())
      .filter(Boolean);
    return [model.name, `Paint: ${color}`, `Wheels: ${wheel}`, `Interior: ${interior}`, ...packages.map(name => `Option: ${name}`)].join("; ");
  }

  function updateView() {
    const model = modelById.get(String(state.modelId));
    if (!model) {
      return;
    }
    const image = model.image || "";
    if (imgTop && image && imgTop.getAttribute("src") !== image) {
      imgTop.style.opacity = "0";
      const updateImage = () => {
        imgTop.src = image;
        imgTop.alt = `${model.name} in the vehicle configurator`;
        imgTop.style.opacity = "1";
      };
      if (imgTop.complete) {
        updateImage();
      } else {
        imgTop.addEventListener("transitionend", updateImage, { once: true });
        window.setTimeout(updateImage, 220);
      }
    } else if (imgTop && !image) {
      imgTop.removeAttribute("src");
      imgTop.alt = `${model.name} image unavailable`;
    }
    if (imgBottom) {
      imgBottom.removeAttribute("src");
    }

    const seriesElement = document.getElementById("config-stage-series");
    const nameElement = document.getElementById("config-stage-name");
    const hpElement = document.getElementById("spec-val-hp");
    const accelerationElement = document.getElementById("spec-val-acc");
    const torqueElement = document.getElementById("spec-val-nm");
    const torqueLabel = document.getElementById("spec-label-nm");
    const priceElement = document.getElementById("price-base");

    if (seriesElement) seriesElement.textContent = model.series || model.vehicleType || "BMW";
    if (nameElement) nameElement.textContent = model.name;
    if (hpElement) hpElement.textContent = model.power || "—";
    if (accelerationElement) accelerationElement.textContent = model.acceleration || "—";
    if (torqueElement) torqueElement.textContent = model.torque || model.driveOrRange || "—";
    if (torqueLabel) torqueLabel.textContent = model.torque ? "Torque" : (model.metricLabel || "Drive");
    if (priceElement) priceElement.textContent = model.price || "Price on request";

    if (colorWash) {
      colorWash.style.setProperty("--config-wash-color", colorSwatches[state.color] || "transparent");
    }
    if (testDriveLink) {
      const preset = selectionsText(model);
      testDriveLink.href = `testdrive.php?car_id=${encodeURIComponent(model.id)}&preset_config=${encodeURIComponent(preset)}`;
    }
    if (enquiryLink) {
      enquiryLink.href = `booking.php?car_id=${encodeURIComponent(model.id)}`;
    }
  }

  modelSelect.addEventListener("change", event => {
    state.modelId = event.target.value;
    updateView();
  });

  document.querySelectorAll(".swatch-option").forEach(swatch => {
    swatch.addEventListener("click", () => {
      document.querySelectorAll(".swatch-option").forEach(option => {
        option.classList.remove("active");
        option.setAttribute("aria-pressed", "false");
      });
      swatch.classList.add("active");
      swatch.setAttribute("aria-pressed", "true");
      state.color = swatch.dataset.color || "red";
      updateView();
    });
  });

  document.querySelectorAll(".wheel-option").forEach(option => {
    option.addEventListener("click", () => {
      document.querySelectorAll(".wheel-option").forEach(item => {
        item.classList.remove("active");
        item.setAttribute("aria-pressed", "false");
      });
      option.classList.add("active");
      option.setAttribute("aria-pressed", "true");
      state.wheels = option.dataset.type || "";
      updateView();
    });
  });

  document.querySelectorAll(".interior-option").forEach(option => {
    option.addEventListener("click", () => {
      document.querySelectorAll(".interior-option").forEach(item => {
        item.classList.remove("active");
        item.setAttribute("aria-pressed", "false");
      });
      option.classList.add("active");
      option.setAttribute("aria-pressed", "true");
      state.interior = option.dataset.type || "";
      updateView();
    });
  });

  document.querySelectorAll(".package-toggle").forEach(option => {
    option.addEventListener("change", () => {
      state.packages = [...document.querySelectorAll(".package-toggle:checked")].map(item => item.value);
    });
  });

  const viewToggle = document.getElementById("config-view-toggle");
  if (viewToggle && stage) {
    viewToggle.addEventListener("click", () => {
      const isDetail = stage.classList.toggle("config-detail-view");
      viewToggle.setAttribute("aria-pressed", String(isDetail));
      viewToggle.innerHTML = isDetail ? "FULL<br>VIEW" : "DETAIL<br>VIEW";
      viewToggle.setAttribute("aria-label", isDetail ? "Show full vehicle" : "Zoom vehicle view");
    });
  }

  if (saveButton && saveStatus) {
    let statusTimer;
    saveButton.addEventListener("click", () => {
      const model = modelById.get(String(state.modelId));
      if (!model) return;
      const configuration = {
        modelId: model.id,
        modelName: model.name,
        color: state.color,
        wheels: state.wheels,
        interior: state.interior,
        packages: [...document.querySelectorAll(".package-toggle:checked")].map(option => option.value),
        savedAt: new Date().toISOString()
      };
      try {
        window.localStorage.setItem("beamersLegacyConfiguration", JSON.stringify(configuration));
        saveStatus.textContent = "Configuration saved on this device.";
      } catch (error) {
        saveStatus.textContent = "Unable to save this configuration in your browser.";
        console.error("Configurator could not save the selected build.", error);
      }
      saveStatus.classList.add("is-visible");
      window.clearTimeout(statusTimer);
      statusTimer = window.setTimeout(() => saveStatus.classList.remove("is-visible"), 3200);
    });
  }

  updateView();
}
