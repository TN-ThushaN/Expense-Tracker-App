<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    // The AND user_id check means users can never delete someone else's record
    $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
}

header('Location: dashboard.php?deleted=1');
exit;
