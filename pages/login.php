<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php'); exit;
}
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);   // prevent session fixation
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log in — SpendSmart</title>
  <link rel="stylesheet" href="../css/style.css">
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">💰 SpendSmart</div>
    <h1 class="auth-title">Welcome back</h1>

    <?php if ($error): ?>
      <div class="alert alert-error"><p><?= htmlspecialchars($error) ?></p></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn-primary">Log in</button>
    </form>

    <p class="auth-switch">No account? <a href="signup.php">Sign up free</a></p>
  </div>
  <script src="../js/darkmode.js"></script>
</body>
</html>