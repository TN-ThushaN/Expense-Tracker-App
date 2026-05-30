<?php
$current = basename($_SERVER['PHP_SELF']);
$month   = $_GET['month'] ?? date('Y-m');
?>
<nav class="navbar">
  <div class="nav-brand">💰 SpendSmart</div>

  <div class="nav-links">
    <a href="dashboard.php"       class="<?= $current==='dashboard.php'       ?'active':'' ?>">Dashboard</a>
    <a href="add-transaction.php" class="<?= $current==='add-transaction.php' ?'active':'' ?>">+ Add</a>
    <a href="budget.php"          class="<?= $current==='budget.php'          ?'active':'' ?>">Budget</a>
    <a href="export.php?month=<?= $month ?>" class="nav-export">↓ Export CSV</a>
  </div>

  <div class="nav-user">
    <button class="dark-toggle" id="darkToggle" title="Toggle dark mode">🌙</button>
    <span>👋 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php" class="btn-logout">Log out</a>
  </div>
</nav>