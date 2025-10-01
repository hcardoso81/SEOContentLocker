document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("seo-locker-submit");
  if (!btn) return;

  btn.addEventListener("click", () => {
    const email = document.getElementById("seo-locker-email").value;
    const msg = document.getElementById("seo-locker-msg");

    fetch(seo_locker_ajax.url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "seo_locker_save_lead",
        nonce: seo_locker_ajax.nonce,
        email,
        slug: window.location.pathname
      }),
    })
      .then(res => res.json())
      .then(data => {
        msg.textContent = data.success
          ? "¡Desbloqueado! Recargá la página."
          : data.data.message;
        if (data.success) {
          location.reload();
        }
      });
  });
});
