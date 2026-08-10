<?php
session_start();
include_once '../processes/db_connection.php';

if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows <= 0) {
        header('Location: ../index.php');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
<?php $assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Credential</title>
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
    <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?php echo $assetBase; ?>/script/js-bootstrap/bootstrap.bundle.min.js"></script>
</head>
<body class="app-shell">
    <?php include "../components/header.html"; ?>
    <main class="container-fluid py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-6">
                <div class="app-panel p-4 p-lg-5">
                    <div class="app-kicker mb-2">Account</div>
                    <h1 class="app-section-title h3 mb-2">Edit Credential</h1>
                    <p class="app-muted mb-4">Update the username and password used to sign in to the main system.</p>
                    <form action="../processes/updateSystemCredentials.php" method="post" class="d-grid gap-3">
                        <div>
                            <label for="currentUsername" class="form-label fw-semibold">Current username</label>
                            <input type="text" name="currentUsername" id="currentUsername" class="form-control" required>
                        </div>
                        <div>
                            <label for="currentPassword" class="form-label fw-semibold">Current password</label>
                            <div class="password-field">
                                <input type="password" name="currentPassword" id="currentPassword" class="form-control" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="newUsername" class="form-label fw-semibold">New username</label>
                            <input type="text" name="newUsername" id="newUsername" class="form-control" required>
                        </div>
                        <div>
                            <label for="newPassword" class="form-label fw-semibold">New password</label>
                            <div class="password-field">
                                <input type="password" name="newPassword" id="newPassword" class="form-control" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn app-btn-primary">Update credentials</button>
                            <a href="main.php" class="btn btn-outline-secondary">Back to home</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
