<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();

$returnToInput = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['return_to'] ?? '') : ($_GET['return_to'] ?? '');
$returnTo = app_auth_destination($returnToInput);

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . $returnTo);
    exit;
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginError = 'Firebase sign-in could not start. Please refresh the page and try again.';
}
$queryNotice = '';
$queryInput = $_GET['status'] ?? '';
$query = is_string($queryInput) ? $queryInput : '';
if ($query === 'signed-out') $queryNotice = 'You have been signed out.';
if ($query === 'invalid') $queryNotice = 'Invalid email or password.';
if ($query === 'database') $queryNotice = 'We could not reach the database. Please try again later.';
if ($query === 'session-expired') $queryNotice = 'Your session has expired. Please sign in again.';
if ($query === 'firebase-required') $queryNotice = 'Please sign in through the Firebase-enabled sign-in form.';
if (($_GET['auth'] ?? '') === 'legacy-disabled') $queryNotice = 'Please use the Firebase-enabled sign-in form to continue.';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In | Beamers Legacy</title>
  <meta name="description" content="Sign in to your Beamers Legacy account to manage your configurations, test drives, and bookings." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
</head>

<body class="auth-page">
  <div class="auth-orb auth-orb--blue" aria-hidden="true"></div>
  <div class="auth-orb auth-orb--ice" aria-hidden="true"></div>

  <main class="glass-auth-card" aria-labelledby="login-title">
    <header class="auth-brand">
      <a class="auth-brand-logo" href="home.php" aria-label="Go to Beamers Legacy home">
        <img src="../images/BMW logo.svg" alt="BMW" />
      </a>
      <span class="auth-wordmark">Beamers Legacy</span>
      <h1 class="auth-heading" id="login-title">Welcome back.</h1>
      <p class="auth-copy">Sign in to pick up where your next drive left off.</p>
    </header>

    <p class="auth-notice<?= ($loginError !== "" || $queryNotice !== "") ? " is-visible" : "" ?>" id="authNotice" role="status"><?= e($loginError !== "" ? $loginError : $queryNotice) ?></p>

    <button type="button" class="google-auth-button" id="googleLoginButton">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.35 12.23c0-.72-.06-1.42-.18-2.09H12v3.96h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.69 2.91-4.18 2.91-7.26Z"/><path fill="#34A853" d="M12 21.7c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.53A9.74 9.74 0 0 0 12 21.7Z"/><path fill="#FBBC05" d="M6.54 13.78A5.85 5.85 0 0 1 6.23 12c0-.62.11-1.22.31-1.78V7.69H3.3A9.74 9.74 0 0 0 2.26 12c0 1.56.37 3.03 1.04 4.31l3.24-2.53Z"/><path fill="#EA4335" d="M12 6.19c1.43 0 2.71.49 3.72 1.46l2.79-2.79C16.83 3.26 14.63 2.3 12 2.3a9.74 9.74 0 0 0-8.7 5.39l3.24 2.53C7.31 7.91 9.46 6.19 12 6.19Z"/></svg>
      Continue with Google
    </button>

    <div class="divider-text"><span>or sign in with email</span></div>

    
<form id="customerLoginForm" action="loginform.php" method="POST"><input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
  <div class="form-group-premium">
    <label for="email">Email address</label>
    <input type="email" id="email" name="email" placeholder="name@example.com" required autocomplete="username" />
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
    <button class="forgot-link" id="forgotPasswordButton" type="button">Forgot password?</button>
  </div>
  <p class="auth-notice" id="firebaseMessage" role="status"></p>
  <button type="button" class="btn-blue auth-submit" id="linkGoogleButton" hidden style="display: none;">Link Google to this account</button>
  <button type="submit" class="btn-blue auth-submit">Sign in</button>
</form>

    <p class="auth-redirect-hint">
      New to Beamers Legacy? <a href="register.php">Create an account</a>
    </p>
  </main>

  <script>
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    if (password && togglePassword) {
      togglePassword.addEventListener('click', () => {
        const isHidden = password.type === 'password';
        password.type = isHidden ? 'text' : 'password';
        togglePassword.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        togglePassword.setAttribute('aria-pressed', String(isHidden));
        togglePassword.querySelector('i').className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
      });
    }
  </script>

<script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-auth-compat.js"></script>
<script src="../js/firebase-config.js"></script>
<script src="../js/customer-auth.js"></script>
<script>
const loginForm = document.getElementById('customerLoginForm');
const googleLoginButton = document.getElementById('googleLoginButton');
const authNotice = document.getElementById('authNotice');
const firebaseMessage = document.getElementById('firebaseMessage');
const linkGoogleButton = document.getElementById('linkGoogleButton');
const forgotPasswordButton = document.getElementById('forgotPasswordButton');
const loginCsrf = loginForm.querySelector('[name="csrf"]').value;
const loginReturnTo = loginForm.querySelector('[name="return_to"]').value;
let pendingGoogleCredential = null;

function showLoginMessage(target, text) {
  target.textContent = text;
  target.classList.add('is-visible');
}

forgotPasswordButton.addEventListener('click', async function () {
  const emailInput = document.getElementById('email');
  const email = emailInput.value.trim();
  if (!email || !emailInput.checkValidity()) {
    emailInput.focus();
    showLoginMessage(firebaseMessage, 'Enter a valid email address first, then request a password reset.');
    return;
  }

  forgotPasswordButton.disabled = true;
  showLoginMessage(firebaseMessage, 'Sending password reset email…');
  try {
    await customerAuth.sendPasswordReset(email);
    showLoginMessage(firebaseMessage, 'If a Firebase account exists for this email, a password reset email has been sent. Check your inbox and spam folder.');
  } catch (error) {
    showLoginMessage(firebaseMessage, customerAuth.errorMessage(error));
  } finally {
    forgotPasswordButton.disabled = false;
  }
});

async function finishLogin(user) {
  const synced = await customerAuth.sync('backend/firebase-login.php', loginCsrf, user, {
    returnTo: loginReturnTo,
    firstName: user.displayName ? user.displayName.trim().split(/\s+/)[0] : '',
    lastName: user.displayName ? user.displayName.trim().split(/\s+/).slice(1).join(' ') : ''
  });
  window.location.href = synced.redirect;
}

loginForm.addEventListener('submit', async function (event) {
  event.preventDefault();
  const email = document.getElementById('email').value.trim();
  const passwordValue = document.getElementById('password').value;
  showLoginMessage(firebaseMessage, 'Signing in securely…');
  try {
    let credential;
    try {
      credential = await firebase.auth().signInWithEmailAndPassword(email, passwordValue);
    } catch (authError) {
      if (!['auth/user-not-found', 'auth/invalid-credential'].includes(authError.code)) throw authError;
      const migration = await customerAuth.prepareLegacyLink(loginCsrf, email, passwordValue);
      if (!migration.legacy_link_authorized) throw authError;
      try {
        credential = await firebase.auth().createUserWithEmailAndPassword(email, passwordValue);
      } catch (createError) {
        if (createError.code === 'auth/email-already-in-use') throw authError;
        throw createError;
      }
    }
    await finishLogin(credential.user);
  } catch (error) {
    showLoginMessage(firebaseMessage, customerAuth.errorMessage(error));
  }
});

googleLoginButton.addEventListener('click', async function () {
  showLoginMessage(authNotice, 'Connecting to Google…');
  try {
    const provider = new firebase.auth.GoogleAuthProvider();
    provider.setCustomParameters({prompt: 'select_account'});
    const credential = await firebase.auth().signInWithPopup(provider);
    await finishLogin(credential.user);
  } catch (error) {
    if (error.code === 'auth/account-exists-with-different-credential') {
      pendingGoogleCredential = firebase.auth.GoogleAuthProvider.credentialFromError(error);
      linkGoogleButton.hidden = !pendingGoogleCredential;
      linkGoogleButton.style.display = pendingGoogleCredential ? 'inline-flex' : 'none';
    }
    showLoginMessage(authNotice, customerAuth.errorMessage(error));
  }
});

linkGoogleButton.addEventListener('click', async function () {
  try {
    const email = document.getElementById('email').value.trim();
    const passwordValue = document.getElementById('password').value;
    if (!email || !passwordValue || !pendingGoogleCredential) {
      throw new Error('Enter your existing email and password first, then try linking Google.');
    }
    showLoginMessage(firebaseMessage, 'Linking your Google account…');
    const credential = await firebase.auth().signInWithEmailAndPassword(email, passwordValue);
    await credential.user.linkWithCredential(pendingGoogleCredential);
    await finishLogin(credential.user);
  } catch (error) {
    showLoginMessage(firebaseMessage, customerAuth.errorMessage(error));
  }
});
</script>
</body>
</html>
