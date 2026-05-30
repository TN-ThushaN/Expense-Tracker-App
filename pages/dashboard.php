<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$uid = $_SESSION['user_id'];

// ── Month filter ──────────────────────────────────────────
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
[$year, $mon] = explode('-', $month);

// ── 1. Totals (income, expense, balance) ─────────────────
$stmt = $pdo->prepare('
    SELECT type, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
      AND YEAR(date) = ? AND MONTH(date) = ?
    GROUP BY type
');
$stmt->execute([$uid, $year, $mon]);
$totals = ['income' => 0, 'expense' => 0];
foreach ($stmt->fetchAll() as $row) {
    $totals[$row['type']] = (float)$row['total'];
}
$balance = $totals['income'] - $totals['expense'];

// ── 2. Spending by category (for pie chart) ───────────────
$stmt = $pdo->prepare('
    SELECT category, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ? AND type = "expense"
      AND YEAR(date) = ? AND MONTH(date) = ?
    GROUP BY category
    ORDER BY total DESC
');
$stmt->execute([$uid, $year, $mon]);
$byCategory = $stmt->fetchAll();

// ── 3. Monthly comparison — last 6 months (bar chart) ─────
$stmt = $pdo->prepare('
    SELECT
        DATE_FORMAT(date, "%Y-%m")  AS month,
        type,
        SUM(amount)                 AS total
    FROM transactions
    WHERE user_id = ?
      AND date >= DATE_SUB(LAST_DAY(NOW()), INTERVAL 5 MONTH)
    GROUP BY month, type
    ORDER BY month ASC
');
$stmt->execute([$uid]);
$monthly = [];
foreach ($stmt->fetchAll() as $row) {
    $monthly[$row['month']][$row['type']] = (float)$row['total'];
}

// Build labels + dataset arrays for Chart.js
$monthLabels  = [];
$monthIncome  = [];
$monthExpense = [];
for ($i = 5; $i >= 0; $i--) {
    $key   = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $monthLabels[]  = $label;
    $monthIncome[]  = $monthly[$key]['income']  ?? 0;
    $monthExpense[] = $monthly[$key]['expense'] ?? 0;
}

// ── 4. Recent transactions ────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$filterType = $_GET['type'] ?? 'all';
$filterCat  = $_GET['category'] ?? 'all';

$where  = ['t.user_id = ?'];
$params = [$uid];

if ($filterType !== 'all') {
    $where[]  = 't.type = ?';
    $params[] = $filterType;
}
if ($filterCat !== 'all') {
    $where[]  = 't.category = ?';
    $params[] = $filterCat;
}
if ($search) {
    $where[]  = '(t.title LIKE ? OR t.note LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT * FROM transactions t
    WHERE $whereSQL
    ORDER BY date DESC, created_at DESC
    LIMIT 20
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// ── 5. All categories (for filter dropdown) ───────────────
$catStmt = $pdo->prepare('
    SELECT DISTINCT category FROM transactions
    WHERE user_id = ? ORDER BY category
');
$catStmt->execute([$uid]);
$allCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Category icons map
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

// Flash messages
$flash = '';
if (isset($_GET['added']))   $flash = 'Transaction added.';
if (isset($_GET['updated'])) $flash = 'Transaction updated.';
if (isset($_GET['deleted'])) $flash = 'Transaction deleted.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — SpendSmart</title>
  <link rel="stylesheet" href="../css/style.css">
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="page-wrapper wide">

  <?php if ($flash): ?>
    <div class="alert alert-success" style="margin-bottom:20px">
      <p><?= htmlspecialchars($flash) ?></p>
    </div>
  <?php endif; ?>

  <!-- ── Header row ── -->
  <div class="dash-header">
    <div>
      <h1 class="dash-title">Dashboard</h1>
      <p class="dash-sub">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <!-- Month picker -->
      <form method="GET" action="">
        <input type="month" name="month" value="<?= $month ?>"
               onchange="this.form.submit()" class="month-input">
      </form>
      <a href="add-transaction.php" class="btn-primary">+ Add transaction</a>
    </div>
  </div>

  <!-- ── Stat cards ── -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-label">Total income</div>
      <div class="stat-value income">LKR <?= number_format($totals['income'], 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total expenses</div>
      <div class="stat-value expense">LKR <?= number_format($totals['expense'], 2) ?></div>
    </div>
    <div class="stat-card <?= $balance < 0 ? 'negative' : '' ?>">
      <div class="stat-label">Balance</div>
      <div class="stat-value balance <?= $balance < 0 ? 'expense' : 'income' ?>">
        LKR <?= number_format(abs($balance), 2) ?>
        <?= $balance < 0 ? '<span class="stat-tag danger">Overspent</span>' : '<span class="stat-tag good">On track</span>' ?>
      </div>
    </div>
  </div>

  <!-- ── Charts ── -->
  <div class="chart-grid">
    <div class="card">
      <div class="card-title">Spending by category</div>
      <?php if ($byCategory): ?>
        <div class="chart-wrap"><canvas id="pieChart"></canvas></div>
      <?php else: ?>
        <div class="empty-chart">No expenses this month</div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-title">6-month comparison</div>
      <div class="chart-wrap"><canvas id="barChart"></canvas></div>
    </div>
  </div>

  <!-- ── Transactions list ── -->
  <div class="card" style="margin-top:20px">
    <div class="card-title">Transactions</div>

    <!-- Filters -->
    <form method="GET" action="" class="filter-row">
      <input type="hidden" name="month" value="<?= $month ?>">
      <input type="text" name="search" placeholder="Search transactions..."
             value="<?= htmlspecialchars($search) ?>" class="filter-search">
      <select name="type" onchange="this.form.submit()" class="filter-select">
        <option value="all"     <?= $filterType === 'all'     ? 'selected' : '' ?>>All types</option>
        <option value="income"  <?= $filterType === 'income'  ? 'selected' : '' ?>>Income</option>
        <option value="expense" <?= $filterType === 'expense' ? 'selected' : '' ?>>Expense</option>
      </select>
      <select name="category" onchange="this.form.submit()" class="filter-select">
        <option value="all">All categories</option>
        <?php foreach ($allCategories as $cat): ?>
          <option value="<?= $cat ?>" <?= $filterCat === $cat ? 'selected' : '' ?>>
            <?= $catIcons[$cat] ?? '' ?> <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-ghost">Search</button>
      <a href="dashboard.php?month=<?= $month ?>" class="btn-ghost">Clear</a>
    </form>

    <!-- Table -->
    <?php if ($transactions): ?>
      <div class="txn-table-wrap">
        <table class="txn-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Title</th>
              <th>Category</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td class="muted"><?= date('d M Y', strtotime($t['date'])) ?></td>
              <td>
                <div class="txn-title"><?= htmlspecialchars($t['title']) ?></div>
                <?php if ($t['note']): ?>
                  <div class="txn-note"><?= htmlspecialchars($t['note']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="cat-badge">
                  <?= $catIcons[$t['category']] ?? '' ?>
                  <?= htmlspecialchars($t['category']) ?>
                </span>
              </td>
              <td>
                <span class="type-badge <?= $t['type'] ?>">
                  <?= ucfirst($t['type']) ?>
                </span>
              </td>
              <td class="amount <?= $t['type'] ?>">
                <?= $t['type'] === 'income' ? '+' : '-' ?>
                LKR <?= number_format($t['amount'], 2) ?>
              </td>
              <td class="actions">
                <a href="edit-transaction.php?id=<?= $t['id'] ?>" class="btn-action edit">Edit</a>
                <a href="delete-transaction.php?id=<?= $t['id'] ?>"
                   class="btn-action delete"
                   onclick="return confirm('Delete this transaction?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <p>No transactions found.</p>
        <a href="add-transaction.php" class="btn-primary" style="display:inline-block;margin-top:12px">
          Add your first transaction
        </a>
      </div>
    <?php endif; ?>

  </div>
</div><!-- end page-wrapper -->

<!-- Pass PHP data to JS -->
<script>
const pieData = {
  labels: <?= json_encode(array_column($byCategory, 'category')) ?>,
  values: <?= json_encode(array_map(fn($r) => (float)$r['total'], $byCategory)) ?>
};
const barData = {
  labels:   <?= json_encode($monthLabels) ?>,
  income:   <?= json_encode($monthIncome) ?>,
  expense:  <?= json_encode($monthExpense) ?>
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="../js/charts.js"></script>
<script src="../js/darkmode.js"></script>
</body>
</html>