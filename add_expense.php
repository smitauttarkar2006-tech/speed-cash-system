<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$errors = [];
$amount = '';
$category = '';
$transactionDate = date('Y-m-d');
$description = '';
$categories = transaction_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = trim($_POST['amount'] ?? '');
    $category = $_POST['category'] ?? '';
    $transactionDate = $_POST['transaction_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!is_numeric($amount) || (float) $amount <= 0 || (float) $amount > 9999999999.99) {
        $errors[] = 'Enter an amount greater than zero.';
    }
    if (!in_array($category, $categories, true)) {
        $errors[] = 'Choose a valid expense category.';
    }
    if (!valid_transaction_date($transactionDate)) {
        $errors[] = 'Enter a valid transaction date.';
    }
    if (strlen($description) > 500) {
        $errors[] = 'Description must be 500 characters or fewer.';
    }

    if (!$errors) {
        $userId = (int) $_SESSION['user_id'];
        $type = 'Expense';
        $amountValue = (float) $amount;
        $statement = $conn->prepare('INSERT INTO transactions (user_id, type, category, amount, transaction_date, description) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->bind_param('issdss', $userId, $type, $category, $amountValue, $transactionDate, $description);
        $statement->execute();
        set_flash('success', 'Expense was added successfully.');
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = 'Add expense';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><p class="eyebrow">MONEY OUT</p><h1>Add expense</h1><p>Record a purchase or bill with the date it happened.</p></section>
<form class="form-panel form-wide" method="post" action="add_expense.php">
    <h2>Expense details</h2>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div class="form-grid">
        <label>Amount (₹)<input type="number" name="amount" min="0.01" max="9999999999.99" step="0.01" required value="<?= h($amount) ?>" placeholder="0.00"></label>
        <label>Expense category<select name="category" required><option value="">Choose a category</option><?php foreach ($categories as $option): ?><option value="<?= h($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select></label>
        <label>Date spent<input type="date" name="transaction_date" required value="<?= h($transactionDate) ?>"></label>
        <label class="field-full">Description <span class="muted-text">(optional)</span><textarea name="description" rows="4" maxlength="500" placeholder="Add a short note"><?= h($description) ?></textarea></label>
    </div>
    <div class="button-row"><button class="button button-dark" type="submit">Save expense</button><a class="button button-outline" href="dashboard.php">Cancel</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
