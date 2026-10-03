<?php
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Enter your name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($phone === '' || strlen($phone) > 30) {
        $errors[] = 'Enter a phone number (up to 30 characters).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Choose a password with at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare('INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)');
            $insert->bind_param('ssss', $name, $email, $passwordHash, $phone);
            $insert->execute();
            set_flash('success', 'Your account is ready. Please sign in.');
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro"><p class="eyebrow">GET STARTED</p><h1>A clearer view of your cash.</h1><p>Create an account to keep your money records private and organized.</p></div>
    <form class="form-panel" method="post" action="register.php" novalidate>
        <h2>Create account</h2>
        <p class="form-hint">All fields are required.</p>
        <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label>Full name<input name="name" type="text" maxlength="100" autocomplete="name" required value="<?= h($name) ?>"></label>
        <label>Email address<input name="email" type="email" maxlength="190" autocomplete="email" required value="<?= h($email) ?>"></label>
        <label>Phone number<input name="phone" type="tel" maxlength="30" autocomplete="tel" required value="<?= h($phone) ?>"></label>
        <label>Password<input name="password" type="password" minlength="8" autocomplete="new-password" required></label>
        <label>Confirm password<input name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></label>
        <button class="button button-full" type="submit">Register</button>
        <p class="form-switch">Already registered? <a href="login.php">Sign in</a></p>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
