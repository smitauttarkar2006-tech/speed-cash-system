<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$summaryQuery = $conn->prepare("SELECT
    COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END), 0) AS total_income,
    COALESCE(SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END), 0) AS total_expense,
    COUNT(*) AS total_transactions
    FROM transactions WHERE user_id = ?");
$summaryQuery->bind_param('i', $userId);
$summaryQuery->execute();
$summary = $summaryQuery->get_result()->fetch_assoc();
$balance = (float) $summary['total_income'] - (float) $summary['total_expense'];

$recentQuery = $conn->prepare('SELECT id, type, category, amount, transaction_date, description FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 5');
$recentQuery->bind_param('i', $userId);
$recentQuery->execute();
$recentTransactions = $recentQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading heading-with-action">
    <div><p class="eyebrow">YOUR FINANCES</p><h1>Hello, <?= h($_SESSION['user_name'] ?? 'there') ?></h1><p>Here is your personal cash overview.</p></div>
    <div class="button-row"><a class="button" href="add_income.php">＋ Add income</a><a class="button button-dark" href="add_expense.php">− Add expense</a></div>
</section>
<section class="summary-grid" aria-label="Financial summary">
    <article class="summary-card card-income"><span>Total income</span><strong>₹<?= number_format((float) $summary['total_income'], 2) ?></strong><small>All recorded income</small></article>
    <article class="summary-card card-expense"><span>Total expenses</span><strong>₹<?= number_format((float) $summary['total_expense'], 2) ?></strong><small>All recorded expenses</small></article>
    <article class="summary-card card-balance"><span>Current balance</span><strong>₹<?= number_format($balance, 2) ?></strong><small>Income minus expenses</small></article>
    <article class="summary-card card-count"><span>Transactions</span><strong><?= (int) $summary['total_transactions'] ?></strong><small>Entries in your account</small></article>
</section>
<section class="content-section">
    <div class="section-heading"><div><p class="eyebrow">LATEST ACTIVITY</p><h2>Recent transactions</h2></div><a class="text-link" href="transactions.php">View all →</a></div>
    <?php if (!$recentTransactions): ?>
        <div class="empty-state"><h3>Your activity starts here</h3><p>Add your first income or expense to see your balance take shape.</p><a class="button" href="add_income.php">Add a transaction</a></div>
    <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th class="align-right">Amount</th></tr></thead><tbody>
        <?php foreach ($recentTransactions as $transaction): ?>
            <tr><td><?= h(date('d-m-Y', strtotime($transaction['transaction_date']))) ?></td><td><span class="type-badge type-<?= strtolower(h($transaction['type'])) ?>"><?= h($transaction['type']) ?></span></td><td><?= h($transaction['category']) ?></td><td class="description-cell"><?= h($transaction['description'] ?: '—') ?></td><td class="align-right amount-<?= strtolower(h($transaction['type'])) ?>"><?= $transaction['type'] === 'Income' ? '+' : '−' ?>₹<?= number_format((float) $transaction['amount'], 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
