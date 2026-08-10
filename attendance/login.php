<?php
session_start();
include '../processes/db_connection.php';

date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
  $inputPassword = $_POST['password'];

  $stmt = $conn->prepare("
    SELECT * FROM `_self-attendance-details`
    WHERE date = ?
    AND (
      (? BETWEEN login_start AND login_end AND login_pw = ?) OR
      (? BETWEEN logout_start AND logout_end AND logout_pw = ?)
    )
    LIMIT 1
  ");
  $stmt->bind_param("sssss", $currentDate, $currentTime, $inputPassword, $currentTime, $inputPassword);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $_SESSION['username'] = 'user';
    $_SESSION['training'] = $row['trainingID'];

    $dayStmt = $conn->prepare("
      SELECT date FROM `_self-attendance-details`
      WHERE trainingID = ?
      ORDER BY date ASC
    ");
    $dayStmt->bind_param("s", $row['trainingID']);
    $dayStmt->execute();
    $dayResult = $dayStmt->get_result();
    $dayNumber = 0;
    while ($dayRow = $dayResult->fetch_assoc()) {
      $dayNumber++;
      if ($dayRow['date'] === $currentDate) break;
    }
    $_SESSION['day_number'] = $dayNumber;
    $_SESSION['attendance_type'] = ($inputPassword === $row['login_pw']) ? "day{$dayNumber}login" : "day{$dayNumber}logout";
    header("Location: scanner.php");
    exit();
  } else {
    $message = "Invalid password or no active session at this time.";
  }
}

$scheduleAvailable = false;
$check = $conn->prepare("
  SELECT 1 FROM `_self-attendance-details`
  WHERE date = ?
  AND ((? BETWEEN login_start AND login_end) OR (? BETWEEN logout_start AND logout_end))
  LIMIT 1
");
$check->bind_param("sss", $currentDate, $currentTime, $currentTime);
$check->execute();
$scheduleAvailable = $check->get_result()->num_rows > 0;

$nextSessionTime = '';
if (!$scheduleAvailable) {
  $nextStmt = $conn->prepare("
    SELECT date, login_start, logout_start
    FROM `_self-attendance-details`
    WHERE (date = ? AND (login_start > ? OR logout_start > ?)) OR (date > ?)
    ORDER BY date ASC, login_start ASC
    LIMIT 1
  ");
  $nextStmt->bind_param("ssss", $currentDate, $currentTime, $currentTime, $currentDate);
  $nextStmt->execute();
  if ($next = $nextStmt->get_result()->fetch_assoc()) {
    $nextSessionTime = $next['date'] . ' ' . (($next['login_start'] && (!$next['logout_start'] || $next['login_start'] < $next['logout_start'])) ? $next['login_start'] : $next['logout_start']);
  }
}

$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Self Attendance Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
</head>
<body class="app-auth-page">
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between">
      <div class="brand-lockup">
        <img src="<?php echo $assetBase; ?>/src/img/csc-logo.png" alt="CSC Logo" width="48" height="48" class="rounded-3">
        <div>
          <div class="app-kicker text-white-50">Training Attendance System</div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="<?php echo $assetBase; ?>/login.php" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" title="Main system login">
          <i class="fas fa-user-lock me-2"></i>Main login
        </a>
        <a href="<?php echo $assetBase; ?>/attendance/admin/login.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold" title="Admin self-attendance login">
          <i class="fas fa-shield-halved me-2"></i>Admin
        </a>
      </div>
    </div>
  </header>

  <main class="container-fluid py-4 py-lg-5">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-7">
        <div class="app-panel p-4 p-lg-5 overflow-hidden">
          <div class="row g-0 align-items-stretch">
            <div class="col-lg-5">
              <div class="h-100 p-4 p-lg-5 text-white" style="background: linear-gradient(160deg, var(--app-brand), var(--app-brand-2)); border-radius: 1rem;">
                <div class="app-auth-badge mb-4"><i class="fas fa-qrcode"></i></div>
                <div class="app-kicker text-white-50 mb-2">Self attendance portal</div>
                <h1 class="display-6 fw-bold mb-3">Participant login</h1>
                <p class="mb-4 opacity-75">Open the QR scanner only when the active attendance window is available.</p>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge rounded-pill <?= $scheduleAvailable ? 'text-bg-success' : 'text-bg-light text-dark' ?>">
                    <?= $scheduleAvailable ? 'Active session' : 'No active session' ?>
                  </span>
                  <span class="badge rounded-pill text-bg-light text-dark">Fast access</span>
                </div>
              </div>
            </div>
            <div class="col-lg-7">
              <div class="p-4 p-lg-5">
                <div class="mb-4">
                  <div class="app-kicker mb-2">Session access</div>
                  <h2 class="app-section-title h3 mb-2">Enter attendance password</h2>
                  <p class="app-muted mb-0">Use the password given by the admin to open the scanner.</p>
                </div>

                <?php if ($message): ?>
                  <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($message) ?>
                  </div>
                <?php endif; ?>

                <?php if ($scheduleAvailable): ?>
                  <form method="POST" action="" class="d-grid gap-3">
                    <div>
                      <label for="password" class="form-label fw-semibold">Attendance password</label>
                      <div class="password-field">
                        <input type="password" name="password" id="password" class="form-control" required autofocus>
                        <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                      <small class="text-muted">Enter the password provided by the admin</small>
                    </div>
                    <button type="submit" class="btn app-btn-primary w-100 py-2 position-relative" style="z-index: 1;">
                      <i class="fas fa-arrow-right-to-bracket me-2"></i>Open scanner
                    </button>
                  </form>
                <?php else: ?>
                  <div class="app-mini-card">
                    <div class="d-flex align-items-center gap-3">
                      <div class="app-auth-badge" style="width: 3rem; height: 3rem;">
                        <i class="fas fa-clock"></i>
                      </div>
                      <div>
                        <h5 class="mb-1">No active attendance session</h5>
                        <p class="app-muted mb-0">Come back when the admin opens the session.</p>
                      </div>
                    </div>
                    <?php if ($nextSessionTime): ?>
                      <div class="mt-4">
                        <p class="app-muted mb-2">Next session starts in:</p>
                        <div class="countdown" id="countdown"></div>
                      </div>
                    <?php else: ?>
                      <p class="app-muted mb-0 mt-3">No upcoming session found.</p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php if ($nextSessionTime): ?>
    <script>
      const countdownTarget = new Date("<?= $nextSessionTime ?>").getTime();
      const countdownEl = document.getElementById("countdown");
      const timer = setInterval(() => {
        const distance = countdownTarget - new Date().getTime();
        if (distance <= 0) {
          clearInterval(timer);
          countdownEl.innerHTML = "Starting now...";
          setTimeout(() => location.reload(), 1000);
          return;
        }
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hrs = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const secs = Math.floor((distance % (1000 * 60)) / 1000);
        countdownEl.innerHTML = `${days > 0 ? days + 'd ' : ''}${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
      }, 1000);
    </script>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.toggle-password').forEach(button => {
      button.addEventListener('click', function () {
        const input = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        const nextType = input.type === 'password' ? 'text' : 'password';
        input.type = nextType;
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      });
    });
    <?php if ($scheduleAvailable): ?>
      document.getElementById('password').focus();
    <?php endif; ?>
  </script>
</body>
</html>
