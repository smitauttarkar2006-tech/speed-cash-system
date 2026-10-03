<?php
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Manage your money';
require __DIR__ . '/includes/header.php';
?>
<section class="welcome-panel">
    <div class="welcome-copy">
        <p class="eyebrow">PERSONAL MONEY, MADE CLEAR</p>
        <h1>Know where your money goes.</h1>
        <p>Keep income, expenses, and everyday spending together in one simple place.</p>
        <div class="button-row">
            <a class="button" href="register.php">Create your account</a>
            <a class="button button-outline" href="login.php">Sign in</a>
        </div>
    </div>
    <div class="welcome-visual" aria-label="A sample monthly balance">
        <span class="visual-label">THIS MONTH</span>
        <strong>₹ 42,850</strong>
        <span class="visual-caption">Available balance</span>
        <div class="visual-bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        <div class="visual-legend"><span><i class="income-dot"></i> Income</span><span><i class="expense-dot"></i> Expenses</span></div>
    </div>
</section>
<section class="feature-strip">
    <div><span class="feature-number">01</span><h2>Record</h2><p>Add income or an expense with its date and category.</p></div>
    <div><span class="feature-number">02</span><h2>Understand</h2><p>See your balance and recent activity at a glance.</p></div>
    <div><span class="feature-number">03</span><h2>Review</h2><p>Filter transactions and compare monthly totals.</p></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
