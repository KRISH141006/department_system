<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up — ICT Department Portal</title>
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
      --card-bg: rgba(255, 255, 255, 0.9);
      --text: #1e293b;
      --text-light: #64748b;
      --border: #e2e8f0;
      --radius: 16px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background-color: var(--bg);
      /* Using the same architectural sketch background as login */
      background-image: linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.4)), url('assets/img/bg_sketch.png');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 40px 20px;
    }

    .auth-wrapper {
      width: 100%;
      max-width: 480px;
      animation: fadeIn 0.6s ease-out;
      position: relative;
      z-index: 2;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .auth-card {
      background: var(--card-bg);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-radius: var(--radius);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      border: 1px solid var(--border);
      padding: 40px;
    }

    .brand {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-logo {
      width: 56px;
      height: 56px;
      background: var(--accent);
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      margin-bottom: 12px;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .brand h1 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.5rem;
      color: var(--primary);
      margin-bottom: 4px;
    }

    .brand p {
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 6px;
    }

    .input-container {
      position: relative;
    }

    .input-container i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .input-container input, .input-container select {
      width: 100%;
      padding: 10px 14px 10px 38px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 0.9rem;
      font-family: inherit;
      color: var(--text);
      transition: all 0.2s;
      background: rgba(255, 255, 255, 0.8);
      appearance: none;
    }

    .input-container input:focus, .input-container select:focus {
      outline: none;
      border-color: var(--accent);
      background: #fff;
      box-shadow: 0 0 0 4px var(--accent-soft);
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .btn-submit {
      width: 100%;
      padding: 12px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 8px;
    }

    .btn-submit:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .btn-submit:disabled {
      background: #94a3b8;
      cursor: not-allowed;
    }

    #otpBox {
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px dashed var(--border);
    }

    .step-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--accent);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 12px;
      display: block;
    }

    .footer-links {
      margin-top: 24px;
      text-align: center;
      font-size: 0.85rem;
      color: var(--text-light);
    }

    .footer-links a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
    }

    .alert {
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="brand">
        <div class="brand-logo">
          <i class="fas fa-user-plus"></i>
        </div>
        <h1>Create Account</h1>
        <p>Join the ICT Department Community</p>
      </div>

      <div id="alertBox"></div>

      <form id="signupForm">
        <div class="form-group">
          <label>Full Name</label>
          <div class="input-container">
            <i class="far fa-user"></i>
            <input type="text" name="name" placeholder="John Doe" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Email Address</label>
            <div class="input-container">
              <i class="far fa-envelope"></i>
              <input type="email" name="email" placeholder="you@ict.com" required>
            </div>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <div class="input-container">
              <i class="fas fa-phone-alt"></i>
              <input type="text" name="phone" placeholder="+91 9876543210" required>
            </div>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Security Password</label>
            <div class="input-container">
              <i class="fas fa-shield-alt"></i>
              <input type="password" name="password" placeholder="••••••••" required>
            </div>
          </div>
          <div class="form-group">
            <label>Account Role</label>
            <div class="input-container">
              <i class="fas fa-users-cog"></i>
              <select name="role" style="padding-right: 30px;">
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="expert">Expert</option>
              </select>
              <i class="fas fa-chevron-down" style="left: auto; right: 14px; pointer-events: none; font-size: 0.7rem;"></i>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-submit" id="sendBtn">
          <span>Send OTP</span>
          <i class="fas fa-paper-plane"></i>
        </button>
      </form>

      <div id="otpBox" style="display:none;">
        <span class="step-label">Step 2 — Identity Verification</span>
        <div class="form-group">
          <label>Enter 6-digit OTP</label>
          <div class="input-container">
            <i class="fas fa-key"></i>
            <input type="text" id="otpInput" placeholder="000000" maxlength="6" style="letter-spacing: 0.2em; font-weight: 700;">
          </div>
        </div>
        <button onclick="verifyOTP()" class="btn-submit" id="verifyBtn" style="background: #059669;">
          <span>Verify & Create Account</span>
          <i class="fas fa-check-circle"></i>
        </button>
      </div>

      <div class="footer-links">
        <p>Already a member? <a href="login.php">Sign in here</a></p>
      </div>
    </div>
  </div>

  <script>
    let signupFormData;

    document.getElementById("signupForm").onsubmit = async (e) => {
      e.preventDefault();
      const btn = document.getElementById("sendBtn");
      const alertBox = document.getElementById("alertBox");
      
      const originalText = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
      btn.disabled = true;
      alertBox.innerHTML = "";

      signupFormData = new FormData(e.target);

      try {
        const res  = await fetch("../app/auth/send_otp.php", {
          method: "POST",
          body: signupFormData,
          credentials: "same-origin"
        });
        
        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error("Server communication failed.");
        }

        if (data.status === "success") {
          document.getElementById("otpBox").style.display = "block";
          alertBox.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> OTP sent to your email!</div>`;
          btn.innerHTML = '<span>Resend OTP</span><i class="fas fa-redo"></i>';
          btn.disabled = false;
        } else {
          alertBox.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> ${data.message}</div>`;
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
      } catch (err) {
        alertBox.innerHTML = `<div class="alert alert-error"><i class="fas fa-wifi-slash"></i> Network error. Please try again.</div>`;
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    };

    async function verifyOTP() {
      const otp = document.getElementById("otpInput").value.trim();
      const alertBox = document.getElementById("alertBox");
      const btn = document.getElementById("verifyBtn");

      if (!otp || otp.length !== 6) {
        alertBox.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Please enter the 6-digit OTP.</div>`;
        return;
      }

      const originalText = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
      btn.disabled = true;

      const payload = new FormData();
      payload.append("otp", otp);

      try {
        const res  = await fetch("../app/auth/verify_otp.php", {
          method: "POST",
          body: payload,
          credentials: "same-origin"
        });
        
        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error("Verification failed.");
        }

        if (data.status === "success") {
          alertBox.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> Account created! Redirecting...</div>`;
          setTimeout(() => window.location.href = "login.php", 1000);
        } else {
          alertBox.innerHTML = `<div class="alert alert-error"><i class="fas fa-times-circle"></i> ${data.message}</div>`;
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
      } catch (err) {
        alertBox.innerHTML = `<div class="alert alert-error"><i class="fas fa-wifi-slash"></i> Connection lost. Please try again.</div>`;
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    }
  </script>
</body>
</html>
