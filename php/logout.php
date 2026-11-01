<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!app_verify_csrf()) {
        http_response_code(403);
        exit('Your session token expired. Please refresh and sign out again.');
    }
    app_destroy_session();
    header('Location: loginform.php?status=signed-out');
    exit;
}

$csrf = app_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signing out | Beamers Legacy</title>
</head>
<body>
  <p id="signOutStatus" role="status">Signing you out securely…</p>
  <form id="signOutForm" method="POST" action="logout.php">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  </form>
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-auth-compat.js"></script>
  <script src="../js/firebase-config.js"></script>
  <script>
    (async function () {
      const status = document.getElementById('signOutStatus');
      try {
        if (typeof firebase !== 'undefined' && firebase.apps.length) {
          await firebase.auth().signOut();
        }
      } catch (error) {
        status.textContent = 'Firebase could not be signed out, but the website session is closing.';
      } finally {
        document.getElementById('signOutForm').submit();
      }
    }());
  </script>
</body>
</html>
