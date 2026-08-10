<?php
session_start();
include '../processes/db_connection.php';

$token = $_GET['token'] ?? ($_SESSION['guest_token'] ?? '');
$pathToken = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
if ($token === '' && preg_match('#^g/([A-Za-z0-9]+)$#', $pathToken, $matches)) {
  $token = $matches[1];
}
$message = '';

$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS guest_scanner_token VARCHAR(64) NULL");
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS guest_scanner_password VARCHAR(255) NULL");

$training = null;
if ($token !== '') {
  $stmt = $conn->prepare("SELECT training_id, training_name, training_days, guest_scanner_password FROM trainings WHERE guest_scanner_token = ? LIMIT 1");
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $training = $stmt->get_result()->fetch_assoc();
}

if (!$training) {
  http_response_code(404);
  echo 'Guest scanner not found.';
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_password'], $_POST['day'], $_POST['mode'])) {
  if (hash_equals((string)($training['guest_scanner_password'] ?? ''), (string)$_POST['guest_password'])) {
    $_SESSION['guest_token'] = $token;
    $_SESSION['guest_training_id'] = $training['training_id'];
    $_SESSION['guest_day'] = (int) $_POST['day'];
    $_SESSION['guest_mode'] = $_POST['mode'] === 'out' ? 'out' : 'in';
    header('Location: ../attendance/scanner.php');
    exit();
  }
  $message = 'Invalid guest password.';
}
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guest Scanner Portal</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="app-auth-page">
  <main class="app-auth-shell">
    <section class="app-auth-card app-auth-card-wide app-shell-card">
      <div class="app-auth-brand">
        <div class="app-auth-badge"><i class="fas fa-qrcode"></i></div>
        <div>
          <div class="app-kicker">Guest attendance</div>
          <h1 class="h3 mb-0 app-section-title"><?php echo htmlspecialchars($training['training_name']); ?></h1>
        </div>
      </div>
      <div class="app-auth-hero">
        <h2 class="h5 mb-2">Guest scanner portal</h2>
        <p class="mb-0 opacity-75">Choose a day and mode, enter the guest password, then you will be taken to the shared scanner.</p>
      </div>
      <?php if ($message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>
      <form method="POST" class="d-grid gap-3">
        <div>
          <label for="day" class="form-label fw-semibold">Day</label>
          <select name="day" id="day" class="form-select" required>
            <?php for ($i = 1; $i <= (int)$training['training_days']; $i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label for="mode" class="form-label fw-semibold">In or out</label>
          <select name="mode" id="mode" class="form-select" required>
            <option value="in">Sign In</option>
            <option value="out">Sign Out</option>
          </select>
        </div>
        <div>
          <label for="guest_password" class="form-label fw-semibold">Guest password</label>
          <div class="password-field">
            <input type="password" name="guest_password" id="guest_password" class="form-control" required>
            <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn app-btn-primary">Open scanner</button>
      </form>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
