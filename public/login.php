<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ICT Department Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #0f172a;
      --accent: #2563eb;
      --accent-soft: rgba(37, 99, 235, 0.1);
      --bg: #f8fafc;
      --card-bg: rgba(255, 255, 255, 0.95);
      --text: #1e293b;
      --text-light: #64748b;
      --border: #e2e8f0;
      --radius: 16px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background-color: var(--bg);
      /* Using the local copy of your architectural sketch */
      background-image: linear-gradient(rgba(248, 250, 252, 0.8), rgba(248, 250, 252, 0.8)), url('assets/img/bg_sketch.png');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .login-wrapper {
      width: 100%;
      max-width: 440px;
      padding: 20px;
      animation: fadeIn 0.6s ease-out;
      position: relative;
      z-index: 2;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .login-card {
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: var(--radius);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      border: 1px solid var(--border);
      padding: 40px;
      position: relative;
    }

    .brand {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-logo {
      width: 64px;
      height: 64px;
      background: var(--accent);
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.75rem;
      margin-bottom: 16px;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .brand h1 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.75rem;
      color: var(--primary);
      margin-bottom: 4px;
    }

    .brand p {
      color: var(--text-light);
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--primary);
      margin-bottom: 8px;
    }

    .input-container {
      position: relative;
    }

    .input-container i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      transition: color 0.2s;
    }

    .input-container input {
      width: 100%;
      padding: 12px 16px 12px 44px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 0.95rem;
      font-family: inherit;
      color: var(--text);
      transition: all 0.2s;
      background: #fcfcfc;
    }

    .input-container input:focus {
      outline: none;
      border-color: var(--accent);
      background: #fff;
      box-shadow: 0 0 0 4px var(--accent-soft);
    }

    .input-container input:focus + i {
      color: var(--accent);
    }

    .btn-submit {
      width: 100%;
      padding: 12px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-submit:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    .btn-submit:disabled {
      background: #94a3b8;
      cursor: not-allowed;
      transform: none;
    }

    .footer-links {
      margin-top: 24px;
      text-align: center;
      font-size: 0.875rem;
      color: var(--text-light);
    }

    .footer-links a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.875rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-error {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="brand">
        <div class="brand-logo">
          <i class="fas fa-microchip"></i>
        </div>
        <h1>ICT Department</h1>
        <p>Academic Management System</p>
      </div>

      <div id="alertBox"></div>

      <form id="loginForm">
        <div class="form-group">
          <label for="email">Institutional Email</label>
          <div class="input-container">
            <i class="far fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="name@ict.com" required>
          </div>
        </div>

        <div class="form-group">
          <label for="password">Security Password</label>
          <div class="input-container">
            <i class="fas fa-shield-alt"></i>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" class="btn-submit" id="loginBtn">
          <span>Sign In</span>
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <div class="footer-links">
        <p>New to the portal? <a href="signup.php">Create an account</a></p>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("loginForm").onsubmit = async (e) => {
      e.preventDefault();
      const btn = document.getElementById("loginBtn");
      const alertBox = document.getElementById("alertBox");
      
      const originalText = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
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
          throw new Error("Internal server error. Please contact administrator.");
        }

        if (data.status === "success") {
          btn.innerHTML = '<i class="fas fa-check"></i> Success!';
          setTimeout(() => {
            window.location = data.redirect;
          }, 600);
        } else {
          alertBox.innerHTML = `
            <div class="alert alert-error">
              <i class="fas fa-exclamation-circle"></i>
              <span>${data.message}</span>
            </div>`;
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
      } catch (err) {
        console.error("Login error:", err);
        alertBox.innerHTML = `
          <div class="alert alert-error">
            <i class="fas fa-wifi-slash"></i>
            <span>Connection failed. Please check your network.</span>
          </div>`;
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    };
  </script>
</body>
</html>
