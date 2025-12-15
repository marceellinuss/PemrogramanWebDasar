document.addEventListener("DOMContentLoaded", function () {
  const registerTamuForm  = document.getElementById("registerTamuForm");
  const registerAdminForm = document.getElementById("registerAdminForm");
  const loginForm         = document.getElementById("loginForm");
  const loginAdminForm    = document.getElementById("loginAdminForm");

  function validasiPendaftaran(form) {
    const username = form.querySelector("input[name='username']").value.trim();
    const email    = form.querySelector("input[name='email']").value.trim();
    const password = form.querySelector("input[name='password']").value;

    if (!username) {
      alert("Username wajib diisi!");
      return false;
    }

    if (!email || !email.includes("@")) {
      alert("Email tidak valid!");
      return false;
    }

    if (!password || password.length < 6) {
      alert("Password minimal 6 karakter.");
      return false;
    }

    return true;
  }

  function validasiLogin(form) {
    const email    = form.querySelector("input[name='email']").value.trim();
    const password = form.querySelector("input[name='password']").value;

    if (!email || !email.includes("@")) {
      alert("Email tidak valid!");
      return false;
    }

    if (!password) {
      alert("Password wajib diisi.");
      return false;
    }

    if (password.length < 6) {
      alert("Password minimal 6 karakter.");
      return false;
    }

    return true;
  }

  if (registerTamuForm) {
    registerTamuForm.addEventListener("submit", function (e) {
      if (!validasiPendaftaran(registerTamuForm)) {
        e.preventDefault();
      }
    });
  }

  if (registerAdminForm) {
    registerAdminForm.addEventListener("submit", function (e) {
      if (!validasiPendaftaran(registerAdminForm)) {
        e.preventDefault();
      }
    });
  }

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      if (!validasiLogin(loginForm)) {
        e.preventDefault();
      }
    });
  }

  if (loginAdminForm) {
    loginAdminForm.addEventListener("submit", function (e) {
      if (!validasiLogin(loginAdminForm)) {
        e.preventDefault();
      }
    });
  }

  const cekLihatPassword = document.querySelectorAll(".cekLihatPassword");

  cekLihatPassword.forEach(function (cek) {
    const form = cek.closest("form");
    if (!form) return;

    const inputPassword = form.querySelector("input[name='password']");
    if (!inputPassword) return;

    cek.addEventListener("change", function () {
      if (cek.checked) {
        inputPassword.type = "text";
      } else {
        inputPassword.type = "password";
      }
    });
  });
});
