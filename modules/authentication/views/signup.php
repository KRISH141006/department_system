<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up — ICT Community</title>
  <link rel="stylesheet" href="<?= $base_path ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= $base_path ?>/assets/css/auth.css">
</head>
<body class="auth-page signup-theme">
  <div class="auth-box">
    <div class="auth-logo">
      <h1>ICT<span id="logoDot" class="logo-dot">.</span>Community</h1>
      <p>Create your account</p>
    </div>

    <div class="card-auth">
      <div id="alertBox"></div>

      <form id="signupForm">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" placeholder="John Doe" required>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" placeholder="+91 9876543210" required>
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
          <label>Role</label>
          <select name="role">
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
            <option value="expert">Expert</option>
          </select>
        </div>

        <button type="submit" class="btn-auth" id="sendBtn">Send OTP</button>
      </form>

      <div id="otpBox" style="display:none;">
        <p class="step-label">Step 2 — Verify your email</p>
        <div class="form-group">
          <label>Enter OTP sent to your email</label>
          <input type="text" id="otpInput" placeholder="6-digit code" maxlength="6">
        </div>
        <button onclick="verifyOTP()" class="btn-auth" id="verifyBtn">Verify & Create Account</button>
      </div>
    </div>

    <p class="auth-footer">Already have an account? <a href="<?= $base_path ?>/login">Sign in</a></p>
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

    let signupFormData;

    document.getElementById("signupForm").onsubmit = async (e) => {
      e.preventDefault();
      const btn = document.getElementById("sendBtn");
      const alertBox = document.getElementById("alertBox");
      
      btn.textContent = "Sending…";
      btn.disabled = true;
      alertBox.innerHTML = "";

      signupFormData = new FormData(e.target);

      try {
        const res  = await fetch("<?= $base_path ?>/api/auth/send_otp", {
          method: "POST",
          body: signupFormData,
          credentials: "same-origin"
        });
        
        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON response:", text);
          alertBox.innerHTML = `<div class="alert alert-error"><strong>Server Error:</strong><br><pre style="white-space:pre-wrap;font-size:0.7rem;margin-top:0.5rem;background:rgba(0,0,0,0.05);padding:0.5rem;">${text || 'Empty response'}</pre></div>`;
          btn.textContent = "Send OTP";
          btn.disabled = false;
          return;
        }

        if (data.status === "success") {
          document.getElementById("otpBox").style.display = "block";
          alertBox.innerHTML =
            `<div class="alert alert-success">OTP sent! Check your inbox.</div>`;
          btn.textContent = "Resend OTP";
          btn.disabled = false;
        } else {
          alertBox.innerHTML =
            `<div class="alert alert-error">${data.message || "Error sending OTP"}</div>`;
          btn.textContent = "Send OTP";
          btn.disabled = false;
        }
      } catch (err) {
        console.error("Signup error:", err);
        alertBox.innerHTML =
          `<div class="alert alert-error">${err.message || "Network error. Try again."}</div>`;
        btn.textContent = "Send OTP";
        btn.disabled = false;
      }
    };

    async function verifyOTP() {
      const otp = document.getElementById("otpInput").value.trim();
      const alertBox = document.getElementById("alertBox");

      if (!otp || otp.length !== 6) {
        alertBox.innerHTML =
          `<div class="alert alert-error">Please enter the 6-digit OTP.</div>`;
        return;
      }

      const btn = document.getElementById("verifyBtn");
      btn.textContent = "Verifying…";
      btn.disabled = true;

      const payload = new FormData();
      payload.append("otp", otp);

      try {
        const res  = await fetch("<?= $base_path ?>/api/auth/verify_otp", {
          method: "POST",
          body: payload,
          credentials: "same-origin"
        });
        
        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON response:", text);
          alertBox.innerHTML = `<div class="alert alert-error"><strong>Server Error:</strong><br><pre style="white-space:pre-wrap;font-size:0.7rem;margin-top:0.5rem;background:rgba(0,0,0,0.05);padding:0.5rem;">${text || 'Empty response'}</pre></div>`;
          btn.textContent = "Verify & Create Account";
          btn.disabled = false;
          return;
        }

        if (data.status === "success") {
          alertBox.innerHTML =
            `<div class="alert alert-success">Account created! Redirecting…</div>`;
          setTimeout(() => {
            window.location.href = data.redirect || "<?= $base_path ?>/login";
          }, 1000);
        } else {
          alertBox.innerHTML =
            `<div class="alert alert-error">${data.message || "Invalid OTP"}</div>`;
          btn.textContent = "Verify & Create Account";
          btn.disabled = false;
        }
      } catch (err) {
        console.error("Verification error:", err);
        alertBox.innerHTML =
          `<div class="alert alert-error">${err.message || "Network error. Try again."}</div>`;
        btn.textContent = "Verify & Create Account";
        btn.disabled = false;
      }
    }
  </script>
</body>
</html>
