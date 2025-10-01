document.addEventListener("DOMContentLoaded", () => {
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

  function showToast(message) {
    if (!toast) return;
    const msg = toast.querySelector(".toast-message");
    if (msg) msg.textContent = message;
    toast.classList.remove("hidden");
    toast.classList.add("show");
    toastTimeout = setTimeout(hideToast, 10000);
  }

  function hideToast() {
    if (!toast) return;
    toast.classList.remove("show");
    setTimeout(() => toast.classList.add("hidden"), 400);
  }

  if (toastClose) {
    toastClose.addEventListener("click", () => {
      clearTimeout(toastTimeout);
      hideToast();
    });
  }

  // --- Validate input ---
  function validateInput() {
    if (!submitBtn || !emailInput || !consentCheckbox) return;

    const email = emailInput.value.trim();
    const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    submitBtn.disabled = !(consentCheckbox.checked && isValidEmail);
  }

  if (consentCheckbox) consentCheckbox.addEventListener("change", validateInput);
  if (emailInput) emailInput.addEventListener("input", validateInput);

  // --- Check trial ---
  function checkTrial() {
    const stored = localStorage.getItem("wplf");
    if (!stored) return false;

    const data = JSON.parse(stored);
    const created = new Date(data.created_at);
    const now = new Date();

    const trialDuration = 2 * 60 * 1000; // 2 minutos para test
    // const trialDuration = 40 * 24 * 60 * 60 * 1000; // 40 días producción

    return now - created > trialDuration;
  }

  const storedToken = localStorage.getItem("wplf");
  const trialExpired = checkTrial();

  // --- Unlock content ---
  function unlockContent() {
    lockedContents.forEach(div => (div.style.display = "block"));
    readMoreButtons.forEach(btnWrapper => (btnWrapper.style.display = "none"));
    if (overlay) overlay.style.display = "none";
  }

  // --- Initialize buttons ---
  readMoreButtons.forEach(btnWrapper => {
    const lockedBtn = btnWrapper.querySelector(".locked-btn");
    const expiredNotice = btnWrapper.querySelector(".trial-expired-notice");

    if (!storedToken) {
      lockedContents.forEach(div => (div.style.display = "none"));
      btnWrapper.style.display = "block";

      if (lockedBtn) {
        lockedBtn.addEventListener("click", e => {
          e.preventDefault();
          if (overlay) overlay.style.display = "flex";
        });
      }
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

  // --- Submit handler ---
  if (submitBtn) {
    submitBtn.addEventListener("click", async () => {
      if (!emailInput) return;

      const email = emailInput.value.trim();
      if (!email) {
        alert("Please enter a valid email address");
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = "Loading...";

      try {
        const response = await fetch(imf_ajax.url, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
          body: new URLSearchParams({
            action: "imf_save_lead",
            email: email,
            slug: window.location.pathname,
            nonce: imf_ajax.nonce
          })
        });

        const data = await response.json();

        if (data.success) {
          const tokenObj = {
            token: "tok_" + Math.random().toString(36).substring(2, 12),
            created_at: new Date().toISOString()
          };
          localStorage.setItem("wplf", JSON.stringify(tokenObj));
          unlockContent();
          showToast(data.data.message);
        } else {
          if (data.data.trialExpired) {
            readMoreButtons.forEach(btnWrapper => {
              const lockedBtn = btnWrapper.querySelector(".locked-btn");
              if (lockedBtn) lockedBtn.style.display = "none";
              const expiredNotice = btnWrapper.querySelector(".trial-expired-notice");
              if (expiredNotice) expiredNotice.style.display = "block";
            });

            if (overlay) overlay.style.display = "none";

            const expiredToken = {
              token: "tok_" + Math.random().toString(36).substring(2, 12),
              created_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString()
            };
            localStorage.setItem("wplf", JSON.stringify(expiredToken));

            showToast(data.data.message);
          } else {
            showToast(data.data.message);
          }
        }
      } catch (err) {
        console.error(err);
        showToast("An unexpected error occurred. Please try again.");
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Continue reading";
      }
    });
  }

  // --- Close overlay ---
  if (closeBtn && overlay) {
    closeBtn.addEventListener("click", () => {
      overlay.style.display = "none";
    });
  }
});
