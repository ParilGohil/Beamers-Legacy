<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !app_verify_csrf()) {
    http_response_code(419);
    exit('Your session expired. Please go back and submit the form again.');
}
$prefillName = trim((string)($_SESSION['first_name'] ?? '') . ' ' . (string)($_SESSION['last_name'] ?? ''));
$prefillEmail = (string)($_SESSION['email'] ?? '');
$status = (string)($_GET['status'] ?? '');
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/connect.php';

    $a = trim((string)($_POST['name'] ?? ''));
    $b = strtolower(trim((string)($_POST['email'] ?? '')));
    $c = trim((string)($_POST['model'] ?? ''));
    $d = trim((string)($_POST['message'] ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    if ($rating < 0 || $rating > 5) $rating = 0;

    if ($a === '' || $d === '' || !filter_var($b, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please provide a valid name, email and feedback message.';
    } else {
        $transactionStarted = false;
        try {
            app_ensure_notification_schema($conn);
            $ratingColumn = mysqli_query($conn, "SHOW COLUMNS FROM `feedback` LIKE 'rating'");
            $hasRatingColumn = $ratingColumn && mysqli_num_rows($ratingColumn) > 0;
            $conn->begin_transaction();
            $transactionStarted = true;
            if ($hasRatingColumn) {
                $stmt = $conn->prepare("INSERT INTO `feedback` (`name`, `email`, `model`, `rating`, `message`) VALUES (?, ?, ?, ?, ?)");
                $safeRating = ($rating >= 1 && $rating <= 5) ? $rating : null;
                $stmt->bind_param('sssis', $a, $b, $c, $safeRating, $d);
            } else {
                $stmt = $conn->prepare("INSERT INTO `feedback` (`name`, `email`, `model`, `message`) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $a, $b, $c, $d);
            }
            $stmt->execute();
            $stmt->close();
            $feedbackId = (int)$conn->insert_id;
            app_notify_admins(
                $conn,
                'feedback',
                'CUSTOMER_FEEDBACK',
                'New customer feedback',
                'A new feedback submission was received.',
                $feedbackId,
                'feedback',
                'feedback:' . $feedbackId
            );
            $conn->commit();
            $transactionStarted = false;
            header('Location: feedback.php?status=success');
            exit;
        } catch (Throwable $error) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            $formError = 'We could not save your feedback. Please check that MySQL is running and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Feedback | Beamers Legacy</title>
  <meta name="description" content="Share your experience with Beamers Legacy. Your feedback helps us improve the showroom, configurator and every BMW service we offer." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
  <style>
    /* Feedback page — built on the shared premium design system, matching
       the same layout language as the Test Drive page. */
    .feedback-page {
      padding: 160px 0 100px;
      min-height: 100vh;
      background-color: var(--bg-primary);
    }

    .feedback-holder {
      width: 90%;
      max-width: 680px;
      margin: 0 auto;
    }

    .feedback-eyebrow {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3em;
      color: var(--bmw-blue);
      margin-bottom: 16px;
    }

    .feedback-holder h1 {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 12px;
    }

    .feedback-holder > p.lead {
      color: var(--text-muted);
      font-size: 1rem;
      margin-bottom: 40px;
      max-width: 60ch;
    }

    .form-notice {
      margin: 0 0 30px;
      padding: 14px 18px;
      border: 1px solid var(--border-color);
      background: var(--bg-secondary);
      color: var(--text-secondary);
      font-size: 0.9rem;
    }
    .form-notice.success { border-color: #2d7a4d; }
    .form-notice.error { border-color: #8a3d3d; color: #ffb4b4; }

    .stars {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }
    .star {
      background: transparent;
      border: none;
      padding: 4px;
      cursor: pointer;
      display: inline-flex;
      transition: transform 0.15s ease;
    }
    .star:hover, .star:focus-visible { transform: scale(1.1); }
    .star svg { width: 32px; height: 32px; fill: #444; transition: fill 0.18s ease; }
    .star.active svg { fill: var(--bmw-blue); }

    .feedback-holder footer {
      text-align: center;
      font-size: 0.75rem;
      color: var(--text-dark);
      margin-top: 30px;
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/navbar.php'; ?>

  <main class="feedback-page">
    <div class="feedback-holder">
      <p class="feedback-eyebrow">Beamers Legacy / Feedback</p>
      <h1>We value your feedback</h1>
      <p class="lead">Your thoughts help us improve and create a better BMW experience for you.</p>

      <?php if ($status === 'success'): ?><div class="form-notice success">Thank you. Your feedback was submitted successfully.</div><?php endif; ?>
      <?php if ($formError !== ''): ?><div class="form-notice error"><?= e($formError) ?></div><?php endif; ?>

      <form action="feedback.php" method="POST">
        <input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">

        <div class="form-group-premium">
          <label for="name">Full Name*</label>
          <input id="name" name="name" type="text" value="<?= e($prefillName) ?>" required>
        </div>

        <div class="form-group-premium">
          <label for="email">Email*</label>
          <input id="email" name="email" type="email" value="<?= e($prefillEmail) ?>" required>
        </div>

        <div class="form-group-premium">
          <label for="model">Which BMW Model did you experience?</label>
          <select id="model" name="model">
            <option value="">Select Model</option>
            <option>BMW 3 Series</option>
            <option>BMW 5 Series</option>
            <option>BMW X Series</option>
            <option>BMW i4</option>
            <option>BMW X7</option>
            <option>BMW M4</option>
            <option>BMW M8</option>
          </select>
        </div>

        <div class="form-group-premium">
          <label>Rate Your Experience</label>
          <div class="stars" role="radiogroup" aria-label="Star rating">
            <button type="button" class="star" data-value="1"><svg viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.79 1.402 8.172L12 18.896l-7.336 3.877 1.402-8.172L.132 9.211l8.2-1.193z"/></svg></button>
            <button type="button" class="star" data-value="2"><svg viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.79 1.402 8.172L12 18.896l-7.336 3.877 1.402-8.172L.132 9.211l8.2-1.193z"/></svg></button>
            <button type="button" class="star" data-value="3"><svg viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.79 1.402 8.172L12 18.896l-7.336 3.877 1.402-8.172L.132 9.211l8.2-1.193z"/></svg></button>
            <button type="button" class="star" data-value="4"><svg viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.79 1.402 8.172L12 18.896l-7.336 3.877 1.402-8.172L.132 9.211l8.2-1.193z"/></svg></button>
            <button type="button" class="star" data-value="5"><svg viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.79 1.402 8.172L12 18.896l-7.336 3.877 1.402-8.172L.132 9.211l8.2-1.193z"/></svg></button>
          </div>
          <input type="hidden" id="rating-value" name="rating" value="0">
        </div>

        <div class="form-group-premium">
          <label for="message">Your Feedback*</label>
          <textarea id="message" name="message" required placeholder="Tell us what you loved or what can be improved..."></textarea>
        </div>

        <button type="submit" class="btn-primary">Submit Feedback</button>
      </form>

      <footer>
        <p>Thank you for sharing your feedback with Beamers Legacy.</p>
        <p>&copy; Beamers Legacy 2025</p>
      </footer>
    </div>
  </main>

  <script>
    // Star rating behavior
    (function(){
      const stars = document.querySelectorAll('.star');
      const ratingInput = document.getElementById('rating-value');
      let current = parseInt(ratingInput.value) || 0;

      function highlight(rating){
        stars.forEach((s,i)=>s.classList.toggle('active', i<rating));
      }

      highlight(current);

      stars.forEach((s,idx)=>{
        s.addEventListener('click', ()=>{
          current = idx+1;
          ratingInput.value = current;
          highlight(current);
        });
        s.addEventListener('mouseover', ()=>highlight(idx+1));
        s.addEventListener('mouseout', ()=>highlight(current));
      });
    })();
  </script>
<script src="../js/premium.js"></script>
</body>
</html>
