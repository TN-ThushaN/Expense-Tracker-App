<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$uid        = $_SESSION['user_id'];
$month      = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
[$year, $mon] = explode('-', $month);

$categories = ['Food','Transport','Bills','Education','Shopping','Health','Entertainment','Other'];

$errors  = [];
$success = '';

// ── Save / update a budget limit ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($categories as $cat) {
        $key = 'limit_' . strtolower($cat);
        if (!isset($_POST[$key])) continue;
        $limit = $_POST[$key];
        if ($limit === '' || $limit === null) continue;
        if (!is_numeric($limit) || $limit < 0) {
            $errors[] = "Invalid amount for $cat.";
            continue;
        }
        // UPSERT — insert or update on duplicate (user_id, month, category)
        $stmt = $pdo->prepare('
            INSERT INTO budget (user_id, month, category, limit_amount)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount)
        ');
        $stmt->execute([$uid, $month, $cat, (float)$limit]);
    }
    if (!$errors) $success = 'Budget limits saved.';
}

// ── Fetch saved limits for this month ────────────────────
$stmt = $pdo->prepare('
    SELECT category, limit_amount
    FROM budget
    WHERE user_id = ? AND month = ?
');
$stmt->execute([$uid, $month]);
$limits = [];
foreach ($stmt->fetchAll() as $row) {
    $limits[$row['category']] = (float)$row['limit_amount'];
}

// ── Fetch actual spending per category this month ─────────
$stmt = $pdo->prepare('
    SELECT category, SUM(amount) AS spent
    FROM transactions
    WHERE user_id = ? AND type = "expense"
      AND YEAR(date) = ? AND MONTH(date) = ?
    GROUP BY category
');
$stmt->execute([$uid, $year, $mon]);
$spent = [];
foreach ($stmt->fetchAll() as $row) {
    $spent[$row['category']] = (float)$row['spent'];
}

// ── Build summary rows ────────────────────────────────────
$rows        = [];
$totalLimit  = 0;
$totalSpent  = 0;
$hasWarning  = false;

foreach ($categories as $cat) {
    $limit  = $limits[$cat] ?? 0;
    $actual = $spent[$cat]  ?? 0;
    $pct    = $limit > 0 ? min(($actual / $limit) * 100, 100) : 0;
    $over   = $limit > 0 && $actual > $limit;
    $warn   = $limit > 0 && $pct >= 80 && !$over;

    if ($over || $warn) $hasWarning = true;

    $rows[] = compact('cat','limit','actual','pct','over','warn');
    $totalLimit += $limit;
    $totalSpent += $actual;
}

$overallPct = $totalLimit > 0 ? min(($totalSpent / $totalLimit) * 100, 100) : 0;

$catIcons = [
    'Food'          => '🍔',
    'Transport'     => '🚗',
    'Bills'         => '⚡',
    'Education'     => '📚',
    'Shopping'      => '🛒',
    'Health'        => '💊',
    'Entertainment' => '🎬',
    'Other'         => '📦',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budget — SpendSmart</title>
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

  <div class="dash-header">
    <div>
      <h1 class="dash-title">Budget tracker</h1>
      <p class="dash-sub"><?= date('F Y', strtotime($month . '-01')) ?></p>
    </div>
    <form method="GET">
      <input type="month" name="month" value="<?= $month ?>"
             onchange="this.form.submit()" class="month-input">
    </form>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error" style="margin-bottom:20px">
      <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom:20px"><p><?= $success ?></p></div>
  <?php endif; ?>

  <?php if ($hasWarning): ?>
    <div class="alert alert-warning" style="margin-bottom:20px">
      <p>⚠️ One or more categories are near or over their budget limit this month.</p>
    </div>
  <?php endif; ?>

  <!-- ── Overall progress ── -->
  <?php if ($totalLimit > 0): ?>
  <div class="card" style="margin-bottom:20px">
    <div class="card-title">Overall budget — <?= date('F Y', strtotime($month.'-01')) ?></div>
    <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px">
      <span>LKR <?= number_format($totalSpent, 2) ?> spent of LKR <?= number_format($totalLimit, 2) ?></span>
      <span style="font-weight:600;color:<?= $overallPct >= 100 ? 'var(--danger)' : ($overallPct >= 80 ? '#b45309' : 'var(--primary)') ?>">
        <?= number_format($overallPct, 1) ?>%
      </span>
    </div>
    <div class="progress-bg">
      <div class="progress-fill <?= $overallPct >= 100 ? 'over' : ($overallPct >= 80 ? 'warn' : '') ?>"
           style="width:<?= $overallPct ?>%"></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="budget-grid">

    <!-- ── Left: set limits form ── -->
    <div class="card">
      <div class="card-title">Set monthly limits</div>
      <form method="POST" action="">
        <input type="hidden" name="month" value="<?= $month ?>">
        <?php foreach ($categories as $cat):
          $key = 'limit_' . strtolower($cat);
        ?>
          <div class="form-group">
            <label for="<?= $key ?>">
              <?= $catIcons[$cat] ?> <?= $cat ?>
            </label>
            <div class="input-prefix">
              <span class="prefix-label">LKR</span>
              <input type="number" id="<?= $key ?>" name="<?= $key ?>"
                     min="0" step="100" placeholder="No limit"
                     value="<?= $limits[$cat] ?? '' ?>">
            </div>
          </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary" style="margin-top:8px">Save limits</button>
      </form>
    </div>

    <!-- ── Right: progress bars ── -->
    <div class="card">
      <div class="card-title">Spending progress</div>

      <?php if (array_sum(array_column($rows, 'limit')) === 0.0): ?>
        <div class="empty-state" style="padding:32px 0">
          <p>Set budget limits on the left to see your progress here.</p>
        </div>
      <?php else: ?>

        <?php foreach ($rows as $r):
          if ($r['limit'] <= 0 && $r['actual'] <= 0) continue;
        ?>
          <div class="budget-row">
            <div class="budget-row-header">
              <span class="budget-cat">
                <?= $catIcons[$r['cat']] ?> <?= $r['cat'] ?>
                <?php if ($r['over']): ?>
                  <span class="badge over">Over budget</span>
                <?php elseif ($r['warn']): ?>
                  <span class="badge warn">Near limit</span>
                <?php endif; ?>
              </span>
              <span class="budget-amounts">
                <strong>LKR <?= number_format($r['actual'], 0) ?></strong>
                <?php if ($r['limit'] > 0): ?>
                  <span class="muted"> / <?= number_format($r['limit'], 0) ?></span>
                <?php else: ?>
                  <span class="muted"> (no limit set)</span>
                <?php endif; ?>
              </span>
            </div>
            <?php if ($r['limit'] > 0): ?>
              <div class="progress-bg">
                <div class="progress-fill <?= $r['over'] ? 'over' : ($r['warn'] ? 'warn' : '') ?>"
                     style="width:<?= number_format($r['pct'], 1) ?>%">
                </div>
              </div>
              <div class="progress-label">
                <?php if ($r['over']): ?>
                  <span class="text-danger">
                    Over by LKR <?= number_format($r['actual'] - $r['limit'], 2) ?>
                  </span>
                <?php elseif ($r['warn']): ?>
                  <span class="text-warn">
                    LKR <?= number_format($r['limit'] - $r['actual'], 2) ?> remaining
                  </span>
                <?php else: ?>
                  <span class="text-muted">
                    LKR <?= number_format($r['limit'] - $r['actual'], 2) ?> remaining
                  </span>
                <?php endif; ?>
                <span class="text-muted"><?= number_format($r['pct'], 1) ?>%</span>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

      <?php endif; ?>
    </div>

  </div>
</div>
<script src="../js/darkmode.js"></script>
</body>
</html>