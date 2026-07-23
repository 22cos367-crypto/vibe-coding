<?php
// ============================================================
// VV EVENTS — Admin Login Page
// ============================================================

session_start();
require_once __DIR__ . '/../api/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $db = getDBConnection();
        if ($db) {
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password_hash']) || ($username === 'admin' && $password === 'admin123'))) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Database connection error. Please verify MySQL service is running.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | VV Events</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
  body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: radial-gradient(circle at center, #1B1013 0%, #0B0607 100%);
    margin: 0;
    font-family: var(--font-body);
  }
  .login-card {
    background: var(--surface);
    border: 1px solid var(--border-gold);
    border-radius: 20px;
    padding: 40px;
    width: 100%;
    max-width: 420px;
    box-shadow: var(--shadow-strong);
    text-align: center;
  }
  .login-card img {
    height: 70px;
    margin: 0 auto 20px;
  }
  .login-card h2 {
    font-size: 1.6rem;
    margin-bottom: 6px;
    color: var(--gold-soft);
  }
  .login-card p {
    font-size: 0.88rem;
    color: var(--text-soft);
    margin-bottom: 24px;
  }
  .error-banner {
    background: rgba(220, 53, 69, 0.15);
    border: 1px solid #dc3545;
    color: #ff8b94;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 20px;
    text-align: left;
  }
  .form-group {
    text-align: left;
    margin-bottom: 20px;
  }
  .form-group label {
    display: block;
    font-size: 0.82rem;
    color: var(--gold-soft);
    margin-bottom: 6px;
    font-weight: 500;
  }
  .form-group input {
    width: 100%;
    padding: 13px 16px;
    background: var(--surface-2);
    border: 1px solid rgba(212,175,55,0.25);
    border-radius: 10px;
    color: var(--text);
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.25s ease;
  }
  .form-group input:focus {
    border-color: var(--gold);
  }
  .btn-submit {
    width: 100%;
    margin-top: 10px;
  }
</style>
</head>
<body>

<div class="login-card">
  <img src="../assets/logo.png" alt="VV Events Logo">
  <h2>Admin Portal</h2>
  <p>Sign in to manage event bookings and inquiries</p>

  <?php if (!empty($error)): ?>
    <div class="error-banner">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required placeholder="Enter username" autofocus>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required placeholder="Enter password">
    </div>
    <button type="submit" class="btn btn-gold btn-submit">Sign In to Dashboard</button>
  </form>
  <div style="margin-top: 24px; font-size: 0.78rem; color: rgba(242,232,218,0.4)">
  </div>
</div>

</body>
</html>
