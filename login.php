<?php
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $statement = $conn->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $statement->bind_param('s', $email);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        set_flash('success', 'Welcome back, ' . $user['name'] . '.');
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Email or password is incorrect.';
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro"><p class="eyebrow">WELCOME BACK</p><h1>Your money, all in one view.</h1><p>Sign in to review your balance and recent activity.</p></div>
    <form class="form-panel" method="post" action="login.php">
        <h2>Sign in</h2>
        <p class="form-hint">Use the email and password from registration.</p>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label>Email address<input name="email" type="email" autocomplete="email" required value="<?= h($email) ?>"></label>
        <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
        <button class="button button-full" type="submit">Sign in</button>
        <p class="form-switch">New here? <a href="register.php">Create an account</a></p>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
