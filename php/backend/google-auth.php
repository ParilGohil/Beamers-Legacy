<?php
declare(strict_types=1);
$entry = ($_GET['entry'] ?? '') === 'register' ? 'register' : 'login';
$page = $entry === 'register' ? '../register.php' : '../loginform.php';
header('Location: ' . $page . '?auth=legacy-disabled');
exit;
