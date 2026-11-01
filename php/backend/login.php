<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
start_app_session();

header('Location: ../loginform.php?status=firebase-required');
exit;
