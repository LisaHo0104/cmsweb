<?php

require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// No point showing the login form to someone who's already in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: menu.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = 'Please enter username and password.';
    } else {
        // Use a prepared statement so the username can never be injected
        $stmt = mysqli_prepare($conn, "SELECT id, password_hash, failed_attempts, last_failed_login, locked_until FROM admin_creds WHERE username = ?");
        if (!$stmt) {
            $errors[] = 'Database error. Please try again.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $id, $password_hash, $failed_attempts, $last_failed_login, $locked_until);
                mysqli_stmt_fetch($stmt);

                $now = new DateTime();
                $locked_until_dt = $locked_until ? new DateTime($locked_until) : null;

                if ($locked_until_dt && $locked_until_dt > $now) {
                    // Account is still within its lockout window
                    $errors[] = 'Account locked until ' . $locked_until_dt->format('Y-m-d H:i:s') . '. Please try later.';
                } elseif (password_verify($password, $password_hash)) {
                    // Good password — clear the failure counter and start the session
                    $update_stmt = mysqli_prepare($conn, "UPDATE admin_creds SET failed_attempts=0, locked_until=NULL WHERE id=?");
                    mysqli_stmt_bind_param($update_stmt, 'i', $id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);

                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $id;
                    $_SESSION['admin_username'] = $username;

                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);

                    header('Location: menu.php');
                    exit;
                } else {
                    // Wrong password — increment the counter and lock the account after 3 failures
                    // within a 15-minute window
                    $failed_attempts++;
                    $locked_until_update = null;

                    $last_failed_dt = $last_failed_login ? new DateTime($last_failed_login) : null;
                    $minutes_since_last_fail = $last_failed_dt ? ($now->getTimestamp() - $last_failed_dt->getTimestamp()) / 60 : null;

                    if ($failed_attempts >= 3 && $minutes_since_last_fail !== null && $minutes_since_last_fail <= 15) {
                        $locked_until_update = (new DateTime())->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s');
                        $errors[] = 'Account locked due to multiple failed login attempts. Try again after 10 minutes.';
                    } else {
                        $errors[] = 'Invalid username or password.';
                    }

                    $update_stmt = mysqli_prepare($conn, "UPDATE admin_creds SET failed_attempts=?, last_failed_login=NOW(), locked_until=? WHERE username=?");
                    mysqli_stmt_bind_param($update_stmt, 'iss', $failed_attempts, $locked_until_update, $username);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                // Username not found — give the same vague message to avoid user enumeration
                $errors[] = 'Invalid username or password.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Gong Cha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="adm-login-page">
    <div class="adm-login-box">

        <div class="adm-login-logo">
            <img src="../assets/images/gongcha-logo-new.svg" alt="Gong Cha" class="adm-login-logo__img">
        </div>

        <h2>Welcome back</h2>
        <p class="adm-login-sub">Sign in to the admin panel</p>

        <?php if ($errors): ?>
            <ul class="adm-error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="" method="post">
            <div class="adm-form-group">
                <label class="adm-label">Username</label>
                <div class="adm-input-wrap">
                    <i class="fas fa-user adm-input-icon"></i>
                    <input type="text" class="adm-input" name="username"
                           placeholder="Enter your username"
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           autocomplete="username">
                </div>
            </div>

            <div class="adm-form-group">
                <label class="adm-label">Password</label>
                <div class="adm-input-wrap">
                    <i class="fas fa-lock adm-input-icon"></i>
                    <input type="password" class="adm-input" name="password"
                           placeholder="Enter your password"
                           autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="adm-login-submit">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

    </div>
</div>

</body>
</html>
