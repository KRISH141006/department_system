<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Department System</title>
  <link rel="stylesheet" href="<?= $base_path ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= $base_path ?>/assets/css/auth.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/auth.css') ?>">
</head>
<body class="auth-page login-theme">
  <div class="auth-box">
    <div class="auth-logo">
      <h1>ICT<span id="logoDot" class="logo-dot">.</span>Community</h1>
      <p>Sign in to your account</p>
    </div>

    <div class="card-auth">
      <div id="alertBox"></div>

      <form id="loginForm">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-auth" id="loginBtn">Sign In</button>
      </form>
    </div>

    <p class="auth-footer">
      Don't have an account? <a href="<?= $base_path ?>/signup">Sign up</a>
    </p>
  </div>

  <script>
    const logoDot = document.getElementById('logoDot');

    function triggerDribble() {
      logoDot.classList.remove('dribble-active');
      void logoDot.offsetWidth; // Trigger reflow
      logoDot.classList.add('dribble-active');
    }

    // Initial load animation
    setTimeout(triggerDribble, 1200);

    // Click to restart
    logoDot.addEventListener('click', triggerDribble);

    document.getElementById("loginForm").onsubmit = async (e) => {
      e.preventDefault();
      const btn = document.getElementById("loginBtn");
      const alertBox = document.getElementById("alertBox");
      
      btn.textContent = "Signing in…";
      btn.disabled = true;
      alertBox.innerHTML = "";

      try {
        const res  = await fetch("<?= $base_path ?>/api/auth/login", { 
          method: "POST", 
          body: new FormData(e.target) 
        });
        
        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON response:", text);
          throw new Error("Server returned an invalid response. Check console for details.");
        }

        if (data.status === "success") {
          window.location = data.redirect;
        } else {
          alertBox.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
          btn.textContent = "Sign In";
          btn.disabled = false;
        }
      } catch (err) {
        console.error("Login error:", err);
        alertBox.innerHTML = `<div class="alert alert-error">${err.message || "Connection failed. Please try again."}</div>`;
        btn.textContent = "Sign In";
        btn.disabled = false;
      }
    };
  </script>
</body>
</html>
