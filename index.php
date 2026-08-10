<?php
    session_start();
    include_once 'processes/db_connection.php';

    if (isset($_SESSION['username'])) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result=$stmt->get_result();

        if($result->num_rows > 0){
            header('Location: pages/main.php');
            exit();
        }
    }
?>
<?php
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($assetBase === '' || $assetBase === '.') {
    $assetBase = '.';
}
?>

<html>
    <head>
        <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
        <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HRD-TAS | Login</title>
        <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
    </head>
    <body class="app-auth-page">
        <main class="app-auth-shell">
          <section class="app-auth-card app-shell-card app-auth-card-wide">
            <div class="app-auth-brand">
              <div class="app-auth-badge">TAS</div>
              <div>
                <div class="app-kicker">Training Attendance System</div>
                <h1 class="h3 mb-0 app-section-title">Sign in</h1>
              </div>
            </div>
            <div class="app-auth-hero">
              <h2 class="h5 mb-2">Welcome back</h2>
              <p class="mb-0 opacity-75">Use your account to manage trainings, participants, and attendance records.</p>
            </div>
            <?php 
                if (isset($_GET['error'])) {
                    $loginError = urldecode($_GET['error']);
                    echo '<div class="alert alert-danger py-2">' . htmlspecialchars($loginError) . '</div>';
                }
            ?>
            <form id="login-form" action="processes/login-process.php" method="post" class="d-grid gap-3">
              <div>
                <label for="username" class="form-label fw-semibold">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required>
              </div>
              <div>
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="password-field">
                  <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                  <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary app-btn-primary w-100">Login</button>
            </form>
          </section>
        </main>
      <script src="<?php echo $assetBase; ?>/script/js-bootstrap/bootstrap.bundle.min.js"></script>
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
