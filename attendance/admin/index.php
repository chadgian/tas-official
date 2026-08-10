<?php
include '../../processes/db_connection.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'hrd') {
  header('Location: ../login.php');
  exit();
}

$selectedTraining = $_GET['training'] ?? '';

$getAllTraining = $conn->prepare("SELECT training_id, training_name, training_days FROM trainings ORDER BY training_id DESC");
$getAllTraining->execute();
$getAllTrainingResult = $getAllTraining->get_result();

$trainingDays = 0;
if ($selectedTraining) {
  $q = $conn->prepare("SELECT training_days FROM trainings WHERE training_id = ?");
  $q->bind_param("i", $selectedTraining);
  $q->execute();
  $res = $q->get_result();
  if ($row = $res->fetch_assoc()) {
    $trainingDays = (int) $row['training_days'];
  }
}

$schedules = [];
if ($selectedTraining) {
  $stmt = $conn->prepare(
    "SELECT schedID, dayNumber, date, login_start, login_end, logout_start, logout_end, login_pw, logout_pw
     FROM `_self-attendance-details`
     WHERE trainingID = ?
     ORDER BY dayNumber"
  );
  $stmt->bind_param("i", $selectedTraining);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $schedules[$row['dayNumber']] = $row;
  }
}
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 3), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Self Attendance Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
</head>
<body class="app-shell">
  <main class="container-fluid py-4 py-lg-5">
    <div class="app-panel p-4 p-lg-5 mb-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
        <div>
          <div class="app-kicker">Self attendance admin</div>
          <h1 class="app-section-title mb-2"><i class="fas fa-calendar-check me-2"></i>Schedule manager</h1>
          <p class="app-muted mb-0">Pick a training, configure its session windows, and generate access passwords.</p>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger rounded-pill px-3">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-xl-4">
        <div class="app-panel p-4 h-100">
          <div class="app-kicker mb-2">Training</div>
          <h2 class="app-section-title h4 mb-3">Select program</h2>
          <form method="GET" class="d-grid gap-3">
            <div>
              <label for="training" class="form-label fw-semibold">Training</label>
              <select name="training" id="training" class="form-select form-select-lg" onchange="this.form.submit()">
                <option value="">Choose a training</option>
                <?php while ($t = $getAllTrainingResult->fetch_assoc()): ?>
                  <option value="<?= $t['training_id'] ?>" <?= ($t['training_id'] == $selectedTraining ? 'selected' : '') ?>>
                    <?= htmlspecialchars($t['training_name']) ?> (<?= $t['training_days'] ?> days)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <button type="submit" class="btn app-btn-primary">
              <i class="fas fa-folder-open me-2"></i>Load training
            </button>
          </form>

          <?php if ($selectedTraining): ?>
            <div class="app-section-grid mt-4">
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Days</div>
                <div class="h4 mb-0"><?= $trainingDays ?></div>
              </div>
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Training ID</div>
                <div class="h4 mb-0"><?= htmlspecialchars($selectedTraining) ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-xl-8">
        <?php if ($selectedTraining): ?>
          <div class="app-panel p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
              <div>
                <div class="app-kicker">Configuration</div>
                <h2 class="app-section-title h4 mb-1">Session windows</h2>
                <p class="app-muted mb-0">Each day needs its own date, login window, logout window, and passwords.</p>
              </div>
              <button type="button" onclick="generatePasswords()" class="btn app-btn-soft">
                <i class="fas fa-wand-magic-sparkles me-2"></i>Generate passwords
              </button>
            </div>

            <form action="updateSelfAttendance.php" method="POST" id="attendanceForm">
              <input type="hidden" name="trainingID" value="<?= $selectedTraining ?>">
              <div class="row g-4">
                <?php for ($day = 1; $day <= $trainingDays; $day++):
                  $sched = $schedules[$day] ?? null;
                ?>
                  <div class="col-12 col-xxl-6">
                    <div class="app-mini-card h-100">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <div class="app-kicker mb-1">Day <?= $day ?></div>
                          <h3 class="h5 mb-0">Schedule setup</h3>
                        </div>
                        <div class="app-auth-badge" style="width: 2.8rem; height: 2.8rem; border-radius: .9rem;">
                          <?= $day ?>
                        </div>
                      </div>
                      <?php if ($sched): ?>
                        <input type="hidden" name="days[<?= $day ?>][schedID]" value="<?= $sched['schedID'] ?>">
                      <?php endif; ?>
                      <div class="row g-3">
                        <div class="col-12">
                          <label class="form-label fw-semibold">Date</label>
                          <input type="date" name="days[<?= $day ?>][date]" class="form-control" value="<?= $sched['date'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Login start</label>
                          <input type="time" name="days[<?= $day ?>][login_start]" class="form-control" value="<?= $sched['login_start'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Login end</label>
                          <input type="time" name="days[<?= $day ?>][login_end]" class="form-control" value="<?= $sched['login_end'] ?? '' ?>" required>
                        </div>
                        <div class="col-12">
                          <label class="form-label fw-semibold">Login password</label>
                          <div class="input-group">
                            <input type="text" name="days[<?= $day ?>][login_pw]" class="form-control login-pw" value="<?= htmlspecialchars($sched['login_pw'] ?? '') ?>" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Logout start</label>
                          <input type="time" name="days[<?= $day ?>][logout_start]" class="form-control" value="<?= $sched['logout_start'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">Logout end</label>
                          <input type="time" name="days[<?= $day ?>][logout_end]" class="form-control" value="<?= $sched['logout_end'] ?? '' ?>" required>
                        </div>
                        <div class="col-12">
                          <label class="form-label fw-semibold">Logout password</label>
                          <div class="input-group">
                            <input type="text" name="days[<?= $day ?>][logout_pw]" class="form-control logout-pw" value="<?= htmlspecialchars($sched['logout_pw'] ?? '') ?>" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endfor; ?>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="../logout.php" class="btn btn-outline-danger">Logout</a>
                <button type="submit" class="btn app-btn-primary"><i class="fas fa-save me-2"></i>Save all</button>
              </div>
            </form>
          </div>
        <?php else: ?>
          <div class="app-panel p-5 text-center">
            <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
            <h3 class="mb-2">No training selected</h3>
            <p class="app-muted mb-0">Choose a training from the left panel to start configuring self attendance.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function generatePasswords() {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      const generate = () => Array.from({ length: 8 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
      document.querySelectorAll('.login-pw').forEach(input => input.value = generate());
      document.querySelectorAll('.logout-pw').forEach(input => input.value = generate());
      showNotification('Passwords generated for all days!', 'success');
    }

    function togglePassword(button) {
      const input = button.parentElement.querySelector('input');
      const icon = button.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    function showNotification(message, type) {
      const existing = document.querySelector('.notification');
      if (existing) existing.remove();
      const colors = { success: '#28a745', error: '#dc3545', info: '#17a2b8' };
      const icon = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
      const notification = document.createElement('div');
      notification.className = 'notification';
      notification.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="toast-header text-white" style="background: ${colors[type]}">
            <strong class="me-auto"><i class="fas ${icon[type]} me-2"></i>${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
          <div class="toast-body">${message}</div>
        </div>`;
      document.body.appendChild(notification);
      setTimeout(() => notification.remove(), 3000);
    }
  </script>
</body>
</html>
