<?php
session_start();

// Define credentials matching includes/db.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_tracker_db');

$status = '';
$error = '';

if (isset($_POST['install'])) {
    try {
        // 1. Connect to MySQL without database first
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // 2. Read schema.sql content
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql file not found in the root directory.");
        }
        $sql = file_get_contents($schemaFile);

        // 3. Execute the SQL schema queries
        // Using exec since schema.sql might contain multiple statements
        $pdo->exec($sql);

        $status = 'Database and tables created successfully!';
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Setup — SpendSmart</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body class="auth-page">
  <div class="auth-card" style="max-width: 480px;">
    <div class="auth-logo">💰 SpendSmart</div>
    <h1 class="auth-title">Database Installer</h1>
    
    <p style="margin-bottom: 20px; font-size: 14px; color: var(--muted);">
      This script will automatically set up your local MySQL database and create the required tables (`users`, `transactions`, `budget`).
    </p>

    <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 24px; font-size: 13px;">
      <div style="margin-bottom: 6px;"><strong>Host:</strong> <code><?= DB_HOST ?></code></div>
      <div style="margin-bottom: 6px;"><strong>User:</strong> <code><?= DB_USER ?></code></div>
      <div style="margin-bottom: 6px;"><strong>Database:</strong> <code><?= DB_NAME ?></code></div>
      <div><strong>Password:</strong> <code><?= DB_PASS === '' ? '*(None)*' : '********' ?></code></div>
    </div>

    <?php if ($status): ?>
      <div class="alert alert-success">
        <p>🎉 <strong>Success!</strong> <?= htmlspecialchars($status) ?></p>
      </div>
      <a href="pages/signup.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; line-height: 22px;">Create Account &rarr;</a>
    <?php elseif ($error): ?>
      <div class="alert alert-error">
        <p>❌ <?= htmlspecialchars($error) ?></p>
      </div>
      <form method="POST" action="">
        <button type="submit" name="install" class="btn-primary">Retry Installation</button>
      </form>
    <?php else: ?>
      <form method="POST" action="">
        <button type="submit" name="install" class="btn-primary" style="font-size: 15px; padding: 12px;">Start Setup</button>
      </form>
    <?php endif; ?>

    <p class="auth-switch" style="margin-top: 24px;">
      <a href="pages/login.php" style="color: var(--muted); font-size: 12px;">Back to login</a>
    </p>
  </div>
  <script src="js/darkmode.js"></script>
</body>
</html>
