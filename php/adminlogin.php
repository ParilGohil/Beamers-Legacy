<?php
require_once __DIR__ . '/config.php';
start_app_session();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Staff Sign In | Beamers Legacy</title>
  <meta name="description" content="Secure sign-in for the Beamers Legacy administration dashboard." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
</head>

<body class="auth-page auth-page--admin">
  <div class="auth-orb auth-orb--blue" aria-hidden="true"></div>
  <div class="auth-orb auth-orb--ice" aria-hidden="true"></div>

  <main class="glass-auth-card" aria-labelledby="admin-login-title">
    <header class="auth-brand">
      <a class="auth-brand-logo" href="home.php" aria-label="Go to Beamers Legacy home">
        <img src="../images/BMW logo.svg" alt="BMW" />
      </a>
      <span class="auth-wordmark">Beamers Legacy</span>
      <span class="auth-eyebrow">Staff portal</span>
      <h1 class="auth-heading" id="admin-login-title">Authorized access.</h1>
      <p class="auth-copy">Sign in with your department credentials to manage the showroom.</p>
    </header>

    <p class="auth-notice" id="portalMessage" role="status"></p>

    <form action="admin/login.php" method="POST"><input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">
      <div class="form-group-premium">
        <label for="email">Work email</label>
        <input type="email" id="email" name="email" placeholder="admin@beamers.com" required autocomplete="username" />
      </div>

      <div class="form-group-premium">
        <label for="password">Password</label>
        <div class="password-field">
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password" />
          <button class="password-toggle" id="togglePassword" type="button" aria-label="Show password" aria-pressed="false">
            <i class="fa-solid fa-eye" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div class="form-row-utility">
        <span class="forgot-link">Need help? Contact the system administrator.</span>
      </div>

      <button type="submit" class="btn-blue auth-submit">Access dashboard</button>
    </form>

    <p class="auth-redirect-hint">
      Looking for the customer portal? <a href="loginform.php">Customer sign in</a>
    </p>
  </main>

  <script>
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', () => {
      const isHidden = password.type === 'password';
      password.type = isHidden ? 'text' : 'password';
      togglePassword.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      togglePassword.setAttribute('aria-pressed', String(isHidden));
      togglePassword.querySelector('i').className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    });

    const portalMessage = document.getElementById('portalMessage');
    const loginState = new URLSearchParams(window.location.search);
    let message = '';

    if (loginState.get('error') === 'session') {
      message = 'Your staff session has ended. Please sign in again.';
    } else if (loginState.get('error')) {
      message = 'Invalid work email or password.';
    } else if (loginState.get('status') === 'signed-out') {
      message = 'You have been signed out.';
    }

    if (message) {
      portalMessage.textContent = message;
      portalMessage.classList.add('is-visible');
    }
  </script>
</body>

</html>
