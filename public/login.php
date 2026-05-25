<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Department System</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; }
    .auth-box { width: 100%; max-width: 420px; padding: 1rem; }
    .auth-logo { text-align: center; margin-bottom: 2rem; }
    .auth-logo h1 { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--text); }
    .auth-logo h1 span { color: var(--accent); }
    .auth-logo p { color: var(--text-2); font-size: 0.9rem; margin-top: 0.25rem; }
    .auth-footer { text-align: center; margin-top: 1.25rem; font-size: 0.875rem; color: var(--text-2); }
    .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 500; }
    .auth-footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="auth-box">
    <div class="auth-logo">
      <h1>ICT<span>.</span>Community</h1>
      <p>Sign in to your account</p>
    </div>

    <div class="card">
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
        <button type="submit" class="btn btn-primary btn-full" id="loginBtn">Sign In</button>
      </form>
    </div>

    <p class="auth-footer">
      Don't have an account? <a href="signup.php">Sign up</a>
    </p>
  </div>

  <script>
    document.getElementById("loginForm").onsubmit = async (e) => {
      e.preventDefault();
      const btn = document.getElementById("loginBtn");
      const alertBox = document.getElementById("alertBox");
      
      btn.textContent = "Signing in…";
      btn.disabled = true;
      alertBox.innerHTML = "";

      try {
        const res  = await fetch("../app/auth/login.php", { 
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
