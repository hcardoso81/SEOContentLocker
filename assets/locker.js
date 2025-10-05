document.addEventListener("DOMContentLoaded", () => {
  // --- Elementos principales ---
  const overlay = document.getElementById("lead-overlay");
  const submitBtn = document.getElementById("lead-submit");
  const emailInput = document.getElementById("lead-email");
  const consentCheckbox = document.getElementById("lead-consent");
  const closeBtn = document.querySelector(".modal-close");

  const lockedContents = document.querySelectorAll(".content-locked");
  const readMoreButtons = document.querySelectorAll(".read-more-locked");

  // --- Toast ---
  const toast = document.getElementById("subscription-toast");
  const toastClose = document.querySelector(".toast-close");
  let toastTimeout;

  // --- Funciones de Toast ---
  const Toast = {
    show(message) {
      if (!toast) return;
      const msg = toast.querySelector(".toast-message");
      if (msg) msg.textContent = message;
      toast.classList.remove("hidden");
      toast.classList.add("show");
      toastTimeout = setTimeout(Toast.hide, 10000);
    },
    hide() {
      if (!toast) return;
      toast.classList.remove("show");
      setTimeout(() => toast.classList.add("hidden"), 400);
    },
  };

  if (toastClose) {
    toastClose.addEventListener("click", () => {
      clearTimeout(toastTimeout);
      Toast.hide();
    });
  }

  // --- Validación de email y consentimiento ---
  const validateInput = () => {
    if (!submitBtn || !emailInput || !consentCheckbox) return;
    const email = emailInput.value.trim();
    const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    submitBtn.disabled = !(consentCheckbox.checked && isValidEmail);
  };

  if (consentCheckbox) consentCheckbox.addEventListener("change", validateInput);
  if (emailInput) emailInput.addEventListener("input", validateInput);

  // --- Manejo del trial ---
  const Trial = {
    getToken() {
      const stored = localStorage.getItem("wplf");
      return stored ? JSON.parse(stored) : null;
    },
    isExpired() {
      const data = this.getToken();
      if (!data) return false;
      const created = new Date(data.created_at);
      const now = new Date();
      const trialDuration = 2 * 60 * 1000; // 2 minutos prueba
      return now - created > trialDuration;
    },
    saveToken(expired = false) {
      const tokenObj = {
        token: "tok_" + Math.random().toString(36).substring(2, 12),
        created_at: expired
          ? new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString()
          : new Date().toISOString(),
      };
      localStorage.setItem("wplf", JSON.stringify(tokenObj));
    },
  };

  // --- Desbloquear contenido ---
  const unlockContent = () => {
    lockedContents.forEach(div => (div.style.display = "block"));
    readMoreButtons.forEach(btnWrapper => (btnWrapper.style.display = "none"));
    if (overlay) overlay.style.display = "none";
  };

  // --- Inicialización de botones "read more" ---
  const initReadMoreButtons = () => {
    const storedToken = Trial.getToken();
    const trialExpired = Trial.isExpired();

    readMoreButtons.forEach(btnWrapper => {
      const lockedBtn = btnWrapper.querySelector(".locked-btn");
      const expiredNotice = btnWrapper.querySelector(".trial-expired-notice");

      if (!storedToken) {
        lockedContents.forEach(div => (div.style.display = "none"));
        btnWrapper.style.display = "block";
        if (lockedBtn) lockedBtn.addEventListener("click", e => { e.preventDefault(); overlay.style.display = "flex"; });
        if (expiredNotice) expiredNotice.style.display = "none";
      } else if (trialExpired) {
        lockedContents.forEach(div => (div.style.display = "none"));
        btnWrapper.style.display = "block";
        if (lockedBtn) lockedBtn.style.display = "none";
        if (expiredNotice) expiredNotice.style.display = "block";
      } else {
        unlockContent();
      }
    });
  };

  initReadMoreButtons();

  // --- Submit del lead ---
  const handleSubmit = async () => {
    if (!emailInput) return;
    const email = emailInput.value.trim();
    if (!email) return alert("Please enter a valid email address");

    submitBtn.disabled = true;
    submitBtn.textContent = "Loading...";

    try {
      const response = await fetch(seocontentlocker_ajax.url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: new URLSearchParams({
          action: "seocontentlocker_save_lead",
          email,
          slug: window.location.pathname,
          nonce: seocontentlocker_ajax.nonce,
        }),
      });

      const data = await response.json();
      processResponse(data);
    } catch (err) {
      console.error(err);
      Toast.show("An unexpected error occurred. Please try again.");
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = "Continue reading";
    }
  };

  const processResponse = (data) => {
    if (data.success) {
      Trial.saveToken();
      unlockContent();
      Toast.show(data.data.message);
    } else if (data.data?.pending) {
      overlay.style.display = "none";
      showPendingNotice();
    } else if (data.data?.trialExpired) {
      readMoreButtons.forEach(btnWrapper => {
        const lockedBtn = btnWrapper.querySelector(".locked-btn");
        if (lockedBtn) lockedBtn.style.display = "none";
        const expiredNotice = btnWrapper.querySelector(".trial-expired-notice");
        if (expiredNotice) expiredNotice.style.display = "block";
      });
      overlay.style.display = "none";
      Trial.saveToken(true);
      Toast.show(data.data.message);
    } else {
      Toast.show(data.data?.message || "An unexpected error occurred.");
    }
  };

  const showPendingNotice = () => {
    readMoreButtons.forEach(btnWrapper => {
      const lockedBtn = btnWrapper.querySelector(".locked-btn");
      const lockerSeparator = btnWrapper.querySelector(".locked-separator");
      const upgradeBtn = btnWrapper.querySelector(".elementor-button-wrapper");
      if (lockedBtn) lockedBtn.style.display = "none";
      if (upgradeBtn) upgradeBtn.style.display = "none";
      if (lockerSeparator) lockerSeparator.style.display = "none";
    });
    document.querySelectorAll(".confirm-email-notice").forEach(notice => notice.style.display = "block");
  };

  if (submitBtn) submitBtn.addEventListener("click", handleSubmit);

  // --- Cerrar overlay ---
  if (closeBtn && overlay) closeBtn.addEventListener("click", () => overlay.style.display = "none");
});
