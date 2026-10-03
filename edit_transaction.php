<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$transactionId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$lookup = $conn->prepare('SELECT id, type, category, amount, transaction_date, description FROM transactions WHERE id = ? AND user_id = ?');
$lookup->bind_param('ii', $transactionId, $userId);
$lookup->execute();
$transaction = $lookup->get_result()->fetch_assoc();
if (!$transaction) {
    http_response_code(404);
    exit('Transaction not found.');
}

$errors = [];
$amount = (string) $transaction['amount'];
$category = $transaction['category'];
$transactionDate = $transaction['transaction_date'];
$description = $transaction['description'];
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
        $errors[] = 'Choose a valid category.';
    }
    if (!valid_transaction_date($transactionDate)) {
        $errors[] = 'Enter a valid transaction date.';
    }
    if (strlen($description) > 500) {
        $errors[] = 'Description must be 500 characters or fewer.';
    }

    if (!$errors) {
        $amountValue = (float) $amount;
        $update = $conn->prepare('UPDATE transactions SET category = ?, amount = ?, transaction_date = ?, description = ? WHERE id = ? AND user_id = ?');
        $update->bind_param('sdssii', $category, $amountValue, $transactionDate, $description, $transactionId, $userId);
        $update->execute();
        set_flash('success', 'Transaction was updated.');
        header('Location: transactions.php');
        exit;
    }
}

$pageTitle = 'Edit transaction';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><p class="eyebrow">UPDATE RECORD</p><h1>Edit <?= h(strtolower($transaction['type'])) ?></h1><p>Update the amount, category, date, or description.</p></section>
<form class="form-panel form-wide" method="post" action="edit_transaction.php?id=<?= $transactionId ?>">
    <h2><?= h($transaction['type']) ?> details</h2>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $transactionId ?>">
    <div class="form-grid">
        <label>Amount (₹)<input type="number" name="amount" min="0.01" max="9999999999.99" step="0.01" required value="<?= h($amount) ?>"></label>
        <label>Category<select name="category" required><?php foreach ($categories as $option): ?><option value="<?= h($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select></label>
        <label>Date<input type="date" name="transaction_date" required value="<?= h($transactionDate) ?>"></label>
        <label class="field-full">Description <span class="muted-text">(optional)</span><textarea name="description" rows="4" maxlength="500"><?= h($description) ?></textarea></label>
    </div>
    <div class="button-row"><button class="button" type="submit">Save changes</button><a class="button button-outline" href="transactions.php">Cancel</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
