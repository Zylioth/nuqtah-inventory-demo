<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Nuqtah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>.change-card{max-width:600px;margin:40px auto;}</style>
</head>
<body class="bg-light">

<?php include 'includes/header.php'; ?>
<?php include 'includes/users_sidebar.php'; ?>

<div class="container change-card">
    <div class="card shadow-sm rounded-4 p-4">
        <h4 class="mb-3">Change Password</h4>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger small">
                <?php
                    if ($_GET['error'] == 'nomatch') echo 'New passwords do not match.';
                    elseif ($_GET['error'] == 'weak') echo 'New password must be at least 8 characters.';
                    elseif ($_GET['error'] == 'wrong_current') echo 'Current password is incorrect.';
                    else echo 'An error occurred. Please try again.';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success small">Password updated successfully.</div>
        <?php endif; ?>

        <form action="actions/change_password.php" method="post">
            <div class="mb-3">
                <label class="form-label small">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label small">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
                <div class="form-text small">Minimum 8 characters recommended.</div>
            </div>

            <div class="mb-3">
                <label class="form-label small">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-teal rounded-pill text-white">Change Password</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
