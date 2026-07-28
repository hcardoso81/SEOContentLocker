document.addEventListener("DOMContentLoaded", () => {
    const pageForms = document.querySelectorAll("#my-subscription-form-page, #my-subscription-form-site");
    const modalForm = document.getElementById("lead-capture-form");
    const isPageSubscription = pageForms.length > 0;

    const overlay = document.getElementById("lead-overlay");
    const closeBtn = document.querySelector(".modal-close");
    const accessLoader = document.getElementById("access-loader");

    const readMoreButtons = document.querySelectorAll(".read-more-locked");
    const trialExpiredNotices = document.querySelectorAll(".trial-expired-notice");
    const subscriptionNotices = document.querySelectorAll(".confirm-email-notice");
    const lockedContents = document.querySelectorAll(".content-locked");
    const hasLockedExperience = Boolean(modalForm) || readMoreButtons.length > 0 || lockedContents.length > 0;
    const isPost = typeof seocontentlocker_ajax !== "undefined" && Boolean(seocontentlocker_ajax.isPost);

    const THANK_YOU_URL = "/your-intermarketflow-access-is-confirmed/";
    const EMAIL_STORAGE_KEY = "wpscl_e";
    const DEFAULT_SUBMIT_TEXT = "Continue";
    const LOADING_SUBMIT_TEXT = "Loading...";

    const getFields = (form) => {
        if (!form) return null;

        return {
            form,
            submitBtn: form.querySelector("#lead-submit"),
            firstNameInput: form.querySelector("#lead-first-name"),
            emailInput: form.querySelector("#lead-email"),
            consentCheckbox: form.querySelector("#lead-consent"),
            recaptchaError: form.querySelector("#recaptcha-error"),
            requiresRecaptcha: form.dataset.recaptchaRequired === "1",
            isLanding: form.dataset.landing === "1",
            isPageForm: form.id === "my-subscription-form-page" || form.id === "my-subscription-form-site",
        };
    };

    const modalFields = getFields(modalForm);
    const pageFields = Array.from(pageForms).map(getFields);

    const showLoader = (text) => {
        if (!accessLoader) return;

        accessLoader.style.display = "flex";
        const message = accessLoader.querySelector("p");
        if (message) {
            message.textContent = text;
        }
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

    const clearRecaptchaError = (fields) => {
        if (!fields?.recaptchaError) return;

        fields.recaptchaError.textContent = "";
        fields.recaptchaError.style.display = "none";
    };

    const showRecaptchaError = (fields, message) => {
        if (!fields?.recaptchaError) return;

        fields.recaptchaError.textContent = message;
        fields.recaptchaError.style.display = "block";
    };

    const showSubmissionError = (fields, message) => {
        if (!fields?.form) return;

        let error = fields.form.querySelector(".locker-form-error");
        if (!error) {
            error = document.createElement("p");
            error.className = "locker-form-error";
            error.style.color = "#b42318";
            fields.form.insertBefore(error, fields.form.firstChild);
        }

        error.textContent = message || "The subscription could not be processed.";
        error.style.display = "block";
    };

    const setSubmitState = (fields, disabled, text) => {
        if (!fields?.submitBtn) return;

        fields.submitBtn.disabled = disabled;
        fields.submitBtn.textContent = text;
    };

    const getRecaptchaResponse = (fields) => {
        if (!fields?.requiresRecaptcha) return "";

        const responseInput = fields.form.querySelector('[name="g-recaptcha-response"]');
        if (responseInput?.value) {
            return responseInput.value;
        }

        return typeof grecaptcha !== "undefined" ? grecaptcha.getResponse() : "";
    };

    const validateInput = (fields) => {
        if (!fields?.submitBtn || !fields.emailInput || !fields.firstNameInput) return;

        const firstName = fields.firstNameInput.value.trim();
        const email = fields.emailInput.value.trim();
        const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        const hasConsent = !fields.consentCheckbox || fields.consentCheckbox.checked;
        fields.submitBtn.disabled = !(firstName && hasConsent && isValidEmail);
    };

    const storeEmail = (email) => {
        localStorage.setItem(EMAIL_STORAGE_KEY, email);
    };

    const hideOverlay = () => {
        if (overlay) {
            overlay.style.display = "none";
        }
    };

    const showUnlockedContent = () => {
        readMoreButtons.forEach((button) => {
            button.style.display = "none";
        });

        lockedContents.forEach((content) => {
            content.style.display = "block";
        });

        if (!isPageSubscription) {
            hideOverlay();
        }
    };

    const showExpiredContent = () => {
        trialExpiredNotices.forEach((notice) => {
            notice.style.display = "block";
        });

        readMoreButtons.forEach((button) => {
            button.style.display = "none";
        });

        if (!isPageSubscription) {
            hideOverlay();
        }
    };

    const updateSubscriptionPageUI = (fields, statusText) => {
        if (!isPageSubscription || !fields?.submitBtn) return;

        fields.submitBtn.textContent = statusText;

        if (statusText === "Subscribed!" || statusText === "Restored!") {
            subscriptionNotices.forEach((notice) => {
                notice.style.display = "block";
            });
        }
    };

    const processSuccess = (fields, email) => {
        clearRecaptchaError(fields);
        storeEmail(email);

        if (fields?.isPageForm) {
            window.location.href = fields.isLanding
                ? "/thank-you/"
                : THANK_YOU_URL;
            return;
        }

        showUnlockedContent();
        updateSubscriptionPageUI(fields, "Subscribed!");
    };

    const processExpired = (fields, email) => {
        clearRecaptchaError(fields);
        storeEmail(email);
        showExpiredContent();
        updateSubscriptionPageUI(fields, "Expired!");
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

            const { data } = await response.json();

            if (data.status === "success" || data.status === "restored") {
                processSuccess(modalFields, email);
                return;
            }

            if (data.status === "expired") {
                processExpired(modalFields, email);
            }
        } catch (err) {
            console.error(err);
        } finally {
            hideLoader();
        }
    };

    const handleSubmit = async (fields, event) => {
        event.preventDefault();

        if (!fields?.firstNameInput || !fields.emailInput || !fields.submitBtn) return;

        const firstName = fields.firstNameInput.value.trim();
        const email = fields.emailInput.value.trim();
        if (!firstName) {
            fields.firstNameInput.focus();
            return;
        }

        if (!email) {
            fields.emailInput.focus();
            return;
        }

        clearRecaptchaError(fields);
        setSubmitState(fields, true, LOADING_SUBMIT_TEXT);

        try {
            const recaptchaResponse = getRecaptchaResponse(fields);

            const response = await fetch(seocontentlocker_ajax.url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: new URLSearchParams({
                    action: "seocontentlocker_save_lead",
                    first_name: firstName,
                    email,
                    slug: window.location.pathname,
                    nonce: seocontentlocker_ajax.nonce,
                    source: fields.isPageForm
                        ? (fields.requiresRecaptcha ? "subscription_page_site" : "subscription_page")
                        : "modal",
                    landing: fields.isLanding ? "1" : "0",
                    "g-recaptcha-response": recaptchaResponse
                }),
            });

            const { data } = await response.json();

            if (data?.message === "Captcha missing") {
                showRecaptchaError(fields, "Please complete the reCAPTCHA.");
                validateInput(fields);
                fields.submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
                return;
            }

            if (data.status === "success" || data.status === "restored") {
                processSuccess(fields, email);
                return;
            }

            if (data.status === "expired") {
                processExpired(fields, email);
                return;
            }

            if (data.status === "same_ip_blocked") {
                showSubmissionError(fields, data.message);
                validateInput(fields);
                fields.submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
                return;
            }

            validateInput(fields);
            fields.submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
        } catch (err) {
            console.error(err);
            validateInput(fields);
            fields.submitBtn.textContent = DEFAULT_SUBMIT_TEXT;
        }
    };

    [modalFields, ...pageFields].forEach((fields) => {
        if (!fields?.form) return;

        if (fields.consentCheckbox) {
            fields.consentCheckbox.addEventListener("change", () => validateInput(fields));
        }

        if (fields.emailInput) {
            fields.emailInput.addEventListener("input", () => validateInput(fields));
        }

        if (fields.firstNameInput) {
            fields.firstNameInput.addEventListener("input", () => validateInput(fields));
        }

        fields.form.addEventListener("submit", (event) => handleSubmit(fields, event));
        validateInput(fields);
    });

    if (closeBtn && overlay) {
        closeBtn.addEventListener("click", hideOverlay);
    }

    if (overlay) {
        overlay.addEventListener("click", (event) => {
            if (event.target === overlay || event.target.classList.contains("overlay-backdrop")) {
                hideOverlay();
            }
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && overlay && overlay.style.display !== "none") {
            hideOverlay();
        }
    });

    document.addEventListener("click", (event) => {
        if (event.target && event.target.id === "locked-btn" && overlay) {
            overlay.style.display = "flex";
        }
    });

    if (isPost && !isPageSubscription && hasLockedExperience) {
        const email = localStorage.getItem(EMAIL_STORAGE_KEY);
        if (email) {
            verifyLeadStatus(email);
        }
    }
});
