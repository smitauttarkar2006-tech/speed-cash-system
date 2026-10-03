<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$search = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$filterDate = $_GET['date'] ?? '';
$month = $_GET['month'] ?? '';
if (!in_array($type, ['', 'Income', 'Expense'], true)) {
    $type = '';
}
if ($filterDate !== '' && !valid_transaction_date($filterDate)) {
    $filterDate = '';
}
if ($month !== '' && (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month))) {
    $month = '';
}
$search = mb_substr($search, 0, 100);

$sql = "SELECT id, type, category, amount, transaction_date, description
        FROM transactions
        WHERE user_id = ?
          AND (? = '' OR type = ?)
          AND (? = '' OR transaction_date = ?)
          AND (? = '' OR DATE_FORMAT(transaction_date, '%Y-%m') = ?)
          AND (? = '' OR category LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%'))
        ORDER BY transaction_date DESC, id DESC";
$statement = $conn->prepare($sql);
$statement->bind_param('isssssssss', $userId, $type, $type, $filterDate, $filterDate, $month, $month, $search, $search, $search);
$statement->execute();
$transactions = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Transactions';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><p class="eyebrow">YOUR RECORDS</p><h1>Transactions</h1><p>Search and review income and expenses by category, type, and date.</p></section>
<form class="filter-panel" method="get" action="transactions.php">
    <label>Search<input type="search" name="q" value="<?= h($search) ?>" placeholder="Category or description"></label>
    <label>Type<select name="type"><option value="">All types</option><option value="Income" <?= $type === 'Income' ? 'selected' : '' ?>>Income</option><option value="Expense" <?= $type === 'Expense' ? 'selected' : '' ?>>Expense</option></select></label>
    <label>Exact date<input type="date" name="date" value="<?= h($filterDate) ?>"></label>
    <label>Month<input type="month" name="month" value="<?= h($month) ?>"></label>
    <div class="filter-actions"><button class="button button-small" type="submit">Apply filters</button><a class="button button-outline button-small" href="transactions.php">Reset</a></div>
</form>
<section class="content-section">
    <div class="section-heading"><h2>All transactions <span class="result-count"><?= count($transactions) ?></span></h2></div>
    <?php if (!$transactions): ?>
        <div class="empty-state"><h3>No matching transactions</h3><p>Try changing your filters, or add an income or expense.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th class="align-right">Amount</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($transactions as $transaction): ?>
        <tr>
            <td><?= h(date('d-m-Y', strtotime($transaction['transaction_date']))) ?></td>
            <td><span class="type-badge type-<?= strtolower(h($transaction['type'])) ?>"><?= h($transaction['type']) ?></span></td>
            <td><?= h($transaction['category']) ?></td>
            <td class="description-cell"><?= h($transaction['description'] ?: '—') ?></td>
            <td class="align-right amount-<?= strtolower(h($transaction['type'])) ?>"><?= $transaction['type'] === 'Income' ? '+' : '−' ?>₹<?= number_format((float) $transaction['amount'], 2) ?></td>
            <td><div class="table-actions"><a class="text-link" href="edit_transaction.php?id=<?= (int) $transaction['id'] ?>">Edit</a><form method="post" action="delete_transaction.php" data-confirm="Are you sure you want to delete this transaction?"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $transaction['id'] ?>"><button class="text-button danger-text" type="submit">Delete</button></form></div></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
