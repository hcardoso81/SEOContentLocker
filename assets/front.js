document.addEventListener("DOMContentLoaded", () => {
    const isPageSsuscription = document.getElementById("my-subscription-form-page");

    const overlay = document.getElementById("lead-overlay");
    const submitBtn = document.getElementById("lead-submit");
    const emailInput = document.getElementById("lead-email");
    const consentCheckbox = document.getElementById("lead-consent");
    const closeBtn = document.querySelector(".modal-close");

    const readMoreButtons = document.querySelectorAll(".read-more-locked");
    const trialExpiredNotices = document.querySelectorAll(".trial-expired-notice");
    const subscriptionNotices = document.querySelectorAll(".confirm-email-notice");
    const inlineMessages = document.querySelectorAll(".locker-inline-message");
    const accessLoader = document.getElementById("access-loader");
    const lockedContents = document.querySelectorAll(".content-locked");

    const THANK_YOU_URL = "/thanks-you-newsletter/";
    const DEFAULT_SUBMIT_TEXT = "Unlock Access";

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

    const showUnlockedContent = () => {
        readMoreButtons.forEach((btn) => btn.style.display = "none");
        lockedContents.forEach((div) => div.style.display = "block");

        if (!isPageSsuscription && overlay) {
            overlay.style.display = "none";
        }
    };

    const showExpiredContent = () => {
        trialExpiredNotices.forEach((notice) => notice.style.display = "block");
        readMoreButtons.forEach((btn) => btn.style.display = "none");

        if (!isPageSsuscription && overlay) {
            overlay.style.display = "none";
        }
    };

    const storeEmail = (email) => {
        localStorage.setItem("wpscl_e", email);
    };

    const updateSubscriptionPageUI = (statusText) => {
        if (!isPageSsuscription || !submitBtn) return;

        submitBtn.textContent = statusText;

        if (statusText === "Subscribed!" || statusText === "Restored!") {
            subscriptionNotices.forEach((notice) => notice.style.display = "block");
        }
    };

    const validateInput = () => {
        if (!submitBtn || !emailInput || !consentCheckbox) return;
        const email = emailInput.value.trim();
        const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        submitBtn.disabled = !(consentCheckbox.checked && isValidEmail);
    };

    const clearRecaptchaError = () => {
        const errBox = document.getElementById("recaptcha-error");
        if (errBox) {
            errBox.textContent = "";
            errBox.style.display = "none";
        }
    };

    const setInlineMessage = (message, tone = "info") => {
        inlineMessages.forEach((node) => {
            node.textContent = message;
            node.className = `locker-inline-message is-${tone}`;
        });
    };

    const clearInlineMessage = () => {
        inlineMessages.forEach((node) => {
            node.textContent = "";
            node.className = "locker-inline-message";
        });
    };

    const processSuccess = (email) => {
        clearRecaptchaError();
        clearInlineMessage();
        storeEmail(email);

        if (isPageSsuscription) {
            window.location.href = THANK_YOU_URL;
            return;
        }

        showUnlockedContent();
        updateSubscriptionPageUI("Subscribed!");
    };

    const processExpired = (email) => {
        clearRecaptchaError();
        clearInlineMessage();
        storeEmail(email);
        showExpiredContent();
        updateSubscriptionPageUI("Expired!");
    };

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

            const payload = await response.json();
            const data = payload?.data ?? {};

            if (data.status === "success" || data.status === "restored") {
                processSuccess(email);
                return;
            }

            if (data.status === "expired") {
                processExpired(email);
                return;
            }

            if (!payload?.success) {
                setInlineMessage(data.message || "We could not verify your access right now. Please try again in a moment.", "error");
            }
        } catch (err) {
            console.error(err);
            setInlineMessage("We could not verify your access right now. Please check your connection and try again.", "error");
        } finally {
            hideLoader();
        }
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        const email = emailInput?.value.trim();
        if (!email) {
            return alert("Email invalid");
        }

        clearInlineMessage();
        submitBtn.disabled = true;
        submitBtn.textContent = "Reviewing...";

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

            const payload = await response.json();
            const data = payload?.data ?? {};

            if (data?.message === "Captcha missing") {
                const errBox = document.getElementById("recaptcha-error");
                if (errBox) {
                    errBox.textContent = "Please complete the reCAPTCHA challenge before continuing.";
                    errBox.style.display = "block";
                }

                submitBtn.disabled = false;
                submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
                return;
            }

            if (!payload?.success) {
                setInlineMessage(data.message || "We could not process your request right now. Please try again.", "error");
                submitBtn.disabled = false;
                submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
                return;
            }

            if (data.status === "success" || data.status === "restored") {
                clearRecaptchaError();
                processSuccess(email);
                return;
            }

            if (data.status === "expired") {
                processExpired(email);
                return;
            }

            if (data.status === "mailchimp_failed") {
                setInlineMessage("Your access was created, but we could not confirm newsletter delivery. Please try again later or contact support if the issue persists.", "info");
                if (!isPageSsuscription) {
                    processSuccess(email);
                    return;
                }

                submitBtn.disabled = false;
                submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
                return;
            }

            setInlineMessage(data.message || "We could not process your request right now. Please try again.", "error");
            submitBtn.disabled = false;
            submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
        } catch (err) {
            console.error(err);
            setInlineMessage("A network error interrupted the request. Please try again in a moment.", "error");
            submitBtn.disabled = false;
            submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
        }
    };

    if (consentCheckbox) consentCheckbox.addEventListener("change", validateInput);
    if (emailInput) emailInput.addEventListener("input", validateInput);
    if (submitBtn) submitBtn.addEventListener("click", handleSubmit);
    if (closeBtn && overlay) closeBtn.addEventListener("click", () => overlay.style.display = "none");

    document.addEventListener("click", (e) => {
        if (e.target && e.target.id === "locked-btn" && overlay) {
            overlay.style.display = "flex";
        }
    });

    if (!isPageSsuscription) {
        const email = localStorage.getItem("wpscl_e");
        if (email) {
            verifyLeadStatus(email);
        }
    }
});
