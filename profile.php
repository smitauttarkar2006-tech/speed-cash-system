<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$profileErrors = [];
$passwordErrors = [];
$profileName = '';
$profileEmail = '';
$profilePhone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'update_profile') {
        $profileName = trim($_POST['name'] ?? '');
        $profileEmail = strtolower(trim($_POST['email'] ?? ''));
        $profilePhone = trim($_POST['phone'] ?? '');

        if ($profileName === '' || mb_strlen($profileName) > 100) {
            $profileErrors[] = 'Enter your name (up to 100 characters).';
        }
        if (!filter_var($profileEmail, FILTER_VALIDATE_EMAIL) || strlen($profileEmail) > 190) {
            $profileErrors[] = 'Enter a valid email address.';
        }
        if ($profilePhone === '' || strlen($profilePhone) > 30) {
            $profileErrors[] = 'Enter a phone number (up to 30 characters).';
        }
        if (!$profileErrors) {
            $duplicate = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $duplicate->bind_param('si', $profileEmail, $userId);
            $duplicate->execute();
            if ($duplicate->get_result()->num_rows > 0) {
                $profileErrors[] = 'That email address is already used by another account.';
            } else {
                $update = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
                $update->bind_param('sssi', $profileName, $profileEmail, $profilePhone, $userId);
                $update->execute();
                $_SESSION['user_name'] = $profileName;
                set_flash('success', 'Your profile was updated.');
                header('Location: profile.php');
                exit;
            }
        }
    } elseif ($formAction === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $passwordQuery = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $passwordQuery->bind_param('i', $userId);
        $passwordQuery->execute();
        $storedPassword = $passwordQuery->get_result()->fetch_assoc()['password'];

        if (!password_verify($currentPassword, $storedPassword)) {
            $passwordErrors[] = 'Your current password is incorrect.';
        }
        if (strlen($newPassword) < 8) {
            $passwordErrors[] = 'Choose a new password with at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'The new passwords do not match.';
        }
        if (!$passwordErrors) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePassword = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updatePassword->bind_param('si', $newHash, $userId);
            $updatePassword->execute();
            set_flash('success', 'Your password was changed.');
            header('Location: profile.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form_action'] ?? '') !== 'update_profile' || !$profileErrors) {
    $userQuery = $conn->prepare('SELECT name, email, phone FROM users WHERE id = ?');
    $userQuery->bind_param('i', $userId);
    $userQuery->execute();
    $profile = $userQuery->get_result()->fetch_assoc();
    $profileName = $profile['name'];
    $profileEmail = $profile['email'];
    $profilePhone = $profile['phone'];
}

$pageTitle = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><p class="eyebrow">ACCOUNT SETTINGS</p><h1>Your profile</h1><p>Update your contact details or change your password.</p></section>
<div class="profile-grid">
    <form class="form-panel" method="post" action="profile.php">
        <h2>Personal details</h2>
        <?php if ($profileErrors): ?><div class="alert alert-error"><ul><?php foreach ($profileErrors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="form_action" value="update_profile">
        <label>Full name<input name="name" type="text" maxlength="100" autocomplete="name" required value="<?= h($profileName) ?>"></label>
        <label>Email address<input name="email" type="email" maxlength="190" autocomplete="email" required value="<?= h($profileEmail) ?>"></label>
        <label>Phone number<input name="phone" type="tel" maxlength="30" autocomplete="tel" required value="<?= h($profilePhone) ?>"></label>
        <button class="button" type="submit">Save profile</button>
    </form>
    <form class="form-panel" method="post" action="profile.php">
        <h2>Change password</h2><p class="form-hint">Your password is never displayed.</p>
        <?php if ($passwordErrors): ?><div class="alert alert-error"><ul><?php foreach ($passwordErrors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="form_action" value="change_password">
        <label>Current password<input name="current_password" type="password" autocomplete="current-password" required></label>
        <label>New password<input name="new_password" type="password" minlength="8" autocomplete="new-password" required></label>
        <label>Confirm new password<input name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></label>
        <button class="button button-dark" type="submit">Change password</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
