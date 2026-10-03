<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}
$periodStart = DateTime::createFromFormat('!Y-m-d', $selectedMonth . '-01');
$periodEnd = (clone $periodStart)->modify('+1 month');
$startDate = $periodStart->format('Y-m-d');
$endDate = $periodEnd->format('Y-m-d');

$overallQuery = $conn->prepare("SELECT
    COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END), 0) AS income,
    COALESCE(SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END), 0) AS expense
    FROM transactions WHERE user_id = ?");
$overallQuery->bind_param('i', $userId);
$overallQuery->execute();
$overall = $overallQuery->get_result()->fetch_assoc();

$monthQuery = $conn->prepare("SELECT
    COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END), 0) AS income,
    COALESCE(SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END), 0) AS expense
    FROM transactions WHERE user_id = ? AND transaction_date >= ? AND transaction_date < ?");
$monthQuery->bind_param('iss', $userId, $startDate, $endDate);
$monthQuery->execute();
$monthly = $monthQuery->get_result()->fetch_assoc();

$categoryQuery = $conn->prepare("SELECT category, SUM(amount) AS total FROM transactions
    WHERE user_id = ? AND type = 'Expense' AND transaction_date >= ? AND transaction_date < ?
    GROUP BY category ORDER BY total DESC");
$categoryQuery->bind_param('iss', $userId, $startDate, $endDate);
$categoryQuery->execute();
$categoryExpenses = $categoryQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$largestCategoryExpense = 0.0;
foreach ($categoryExpenses as $row) {
    $largestCategoryExpense = max($largestCategoryExpense, (float) $row['total']);
}

$pageTitle = 'Reports';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading heading-with-action">
    <div><p class="eyebrow">YOUR MONEY, OVER TIME</p><h1>Reports</h1><p>Review your overall totals and the selected month's activity.</p></div>
    <form class="month-picker" method="get" action="reports.php"><label for="report-month">Report month</label><input id="report-month" type="month" name="month" value="<?= h($selectedMonth) ?>"><button class="button button-small" type="submit">View report</button></form>
</section>
<section class="report-period"><span>MONTHLY REPORT</span><strong><?= h($periodStart->format('F Y')) ?></strong></section>
<section class="summary-grid summary-grid-three">
    <article class="summary-card card-income"><span>All-time income</span><strong>₹<?= number_format((float) $overall['income'], 2) ?></strong></article>
    <article class="summary-card card-expense"><span>All-time expenses</span><strong>₹<?= number_format((float) $overall['expense'], 2) ?></strong></article>
    <article class="summary-card card-balance"><span>Current balance</span><strong>₹<?= number_format((float) $overall['income'] - (float) $overall['expense'], 2) ?></strong></article>
</section>
<section class="summary-grid summary-grid-three monthly-summary">
    <article class="summary-card card-income"><span><?= h($periodStart->format('F')) ?> income</span><strong>₹<?= number_format((float) $monthly['income'], 2) ?></strong></article>
    <article class="summary-card card-expense"><span><?= h($periodStart->format('F')) ?> expenses</span><strong>₹<?= number_format((float) $monthly['expense'], 2) ?></strong></article>
    <article class="summary-card card-balance"><span>Monthly balance</span><strong>₹<?= number_format((float) $monthly['income'] - (float) $monthly['expense'], 2) ?></strong></article>
</section>
<section class="content-section report-categories">
    <div class="section-heading"><div><p class="eyebrow">SPENDING BREAKDOWN</p><h2>Expenses by category</h2></div><span class="muted-text"><?= h($periodStart->format('F Y')) ?></span></div>
    <?php if (!$categoryExpenses): ?><div class="empty-state compact-empty"><p>No expenses were recorded for this month.</p></div><?php else: ?>
        <div class="category-list">
        <?php foreach ($categoryExpenses as $row): $width = $largestCategoryExpense > 0 ? ((float) $row['total'] / $largestCategoryExpense) * 100 : 0; ?>
            <div class="category-row"><div class="category-row-label"><strong><?= h($row['category']) ?></strong><span>₹<?= number_format((float) $row['total'], 2) ?></span></div><div class="bar-track"><span style="width: <?= h(number_format($width, 2, '.', '')) ?>%"></span></div></div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
