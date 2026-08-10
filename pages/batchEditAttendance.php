<?php
session_start();
include '../processes/db_connection.php';

if (!isset($_SESSION['username'])) {
  header('Location: ../index.php');
  exit();
}

$trainingID = $_GET['id'] ?? '';
$day = (int)($_GET['day'] ?? 1);

if ($trainingID === '') {
  header('Location: viewTraining.php');
  exit();
}

$stmtTraining = $conn->prepare("SELECT training_name, training_days FROM trainings WHERE training_id = ? LIMIT 1");
$stmtTraining->bind_param('s', $trainingID);
$stmtTraining->execute();
$training = $stmtTraining->get_result()->fetch_assoc();
if (!$training) {
  header('Location: viewTraining.php');
  exit();
}

$trainingDays = (int)($training['training_days'] ?? 0);
if ($day < 1 || $day > $trainingDays) {
  $day = 1;
}

$trainingTable = "training-$trainingID-$day";
$stmtAttendance = $conn->prepare("SELECT participant_id, lastname, firstname, middle_initial, agency, login, logout FROM `$trainingTable` ORDER BY participant_id ASC");
$stmtAttendance->execute();
$attendance = $stmtAttendance->get_result()->fetch_all(MYSQLI_ASSOC);
$participantCount = count($attendance);
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Batch Edit Attendance</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="app-shell">
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
      <div class="brand-lockup">
        <img src="<?php echo $assetBase; ?>/src/img/csc-logo.png" alt="CSC Logo" width="48" height="48" class="rounded-3">
        <div>
          <div class="app-kicker text-white-50">Training Attendance System</div>
          <div class="fw-semibold text-white"><?php echo htmlspecialchars($training['training_name']); ?></div>
        </div>
      </div>
      <a class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" href="viewTraining.php?id=<?php echo urlencode($trainingID); ?>">Back</a>
    </div>
  </header>

  <main class="container-fluid py-4 py-lg-5">
    <div class="app-panel p-4 p-lg-5 mx-auto batch-page-content" style="max-width: 1200px;">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
          <div class="app-kicker mb-1">Batch edit</div>
          <h1 class="app-section-title h3 mb-2">Attendance</h1>
          <p class="app-muted mb-0">Edit sign-in and sign-out times for multiple participants in one screen.</p>
        </div>
        <div class="app-mini-card">
          <div class="small text-muted">Day</div>
          <div class="h4 mb-0"><?php echo (int)$day; ?></div>
        </div>
      </div>

      <form action="../processes/batchUpdateAttendance.php" method="post" id="batchEditAttendanceForm" class="batch-edit-form">
        <input type="hidden" name="trainingID" value="<?php echo htmlspecialchars($trainingID); ?>">
        <input type="hidden" name="day" value="<?php echo (int)$day; ?>">

        <div class="app-toolbar mb-4 batch-search-toolbar">
          <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
            <label for="attendanceSearch" class="form-label fw-semibold mb-0">Search attendance</label>
            <div class="small text-muted" id="attendanceSearchCount">Showing <?php echo (int)$participantCount; ?> participants</div>
          </div>
          <div class="batch-search-group">
            <span class="batch-search-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
            <input type="search" id="attendanceSearch" class="form-control batch-search-input" placeholder="Type a name, ID, or agency">
            <button type="button" class="btn btn-outline-secondary batch-search-clear" id="clearAttendanceSearchBtn">Clear</button>
          </div>
        </div>

        <div class="table-responsive d-none d-md-block">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width: 90px;">ID</th>
                <th>Name</th>
                <th>Agency</th>
                <th style="width: 140px;">In</th>
                <th style="width: 140px;">Out</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendance as $row): ?>
                <?php
                  $fullName = trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middle_initial']);
                  $login = !empty($row['login']) ? date('H:i', strtotime($row['login'])) : '';
                  $logout = !empty($row['logout']) ? date('H:i', strtotime($row['logout'])) : '';
                  $search = strtolower($row['participant_id'] . ' ' . $row['lastname'] . ' ' . $row['firstname'] . ' ' . $row['middle_initial'] . ' ' . $row['agency']);
                ?>
                <tr class="attendance-row" data-search="<?php echo htmlspecialchars($search); ?>">
                  <td class="fw-semibold"><?php echo htmlspecialchars($row['participant_id']); ?></td>
                  <td><?php echo htmlspecialchars($fullName); ?></td>
                  <td><?php echo htmlspecialchars($row['agency']); ?></td>
                  <td><input type="time" step="60" class="form-control" name="participants[<?php echo htmlspecialchars($row['participant_id']); ?>][login]" value="<?php echo htmlspecialchars($login); ?>"></td>
                  <td><input type="time" step="60" class="form-control" name="participants[<?php echo htmlspecialchars($row['participant_id']); ?>][logout]" value="<?php echo htmlspecialchars($logout); ?>"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-grid gap-3 d-md-none">
          <?php foreach ($attendance as $row): ?>
            <?php
              $fullName = trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middle_initial']);
              $login = !empty($row['login']) ? date('H:i', strtotime($row['login'])) : '';
              $logout = !empty($row['logout']) ? date('H:i', strtotime($row['logout'])) : '';
              $search = strtolower($row['participant_id'] . ' ' . $row['lastname'] . ' ' . $row['firstname'] . ' ' . $row['middle_initial'] . ' ' . $row['agency']);
            ?>
            <div class="app-record-card attendance-card" data-search="<?php echo htmlspecialchars($search); ?>">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                  <div class="app-kicker mb-1">Participant ID</div>
                  <div class="h5 mb-0"><?php echo htmlspecialchars($row['participant_id']); ?></div>
                  <div class="small text-muted mt-1"><?php echo htmlspecialchars($fullName); ?></div>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-12">
                  <div class="small text-muted mb-1">Agency</div>
                  <div class="fw-semibold"><?php echo htmlspecialchars($row['agency']); ?></div>
                </div>
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold">In</label>
                  <input type="time" step="60" class="form-control" name="participants[<?php echo htmlspecialchars($row['participant_id']); ?>][login]" value="<?php echo htmlspecialchars($login); ?>">
                </div>
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold">Out</label>
                  <input type="time" step="60" class="form-control" name="participants[<?php echo htmlspecialchars($row['participant_id']); ?>][logout]" value="<?php echo htmlspecialchars($logout); ?>">
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="app-record-card text-center text-muted py-4 d-none" id="noAttendanceResultsMessage">
          No participants match your search.
        </div>

      </form>
    </div>
  </main>
  <div class="batch-save-float">
    <button type="submit" form="batchEditAttendanceForm" class="btn app-btn-primary">
      <i class="fas fa-save me-1"></i>Save all attendance
    </button>
  </div>

  <script>
    const attendanceSearch = document.getElementById('attendanceSearch');
    const clearAttendanceSearchBtn = document.getElementById('clearAttendanceSearchBtn');
    const attendanceSearchCount = document.getElementById('attendanceSearchCount');
    const noAttendanceResultsMessage = document.getElementById('noAttendanceResultsMessage');
    const desktopAttendanceTable = document.querySelector('.table-responsive.d-none.d-md-block');
    const mobileAttendanceList = document.querySelector('.d-grid.gap-3.d-md-none');

    function syncAttendanceFieldVisibility() {
      const isDesktop = window.matchMedia('(min-width: 768px)').matches;

      document.querySelectorAll('.attendance-row input, .attendance-card input').forEach(input => {
        input.disabled = false;
      });

      if (desktopAttendanceTable) {
        desktopAttendanceTable.querySelectorAll('input').forEach(input => {
          input.disabled = !isDesktop;
        });
      }

      if (mobileAttendanceList) {
        mobileAttendanceList.querySelectorAll('input').forEach(input => {
          input.disabled = isDesktop;
        });
      }
    }

    function filterAttendance(term) {
      const normalized = term.trim().toLowerCase();
      const rows = document.querySelectorAll('.attendance-row, .attendance-card');
      let visibleCount = 0;

      rows.forEach(row => {
        const haystack = row.dataset.search || '';
        const match = !normalized || haystack.includes(normalized);
        row.classList.toggle('d-none', !match);
        if (match) {
          visibleCount++;
        }
      });

      if (attendanceSearchCount) {
        attendanceSearchCount.textContent = normalized
          ? `Showing ${visibleCount} participant${visibleCount === 1 ? '' : 's'}`
          : `Showing <?php echo (int)$participantCount; ?> participants`;
      }

      if (noAttendanceResultsMessage) {
        noAttendanceResultsMessage.classList.toggle('d-none', visibleCount !== 0 || !normalized);
      }
    }

    if (attendanceSearch) {
      attendanceSearch.addEventListener('input', () => {
        filterAttendance(attendanceSearch.value);
      });
    }

    if (clearAttendanceSearchBtn && attendanceSearch) {
      clearAttendanceSearchBtn.addEventListener('click', () => {
        attendanceSearch.value = '';
        attendanceSearch.focus();
        filterAttendance('');
      });
    }

    syncAttendanceFieldVisibility();
    window.addEventListener('resize', syncAttendanceFieldVisibility);
  </script>
</body>
</html>
