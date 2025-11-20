document.addEventListener("DOMContentLoaded", () => {
    const isPageSsuscription = document.getElementById("my-subscription-form-page");

    const overlay = document.getElementById("lead-overlay");

    const submitBtn = document.getElementById("lead-submit");
    const emailInput = document.getElementById("lead-email");
    const consentCheckbox = document.getElementById("lead-consent");
    const closeBtn = document.querySelector(".modal-close");

    const readMoreButtons = document.querySelectorAll(".read-more-locked");
    const trialExpiredNotices = document.querySelectorAll(".trial-expired-notice")
    const subscriptionNotices = document.querySelectorAll(".confirm-email-notice")

    const accessLoader = document.getElementById("access-loader");
    const lockedContents = document.querySelectorAll(".content-locked");


    // ===============================
    // 🔸 LOADER
    // ===============================
    const showLoader = (text) => {
        if (!accessLoader) return;
        accessLoader.style.display = "flex";
        const msg = accessLoader.querySelector("p");
        if (msg) msg.textContent = text;
    };

    const hideLoader = () => {
        if (!accessLoader) return;
        accessLoader.style.transition = "opacity 0.3s ease";
        accessLoader.style.opacity = "0";
        setTimeout(() => {
            accessLoader.style.display = "none";
            accessLoader.style.opacity = "1";
        }, 300);
    };


    // ===============================
    // FUNCIONES (declararlas PRIMERO)
    // ===============================

    const showUnlockedContent = () => {
        readMoreButtons.forEach(btn => btn.style.display = "none");
        lockedContents.forEach(div => div.style.display = "block");

        if (!isPageSsuscription) overlay.style.display = "none";
    };

    const showExpiredContent = () => {
        trialExpiredNotices.forEach(n => n.style.display = "block");
        readMoreButtons.forEach(btn => btn.style.display = "none");

        if (!isPageSsuscription) overlay.style.display = "none";
    };

    const storeEmail = (email) => {
        localStorage.setItem("wpscl_e", email);
    };

    const updateSubscriptionPageUI = (statusText) => {
        if (!isPageSsuscription) return;

        submitBtn.textContent = statusText;

        if (statusText === "Subscribed!" || statusText === "Restored!") {
            subscriptionNotices.forEach(n => n.style.display = "block");
        }
    };


    const validateInput = () => {
        if (!submitBtn || !emailInput || !consentCheckbox) return;
        const email = emailInput.value.trim();
        const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        submitBtn.disabled = !(consentCheckbox.checked && isValidEmail);
    };

    const processSuccess = (email) => {
        clearRecaptchaError();
        storeEmail(email);
        showUnlockedContent();
        updateSubscriptionPageUI("Subscribed!");
    };

    const processExpired = (email) => {
        clearRecaptchaError();
        storeEmail(email);
        showExpiredContent();
        updateSubscriptionPageUI("Expired!");
    };

    const clearRecaptchaError = () => {
    const errBox = document.getElementById("recaptcha-error");
    if (errBox) {
        errBox.textContent = "";
        errBox.style.display = "none";
    }
};



    // ===============================
    // SUBMITS
    // ===============================


    const verifyLeadStatus = async (email) => {
        showLoader("Checking subscription status...");

        try {
            const response = await fetch(seocontentlocker_ajax.url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: new URLSearchParams({
                    action: "seocontentlocker_check_lead_status",
                    email,
                    slug: window.location.pathname,
                    nonce: seocontentlocker_ajax.nonce,
                }),
            });

            const { data } = await response.json();

            if (data.status === "success" || data.status === "restored") {
                processSuccess(email);
            }
            else if (data.status === "expired") {
                processExpired(email);
            }

        } catch (err) {
            console.error(err);
        } finally {
            hideLoader();
        }
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        const email = emailInput.value.trim();
        if (!email) return alert("Email invalid");

        submitBtn.disabled = true;
        submitBtn.textContent = "Loading...";

        try {
            const response = await fetch(seocontentlocker_ajax.url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: new URLSearchParams({
                    action: "seocontentlocker_save_lead",
                    email,
                    slug: window.location.pathname,
                    nonce: seocontentlocker_ajax.nonce,
                    "g-recaptcha-response": grecaptcha.getResponse()
                }),
            });

            const { data } = await response.json();

            if (data?.message === "Captcha missing") {
                const errBox = document.getElementById("recaptcha-error");
                if (errBox) {
                    errBox.textContent = "⚠ Please complete the reCAPTCHA.";
                    errBox.style.display = "block";
                }

                submitBtn.disabled = false;
                submitBtn.textContent = "Continue";

                return; // detener flujo
            }

            if (data.status === "success" || data.status === "restored") {
                clearRecaptchaError();
                processSuccess(email);
            }
            else if (data.status === "expired") {
                processExpired(email);
            }

        } catch (err) {
            console.error(err);
        }
    };


    // ===============================
    // EVENTOS (después de las funciones)
    // ===============================

    if (consentCheckbox) consentCheckbox.addEventListener("change", validateInput);
    if (emailInput) emailInput.addEventListener("input", validateInput);
    if (submitBtn) submitBtn.addEventListener("click", handleSubmit);
    if (closeBtn && overlay) closeBtn.addEventListener("click", () => overlay.style.display = "none");

    // Delegación de eventos para locked-btn
    document.addEventListener("click", (e) => {
        if (e.target && e.target.id === "locked-btn") {
            console.log('click en locked-btn');
            if (overlay) overlay.style.display = "flex"; // o "block"
        }
    });

    // ===============================
    // 🔸 FLUJO PRINCIPAL AL CARGAR
    // ===============================
    if (!isPageSsuscription) {

        const email = localStorage.getItem("wpscl_e");
        if (email) {
            verifyLeadStatus(email);
        }
    }
});