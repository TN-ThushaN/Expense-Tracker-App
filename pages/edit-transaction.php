<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$categories = ['Food','Transport','Bills','Education','Shopping','Health','Entertainment','Other'];
$errors = [];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit; }

// Fetch — make sure it belongs to this user
$stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$txn = $stmt->fetch();
if (!$txn) { header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $amount   = $_POST['amount'] ?? '';
    $type     = $_POST['type'] ?? '';
    $category = $_POST['category'] ?? '';
    $date     = $_POST['date'] ?? '';
    $note     = trim($_POST['note'] ?? '');

    if (!$title)                                $errors[] = 'Title is required.';
    if (!is_numeric($amount) || $amount <= 0)   $errors[] = 'Enter a valid amount.';
    if (!in_array($type, ['income','expense']))  $errors[] = 'Select a type.';
    if (!in_array($category, $categories))      $errors[] = 'Select a category.';
    if (!$date)                                 $errors[] = 'Date is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('
            UPDATE transactions
            SET type=?, category=?, amount=?, title=?, note=?, date=?
            WHERE id=? AND user_id=?
        ');
        $stmt->execute([
            $type, $category, $amount, $title, $note, $date,
            $id, $_SESSION['user_id']
        ]);
        header('Location: dashboard.php?updated=1'); exit;
    }

    // Re-populate from POST on error
    $txn = array_merge($txn, compact('title','amount','type','category','date','note'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Transaction — SpendSmart</title>
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
        <h2>Edit transaction</h2>
        <a href="dashboard.php" class="btn-ghost">← Back</a>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">

        <div class="type-toggle">
          <label class="type-opt <?= $txn['type'] === 'expense' ? 'active-expense' : '' ?>">
            <input type="radio" name="type" value="expense"
              <?= $txn['type'] === 'expense' ? 'checked' : '' ?>> Expense
          </label>
          <label class="type-opt <?= $txn['type'] === 'income' ? 'active-income' : '' ?>">
            <input type="radio" name="type" value="income"
              <?= $txn['type'] === 'income' ? 'checked' : '' ?>> Income
          </label>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title"
                   value="<?= htmlspecialchars($txn['title']) ?>" required>
          </div>
          <div class="form-group">
            <label for="amount">Amount (LKR)</label>
            <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                   value="<?= htmlspecialchars($txn['amount']) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>"
                  <?= $txn['category'] === $cat ? 'selected' : '' ?>>
                  <?= $cat ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date"
                   value="<?= htmlspecialchars($txn['date']) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="note">Note <span class="optional">(optional)</span></label>
          <textarea id="note" name="note" rows="2"><?= htmlspecialchars($txn['note'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
          <a href="dashboard.php" class="btn-ghost">Cancel</a>
          <button type="submit" class="btn-primary">Update transaction</button>
        </div>

      </form>
    </div>
  </div>

  <script src="../js/form.js"></script>
  <script src="../js/darkmode.js"></script>
</body>
</html>
