<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$uid = $_SESSION['user_id'];

// Month validation
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
[$year, $mon] = explode('-', $month);

// Fetch transactions for the user and month
$stmt = $pdo->prepare('
    SELECT date, title, category, type, amount, note 
    FROM transactions 
    WHERE user_id = ? 
      AND YEAR(date) = ? 
      AND MONTH(date) = ?
    ORDER BY date DESC, created_at DESC
');
$stmt->execute([$uid, $year, $mon]);
$transactions = $stmt->fetchAll();

// Set appropriate download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=SpendSmart_Transactions_' . $month . '.csv');

// Open PHP output stream for writing
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header row
fputcsv($output, ['Date', 'Title', 'Category', 'Type', 'Amount (LKR)', 'Note']);

// Write data rows
foreach ($transactions as $t) {
    fputcsv($output, [
        date('Y-m-d', strtotime($t['date'])),
        $t['title'],
        $t['category'],
        ucfirst($t['type']),
        number_format($t['amount'], 2, '.', ''),
        $t['note'] ?? ''
    ]);
}

fclose($output);
exit;
