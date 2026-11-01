<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

class FirebaseSyncException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode = 400)
    {
        parent::__construct($message);
    }
}

/*
 * Firebase Authentication is verified server-side using Firebase's
 * accounts:lookup endpoint. The endpoint validates the Firebase ID token
 * before we create a PHP session.
 */
function firebase_lookup_id_token(string $idToken): array
{
    if ($idToken === '') {
        throw new FirebaseSyncException('Firebase token is missing.');
    }

    if (FIREBASE_API_KEY === '' || str_contains(FIREBASE_API_KEY, 'PASTE_YOUR_')) {
        throw new FirebaseSyncException('Firebase is not configured correctly. Please contact the site administrator.', 503);
    }

    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' .
        rawurlencode(FIREBASE_API_KEY);

    $payload = json_encode(['idToken' => $idToken], JSON_THROW_ON_ERROR);

    $networkError = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $networkError = (string)curl_error($ch);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $lastError = error_get_last();
            $networkError = (string)($lastError['message'] ?? 'Firebase token verification request failed.');
        }
        $status = 200;
        if (isset($http_response_header[0]) &&
            preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
    }

    $data = is_string($body) ? json_decode($body, true) : null;

    if ($networkError !== '') {
        error_log('Firebase token verification transport failure: ' . $networkError);
        if (stripos($networkError, 'certificate') !== false || stripos($networkError, 'issuer') !== false) {
            throw new FirebaseSyncException('PHP cannot verify Firebase TLS certificates. Configure curl.cainfo with a trusted CA bundle in php.ini and restart Apache.', 503);
        }
        throw new FirebaseSyncException('We could not reach Firebase to verify your sign-in. Please try again.', 502);
    }
    if ($status < 200 || $status >= 300 || !is_array($data) || empty($data['users'][0])) {
        $firebaseError = (string)($data['error']['message'] ?? 'Unknown Firebase verification error');
        error_log('Firebase token verification failed (HTTP ' . $status . '): ' . $firebaseError);
        if (str_contains($firebaseError, 'API_KEY') || str_contains($firebaseError, 'PROJECT')) {
            throw new FirebaseSyncException('Firebase is not configured correctly. Please contact the site administrator.', 503);
        }
        throw new FirebaseSyncException('Firebase could not verify your session. Please sign in again.', 401);
    }

    return $data['users'][0];
}

function firebase_sync_customer(array $firebaseUser, array $profile = []): array
{
    $firebaseUid = trim((string)($firebaseUser['localId'] ?? ''));
    $email = strtolower(trim((string)($firebaseUser['email'] ?? '')));
    if ($firebaseUid === '' || strlen($firebaseUid) > 128) {
        throw new FirebaseSyncException('Firebase returned an invalid user identifier.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new FirebaseSyncException('Firebase must provide a valid email address for this account.');
    }

    $fullName = trim((string)($firebaseUser['displayName'] ?? ''));
    $nameParts = preg_split('/\s+/', $fullName, 2) ?: [];
    $firstName = trim((string)($nameParts[0] ?? ''));
    $lastName = trim((string)($nameParts[1] ?? ''));
    $firstName = $firstName !== '' ? $firstName : trim((string)($profile['first_name'] ?? ''));
    $lastName = $lastName !== '' ? $lastName : trim((string)($profile['last_name'] ?? ''));
    if ($firstName === '') {
        $firstName = 'BMW';
    }
    if ($lastName === '') {
        $lastName = 'Customer';
    }
    if (strlen($firstName) > 100 || strlen($lastName) > 100) {
        throw new FirebaseSyncException('The profile name is too long.');
    }

    $photoUrl = trim((string)($firebaseUser['photoUrl'] ?? ''));
    if (strtolower((string)parse_url($photoUrl, PHP_URL_SCHEME)) !== 'https') {
        $photoUrl = '';
    }
    $provider = 'password';
    foreach (($firebaseUser['providerUserInfo'] ?? []) as $providerInfo) {
        if (($providerInfo['providerId'] ?? '') === 'google.com') {
            $provider = 'google.com';
            break;
        }
    }

    $conn = db();
    app_ensure_customer_profile_schema($conn);
    app_ensure_notification_schema($conn);
    $transactionStarted = false;
    try {
        $conn->begin_transaction();
        $transactionStarted = true;

        $find = $conn->prepare('SELECT id, First_Name, Last_Name, `E-mail`, Firebase_UID, Photo_URL FROM register WHERE Firebase_UID = ? LIMIT 1 FOR UPDATE');
        $find->bind_param('s', $firebaseUid);
        $find->execute();
        $user = $find->get_result()->fetch_assoc();
        $find->close();

        $emailFind = $conn->prepare('SELECT id, First_Name, Last_Name, `E-mail`, Firebase_UID, Photo_URL FROM register WHERE LOWER(`E-mail`) = LOWER(?) LIMIT 1 FOR UPDATE');
        $emailFind->bind_param('s', $email);
        $emailFind->execute();
        $emailUser = $emailFind->get_result()->fetch_assoc();
        $emailFind->close();

        if ($user && $emailUser && (int)$user['id'] !== (int)$emailUser['id']) {
            throw new FirebaseSyncException('This Firebase account conflicts with another customer record. Please contact support.', 409);
        }
        if (!$user && $emailUser) {
            $existingUid = trim((string)($emailUser['Firebase_UID'] ?? ''));
            if ($existingUid !== '' && !hash_equals($existingUid, $firebaseUid)) {
                throw new FirebaseSyncException('This email address is already linked to another Firebase account.', 409);
            }
            $authorizedEmail = (string)($_SESSION['firebase_legacy_link_email'] ?? '');
            $authorizedUntil = (int)($_SESSION['firebase_legacy_link_expires'] ?? 0);
            $isVerifiedEmail = ($firebaseUser['emailVerified'] ?? false) === true;
            $hasLegacyAuthorization = $authorizedEmail !== ''
                && hash_equals(strtolower($authorizedEmail), $email)
                && $authorizedUntil >= time();
            if (!$isVerifiedEmail && !$hasLegacyAuthorization) {
                throw new FirebaseSyncException('A matching legacy account exists. Verify this email in Firebase or sign in with its current password to link it safely.', 409);
            }
            $user = $emailUser;
        }

        if ($user) {
            $firstName = $firstName !== 'BMW' ? $firstName : (string)$user['First_Name'];
            $lastName = $lastName !== 'Customer' ? $lastName : (string)$user['Last_Name'];
            if ($photoUrl === '') {
                $photoUrl = trim((string)($user['Photo_URL'] ?? ''));
            }
            $update = $conn->prepare('UPDATE register SET First_Name = ?, Last_Name = ?, `E-mail` = ?, Firebase_UID = ?, Photo_URL = ?, Auth_Provider = ? WHERE id = ?');
            $userId = (int)$user['id'];
            $update->bind_param('ssssssi', $firstName, $lastName, $email, $firebaseUid, $photoUrl, $provider, $userId);
            $update->execute();
            $update->close();
        } else {
            $placeholderPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $insert = $conn->prepare('INSERT INTO register (First_Name, Last_Name, `E-mail`, Password, Firebase_UID, Photo_URL, Auth_Provider) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $insert->bind_param('sssssss', $firstName, $lastName, $email, $placeholderPassword, $firebaseUid, $photoUrl, $provider);
            $insert->execute();
            $userId = (int)$insert->insert_id;
            $insert->close();
            app_notify_admins(
                $conn,
                'registrations',
                'NEW_CUSTOMER',
                'New Customer Registration',
                trim($firstName . ' ' . $lastName) . ' created a showroom account.',
                $userId,
                'customer',
                'new-customer:' . $userId
            );
        }

        $load = $conn->prepare('SELECT id, First_Name, Last_Name, `E-mail`, Firebase_UID, Photo_URL, Auth_Provider FROM register WHERE Firebase_UID = ? LIMIT 1');
        $load->bind_param('s', $firebaseUid);
        $load->execute();
        $savedUser = $load->get_result()->fetch_assoc();
        $load->close();
        if (!$savedUser) {
            throw new FirebaseSyncException('The customer account could not be confirmed after synchronization.', 500);
        }
        $conn->commit();
        $transactionStarted = false;
        unset($_SESSION['firebase_legacy_link_email'], $_SESSION['firebase_legacy_link_expires']);
        return $savedUser;
    } catch (Throwable $error) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('Firebase customer synchronization failed for UID ' . $firebaseUid . ': ' . $error->getMessage());
        if ($error instanceof mysqli_sql_exception) {
            if ((int)$error->getCode() === 1062) {
                throw new FirebaseSyncException('This Firebase account conflicts with an existing customer record. Please contact support.', 409);
            }
            throw new FirebaseSyncException('Your sign-in succeeded, but we could not save your customer profile. Please try again.', 503);
        }
        throw $error;
    }
}

function firebase_prepare_legacy_link(string $email, string $password): bool
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        return false;
    }
    $conn = db();
    app_ensure_customer_profile_schema($conn);
    $find = $conn->prepare('SELECT id, Password, Firebase_UID FROM register WHERE LOWER(`E-mail`) = LOWER(?) LIMIT 1');
    $find->bind_param('s', $email);
    $find->execute();
    $user = $find->get_result()->fetch_assoc();
    $find->close();
    if (!$user) {
        return false;
    }
    if (trim((string)($user['Firebase_UID'] ?? '')) !== '') {
        throw new FirebaseSyncException('This customer record is already linked to a Firebase account. Use its Firebase password or Google sign-in.', 409);
    }
    if (!app_verify_customer_password($conn, $user, $password)) {
        throw new FirebaseSyncException('The password does not match the existing customer account.', 401);
    }
    $_SESSION['firebase_legacy_link_email'] = $email;
    $_SESSION['firebase_legacy_link_expires'] = time() + 600;
    return true;
}
