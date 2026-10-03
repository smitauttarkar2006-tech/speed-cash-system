<?php
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transactions.php');
    exit;
}
verify_csrf();
$transactionId = (int) ($_POST['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];
$delete = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
$delete->bind_param('ii', $transactionId, $userId);
$delete->execute();

if ($delete->affected_rows > 0) {
    set_flash('success', 'Transaction was deleted.');
} else {
    set_flash('error', 'Transaction was not found or could not be deleted.');
}
header('Location: transactions.php');
exit;
