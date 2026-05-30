<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$categories = ['Food','Transport','Bills','Education','Shopping','Health','Entertainment','Other'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $amount   = $_POST['amount'] ?? '';
    $type     = $_POST['type'] ?? '';
    $category = $_POST['category'] ?? '';
    $date     = $_POST['date'] ?? '';
    $note     = trim($_POST['note'] ?? '');

    if (!$title)                              $errors[] = 'Title is required.';
    if (!is_numeric($amount) || $amount <= 0) $errors[] = 'Enter a valid amount.';
    if (!in_array($type, ['income','expense'])) $errors[] = 'Select a type.';
    if (!in_array($category, $categories))    $errors[] = 'Select a category.';
    if (!$date)                               $errors[] = 'Date is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('
            INSERT INTO transactions (user_id, type, category, amount, title, note, date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $_SESSION['user_id'], $type, $category,
            $amount, $title, $note, $date
        ]);
        header('Location: dashboard.php?added=1'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Transaction — SpendSmart</title>
  <link rel="stylesheet" href="../css/style.css">
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body>
  <?php include '../includes/navbar.php'; ?>

  <div class="page-wrapper">
    <div class="form-card">
      <div class="form-card-header">
        <h2>Add transaction</h2>
        <a href="dashboard.php" class="btn-ghost">← Back</a>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">

        <!-- Type toggle -->
        <div class="type-toggle">
          <label class="type-opt <?= ($_POST['type'] ?? 'expense') === 'expense' ? 'active-expense' : '' ?>">
            <input type="radio" name="type" value="expense"
              <?= ($_POST['type'] ?? 'expense') === 'expense' ? 'checked' : '' ?>>
            Expense
          </label>
          <label class="type-opt <?= ($_POST['type'] ?? '') === 'income' ? 'active-income' : '' ?>">
            <input type="radio" name="type" value="income"
              <?= ($_POST['type'] ?? '') === 'income' ? 'checked' : '' ?>>
            Income
          </label>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="e.g. Supermarket"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label for="amount">Amount (LKR)</label>
            <input type="number" id="amount" name="amount" min="0.01" step="0.01" placeholder="0.00"
                   value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
              <option value="" disabled <?= !isset($_POST['category']) ? 'selected' : '' ?>>Select category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>"
                  <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                  <?= $cat ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date"
                   value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="note">Note <span class="optional">(optional)</span></label>
          <textarea id="note" name="note" rows="2" placeholder="Add some details..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
          <a href="dashboard.php" class="btn-ghost">Cancel</a>
          <button type="submit" class="btn-primary">Add transaction</button>
        </div>

      </form>
    </div>
  </div>

  <script src="../js/form.js"></script>
  <script src="../js/darkmode.js"></script>
</body>
</html>