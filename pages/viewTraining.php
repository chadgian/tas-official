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

if (!isset($_GET['id'])) {
  header('Location: main.php');
  exit();
}

$trainingID = $_GET['id'];
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_start_date DATE NULL");
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_venue VARCHAR(255) NULL");

$stmtName = $conn->prepare("SELECT * FROM trainings WHERE training_id = ?");
$stmtName->bind_param('s', $trainingID);
$stmtName->execute();
$resultName = $stmtName->get_result();

if ($resultName->num_rows > 0) {
  $dataName = $resultName->fetch_assoc();
  $trainingName = $dataName['training_name'];
  $trainingDays = (int) $dataName['training_days'];
  $savedTrainingName = $_SESSION['generated_training_name'] ?? $trainingName;
  $trainingStartDate = $dataName['training_start_date'] ?? '';
  $trainingVenue = $dataName['training_venue'] ?? '';
  $canGenerateIds = !empty($trainingStartDate) && !empty($trainingVenue);
} else {
  $trainingName = 'Training not found';
  $trainingDays = 0;
  $savedTrainingName = $_SESSION['generated_training_name'] ?? '';
  $trainingStartDate = '';
  $trainingVenue = '';
  $canGenerateIds = false;
}

$participantCount = 0;
$participantTable = "training-$trainingID-1";
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `$participantTable`");
if ($countStmt && $countStmt->execute()) {
  $countResult = $countStmt->get_result();
  if ($countRow = $countResult->fetch_assoc()) {
    $participantCount = (int) ($countRow['total'] ?? 0);
  }
}

$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS guest_scanner_token VARCHAR(64) NULL");
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS guest_scanner_password VARCHAR(255) NULL");

$guestStmt = $conn->prepare("SELECT guest_scanner_token, guest_scanner_password FROM trainings WHERE training_id = ? LIMIT 1");
$guestStmt->bind_param('s', $trainingID);
$guestStmt->execute();
$guestRow = $guestStmt->get_result()->fetch_assoc() ?: [];
$guestToken = $guestRow['guest_scanner_token'] ?? '';
$guestPassword = $guestRow['guest_scanner_password'] ?? '';

if ($guestToken === '') {
  $guestToken = bin2hex(random_bytes(16));
  $tokenStmt = $conn->prepare("UPDATE trainings SET guest_scanner_token = ? WHERE training_id = ?");
  $tokenStmt->bind_param('ss', $guestToken, $trainingID);
  $tokenStmt->execute();
}

$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = $assetBase;
$guestLink = $scheme . '://' . $host . $basePath . '/g/' . urlencode($guestToken);
?>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Attendance</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
  <script src="<?php echo $assetBase; ?>/script/js-bootstrap/bootstrap.min.js"></script>
  <script src="<?php echo $assetBase; ?>/script/jquery-3.6.0.min.js"></script>
</head>
<body class="app-shell">
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between">
      <div class="brand-lockup">
        <img src="<?php echo $assetBase; ?>/src/img/csc-logo.png" alt="CSC Logo" width="48" height="48" class="rounded-3">
        <div>
          <div class="app-kicker text-white-50">Training Attendance System</div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="main.php" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" aria-label="Back to list" title="Back to list">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M12.5 8a.5.5 0 0 1-.5.5H4.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L4.707 7.5H12a.5.5 0 0 1 .5.5z"/>
          </svg>
        </a>
        <button class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="offcanvas" href="#sidebar" role="button" aria-controls="sidebar" aria-label="Actions" title="Actions">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
          </svg>
        </button>
      </div>
    </div>
  </header>

  <main class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="app-panel p-4 p-lg-5">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-4">
            <div>
              <div class="app-kicker mb-2">Current training</div>
              <h1 class="app-section-title mb-2"><?php echo htmlspecialchars($trainingName); ?></h1>
              <p class="app-muted mb-0">Use the action drawer to manage details, participants, attendance, and exports.</p>
            </div>
            <div class="app-section-grid" style="min-width:min(100%, 620px);">
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Total participants</div>
                <div class="h5 mb-0"><?php echo $participantCount; ?></div>
              </div>
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Days</div>
                <div class="h5 mb-0"><?php echo (int) $trainingDays; ?></div>
              </div>
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Start date</div>
                <div class="h5 mb-0"><?php echo $trainingStartDate ? htmlspecialchars($trainingStartDate) : 'Not set'; ?></div>
              </div>
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Venue</div>
                <div class="h6 mb-0"><?php echo $trainingVenue ? htmlspecialchars($trainingVenue) : 'Not set'; ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="app-panel p-3 p-lg-4">
          <?php
          $page = $_GET['page'] ?? null;
          switch ($page) {
            case "attendance":
              $day = $_GET['day'] ?? 1;
              require "../components/attendance.php";
              break;
            default:
              require "../components/participants.php";
              break;
          }
          ?>
        </div>
      </div>
    </div>
  </main>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header">
      <div>
        <div class="app-kicker">Actions</div>
        <h5 class="offcanvas-title mb-0" id="sidebarLabel">Training menu</h5>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="accordion app-sidebar" id="sidebarAccordion">
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTrainingDetails" aria-expanded="false" aria-controls="collapseTrainingDetails">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13Zm0 1a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>
                <path d="M8 4a.5.5 0 0 1 .5.5V8H12a.5.5 0 0 1 0 1H8.5v3.5a.5.5 0 0 1-1 0V9H4a.5.5 0 0 1 0-1h3.5V4.5A.5.5 0 0 1 8 4Z"/>
              </svg>
              <span>Training details</span>
            </button>
          </h2>
          <div id="collapseTrainingDetails" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <form action="../processes/setTrainingDetails.php" method="post" class="d-grid gap-3">
                <input type="hidden" name="trainingID" value="<?php echo $trainingID; ?>">
                <div>
                  <label for="trainingStartDate" class="form-label fw-semibold">Start date</label>
                  <input type="date" name="trainingStartDate" id="trainingStartDate" class="form-control" value="<?php echo htmlspecialchars($trainingStartDate); ?>" required>
                </div>
                <div>
                  <label for="trainingVenue" class="form-label fw-semibold">Venue</label>
                  <input type="text" name="trainingVenue" id="trainingVenue" class="form-control" value="<?php echo htmlspecialchars($trainingVenue); ?>" placeholder="Grand Xing Imperial Hotel, Iloilo City" required>
                </div>
                <small class="text-muted">Used in exports, QR verification, and ID generation.</small>
                <button type="submit" class="btn app-btn-primary">Save details</button>
              </form>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#addParticipant" aria-expanded="false" aria-controls="addParticipant">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0 1c-2.67 0-8 1.34-8 4v1h8v-1a5 5 0 0 1 3.9-4.9A7.5 7.5 0 0 0 8 8Z"/>
                <path d="M13 8.5a.5.5 0 0 1 .5.5V11h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2V9a.5.5 0 0 1 .5-.5Z"/>
              </svg>
              <span>Reset participants</span>
            </button>
          </h2>
          <div id="addParticipant" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <p class="text-muted">Upload an Excel file with participant number, last name, first name, middle initial, and agency.</p>
              <form action="../processes/exportExcel.php" method="post" enctype="multipart/form-data" class="d-grid gap-3">
                <input type="hidden" value="<?php echo $trainingID; ?>" name="trainingID" id="trainingID">
                <input type="file" name="excelFile" id="excelFile" class="form-control">
                <button type="submit" class="btn app-btn-primary">Upload file</button>
              </form>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M12.146.854a.5.5 0 0 1 .708 0l2.292 2.292a.5.5 0 0 1 0 .708L5.207 13.793l-2.5.5.5-2.5L12.146.854Zm1.061 2.475-1.768-1.768L4.5 8.5l1.768 1.768 6.939-6.939ZM2 13.5V11h2.5a.5.5 0 0 1 0 1H3v1.5a.5.5 0 0 1-1 0Z"/>
              </svg>
              <span>Edit/Add Participants</span>
            </button>
          </h2>
          <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <p class="app-muted mb-3">Open the batch editor to update multiple participants in one place, or add new rows in the batch add screen.</p>
              <div class="d-grid gap-2">
                <a href="../pages/batchEditParticipants.php?id=<?php echo urlencode($trainingID); ?>" class="btn app-btn-primary">Open batch editor</a>
                <a href="../pages/batchAddParticipants.php?id=<?php echo urlencode($trainingID); ?>" class="btn app-btn-soft">Add multiple participants</a>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAttendanceEdit" aria-expanded="false" aria-controls="collapseAttendanceEdit">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.168.11l-4 1.5a.5.5 0 0 1-.65-.65l1.5-4a.5.5 0 0 1 .11-.168l7-7ZM11.207 2 13.5 4.293 14.793 3 12.5.707 11.207 2ZM12.5 5.207 10.293 3 4 9.293V10h.707L12.5 5.207Z"/>
              </svg>
              <span>Edit attendance</span>
            </button>
          </h2>
          <div id="collapseAttendanceEdit" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <p class="app-muted mb-3">Edit sign-in and sign-out time records for each day using the same batch editing style.</p>
              <div class="app-section-grid">
                <?php for ($i = 1; $i <= $trainingDays; $i++): ?>
                  <a href="../pages/batchEditAttendance.php?id=<?php echo urlencode($trainingID); ?>&day=<?php echo $i; ?>" class="app-link-button justify-content-between">
                    <span>Day <?php echo $i; ?></span>
                    <span class="text-muted">Open</span>
                  </a>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M14 4.5V14a1 1 0 0 1-1 1H6.5a1 1 0 0 1-1-1V2A1 1 0 0 1 6.5 1H10l4 3.5ZM10.5 5V2.5L13.5 5H10.5ZM3.5 4a1 1 0 0 0-1 1v9A1.5 1.5 0 0 0 4 15h6.5a1 1 0 0 0 1-1h-1.5A.5.5 0 0 1 9 13.5v-9a.5.5 0 0 0-.5-.5H3.5Z"/>
              </svg>
              <span>Generate IDs</span>
            </button>
          </h2>
          <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <p class="app-muted mb-3">Open the modal to choose participants and generate the printable Word file.</p>
              <?php if (!$canGenerateIds): ?>
                <div class="text-danger fw-semibold small mb-2">Set the Start Date and Venue first.</div>
              <?php endif; ?>
              <span class="d-inline-block w-100" title="<?php echo $canGenerateIds ? '' : 'Set the Start Date and Venue first.'; ?>">
                <button type="button" class="btn app-btn-primary w-100" id="openGenerateIdsModalBtn" <?php echo $canGenerateIds ? '' : 'disabled'; ?>>
                  Open ID generator
                </button>
              </span>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M14 3h-1V1h-2v2H5V1H3v2H2a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1Zm0 10H2V6h12v7Z"/>
                <path d="M4 8h3v3H4V8Zm4 0h3v3H8V8Zm4 0h0v3h-3V8h3Z"/>
              </svg>
              <span>View attendance</span>
            </button>
          </h2>
          <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <div class="app-section-grid w-100">
                <?php for ($i = 1; $i <= $trainingDays; $i++): ?>
                  <a class="app-link-button justify-content-between" href="viewTraining.php?page=attendance&day=<?php echo $i; ?>&id=<?php echo $trainingID; ?>">
                    <span>Day <?php echo $i; ?></span>
                    <span class="text-muted">Open</span>
                  </a>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatistics" aria-expanded="false" aria-controls="collapseStatistics">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 13.5 3H2.5A1.5 1.5 0 0 0 1 4.5v9Zm1.5-.5h11V5h-11v8Zm2-1h1V8h-1v4Zm3 0h1V6h-1v6Zm3 0h1V9h-1v3Z"/>
              </svg>
              <span>Statistics</span>
            </button>
          </h2>
          <div id="collapseStatistics" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <p class="app-muted mb-3">View the attendance percentage for today and each agency.</p>
              <div class="app-section-grid w-100">
                <?php for ($i = 1; $i <= $trainingDays; $i++): ?>
                  <a class="app-link-button justify-content-between" href="viewStatistics.php?id=<?php echo $trainingID; ?>&day=<?php echo $i; ?>">
                    <span>Day <?php echo $i; ?></span>
                    <span class="text-muted">Open</span>
                  </a>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5"/>
                <path d="M7 2H2v5h5V2Zm-1 1v3H3V3h3Zm6 0h1v1h-1V3Zm0 2h1v1h-1V5ZM7 9H2v5h5V9Zm-1 1v3H3v-3h3Zm5-1h1v1h-1v-1Zm2 2h1v1h-1v-1ZM9 2h5v5H9V2Zm1 1v3h3V3h-3Z"/>
              </svg>
              <span>Scan attendance</span>
            </button>
          </h2>
          <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <div class="app-muted mb-3"><?php echo htmlspecialchars($trainingName); ?></div>
              <div class="accordion app-sidebar" id="attendanceAccordion">
                <?php for ($i = 1; $i <= $trainingDays; $i++): ?>
                  <div class="accordion-item">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#out-<?php echo $i; ?>" aria-expanded="false" aria-controls="out-<?php echo $i; ?>">Day <?php echo $i; ?></button>
                    </h2>
                    <div id="out-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#attendanceAccordion">
                      <div class="accordion-body">
                        <form action="../attendance/scanner.php" method="post" class="d-flex gap-2">
                          <input type="hidden" value="<?php echo $trainingID; ?>" name="training">
                          <input type="hidden" value="<?php echo $i; ?>" name="days">
                          <button type="submit" value="in" name="inORout" class="btn app-btn-soft flex-fill">In</button>
                          <button type="submit" value="out" name="inORout" class="btn app-btn-soft flex-fill">Out</button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGuest" aria-expanded="false" aria-controls="collapseGuest">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13Zm0 1a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>
                <path d="M8 4a1 1 0 1 0 0 2A1 1 0 0 0 8 4Zm0 3a3 3 0 0 0-2.83 2H10.83A3 3 0 0 0 8 7Z"/>
              </svg>
              <span>Guest scanner</span>
            </button>
          </h2>
          <div id="collapseGuest" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
            <div class="accordion-body">
              <form action="../processes/setGuestScanner.php" method="post" class="d-grid gap-3">
                <input type="hidden" name="trainingID" value="<?php echo $trainingID; ?>">
                <div>
                  <label for="guestPassword" class="form-label fw-semibold">Guest password</label>
                  <input type="text" name="guestPassword" id="guestPassword" class="form-control" value="<?php echo htmlspecialchars($guestPassword); ?>" placeholder="Create a password for guest scanning" required>
                </div>
                <button type="submit" class="btn app-btn-primary">Save guest scanner</button>
              </form>
              <div class="app-mini-card mt-3">
                <div class="app-kicker mb-1">Guest link</div>
                <div class="input-group app-guest-link-group mb-2">
                  <input type="text" class="form-control app-guest-link-input" id="guestLinkInput" value="<?php echo htmlspecialchars($guestLink); ?>" readonly>
                  <button type="button" class="btn app-btn-soft app-guest-link-copy" id="copyGuestLinkBtn">Copy</button>
                </div>
                <small class="text-muted">Share this link with guests. They will choose the day and whether they are signing in or out.</small>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mt-3">
        <div class="app-toolbar-actions">
          <a href="../processes/exportAttendance.php?id=<?php echo $trainingID; ?>" class="app-link-button">Export attendance</a>
          <a href="../processes/exportAttendancePdf.php?id=<?php echo $trainingID; ?>" class="app-link-button">Export PDF</a>
        </div>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#exampleModal">Delete training</button>
      </div>
    </div>
  </main>

  <div class="generate-ids-overlay" id="generateIdsOverlay" aria-hidden="true">
    <div class="generate-ids-panel">
      <div class="modal-header">
        <div>
          <div class="app-kicker mb-1">Generate IDs</div>
          <h1 class="modal-title fs-5 mb-0" id="generateIdsModalLabel">Select participants to print</h1>
        </div>
        <button type="button" class="btn-close" id="closeGenerateIdsModalBtn" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <form action="../processes/generateIDProcess.php" method="POST" class="d-grid gap-3" id="generateIdsForm">
            <input type="hidden" value="<?php echo $trainingID; ?>" name="id" id="id">
            <div>
              <label for="trainingName" class="form-label fw-semibold">Training name</label>
              <input type="text" name="trainingName" id="trainingName" class="form-control" placeholder="e.g. ePRIMEtime" value="<?php echo htmlspecialchars($savedTrainingName ?? ''); ?>" required>
            </div>
            <button type="submit" class="btn app-btn-primary">Generate and download IDs</button>
            <div class="app-mini-card">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <div>
                  <div class="app-kicker mb-1">Select participants</div>
                  <div class="fw-semibold">Generate IDs for checked participants only</div>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm app-btn-soft" id="selectAllIdsBtn">Select all</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllIdsBtn">Clear</button>
                </div>
              </div>
              <div class="batch-id-search mb-3">
                <span class="batch-id-search-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                <input type="search" class="form-control batch-id-search-input" id="participantIdSearch" placeholder="Search by ID, name, or agency">
              </div>
              <div class="app-searchable-list batch-id-select-list" id="participantIdList">
                <?php
                $trainingTable = "training-$trainingID-1";
                $stmtParticipants = $conn->prepare("SELECT participant_id, firstname, middle_initial, lastname, agency FROM `$trainingTable` ORDER BY participant_id ASC");
                $stmtParticipants->execute();
                $resultParticipants = $stmtParticipants->get_result();
                while ($row = $resultParticipants->fetch_assoc()) {
                  $participantID = $row['participant_id'];
                  $participantName = trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middle_initial']);
                  $participantAgency = $row['agency'];
                  $participantSearch = strtolower(trim($participantID . ' ' . $participantName . ' ' . $participantAgency));
                ?>
                  <label class="batch-id-item" data-search="<?php echo htmlspecialchars($participantSearch); ?>">
                    <input class="form-check-input batch-id-checkbox" type="checkbox" name="selectedParticipants[]" value="<?php echo htmlspecialchars($participantID); ?>" checked>
                    <div class="batch-id-item-body">
                      <div class="fw-semibold"><?php echo htmlspecialchars($participantID . '. ' . $participantName); ?></div>
                      <div class="small text-muted"><?php echo htmlspecialchars($participantAgency); ?></div>
                    </div>
                  </label>
                <?php } ?>
              </div>
            </div>
          </form>
          <div class="mt-3">
            <?php
            $docxPath = "../generated_ids/training-$trainingID-selected.docx";
            if (file_exists($docxPath)) {
              echo "<div class='alert alert-success py-2 mb-0'>Generated IDs ready: <a href='../generated_ids/training-" . htmlspecialchars($trainingID) . "-selected.docx' download>Download file</a></div>";
            } else {
              echo "<div class='alert alert-info py-2 mb-0'>Choose the participants you want, then generate the printable Word file.</div>";
            }
            ?>
          </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="exampleModalLabel">Delete Training</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete <b><?php echo htmlspecialchars($trainingName); ?></b>? This action cannot be undone.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <a href="../processes/deleteTraining.php?id=<?php echo $trainingID; ?>" class="btn btn-danger">Delete</a>
        </div>
      </div>
    </div>
  </div>
  <script>
    const guestLinkInput = document.getElementById('guestLinkInput');
    const copyGuestLinkBtn = document.getElementById('copyGuestLinkBtn');
    const selectAllIdsBtn = document.getElementById('selectAllIdsBtn');
    const clearAllIdsBtn = document.getElementById('clearAllIdsBtn');
    const participantIdSearch = document.getElementById('participantIdSearch');
    const generateIdsForm = document.getElementById('generateIdsForm');
    const generateIdsOverlay = document.getElementById('generateIdsOverlay');
    const closeGenerateIdsModalBtn = document.getElementById('closeGenerateIdsModalBtn');
    const openGenerateIdsModalBtn = document.getElementById('openGenerateIdsModalBtn');
    if (selectAllIdsBtn) {
      selectAllIdsBtn.addEventListener('click', () => {
        document.querySelectorAll('.batch-id-checkbox').forEach(checkbox => {
          checkbox.checked = true;
        });
      });
    }

    if (clearAllIdsBtn) {
      clearAllIdsBtn.addEventListener('click', () => {
        document.querySelectorAll('.batch-id-checkbox').forEach(checkbox => {
          checkbox.checked = false;
        });
      });
    }

    function filterParticipantIdList(term) {
      const normalizedTerm = term.trim().toLowerCase();
      document.querySelectorAll('.batch-id-item').forEach(item => {
        const haystack = item.dataset.search || '';
        const match = !normalizedTerm || haystack.includes(normalizedTerm);
        item.classList.toggle('d-none', !match);
      });
    }

    if (participantIdSearch) {
      participantIdSearch.addEventListener('input', () => {
        filterParticipantIdList(participantIdSearch.value);
      });
    }

    if (generateIdsForm) {
      generateIdsForm.addEventListener('submit', (event) => {
        const checkedCount = document.querySelectorAll('.batch-id-checkbox:checked').length;
        if (checkedCount === 0) {
          alert('Please select at least one participant.');
          event.preventDefault();
        }
      });
    }

    if (openGenerateIdsModalBtn) {
      openGenerateIdsModalBtn.addEventListener('click', () => {
        if (!generateIdsOverlay || openGenerateIdsModalBtn.disabled) {
          return;
        }
        generateIdsOverlay.classList.add('show');
        generateIdsOverlay.setAttribute('aria-hidden', 'false');
      });
    }

    function closeGenerateIdsOverlay() {
      const activeElement = document.activeElement;
      if (generateIdsOverlay && activeElement && generateIdsOverlay.contains(activeElement) && typeof activeElement.blur === 'function') {
        activeElement.blur();
      }
      if (generateIdsOverlay) {
        generateIdsOverlay.classList.add('closing');
        window.setTimeout(() => {
          generateIdsOverlay.classList.remove('show');
          generateIdsOverlay.classList.remove('closing');
          generateIdsOverlay.setAttribute('aria-hidden', 'true');
        }, 180);
      }
    }

    if (closeGenerateIdsModalBtn) {
      closeGenerateIdsModalBtn.addEventListener('click', closeGenerateIdsOverlay);
    }

    if (generateIdsOverlay) {
      generateIdsOverlay.addEventListener('click', (event) => {
        if (event.target === generateIdsOverlay) {
          closeGenerateIdsOverlay();
        }
      });
    }

    if (guestLinkInput && copyGuestLinkBtn) {
      copyGuestLinkBtn.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(guestLinkInput.value);
        } catch (error) {
          guestLinkInput.select();
          document.execCommand('copy');
        }
        const originalLabel = copyGuestLinkBtn.textContent;
        copyGuestLinkBtn.textContent = 'Copied';
        setTimeout(() => {
          copyGuestLinkBtn.textContent = originalLabel;
        }, 1200);
      });
    }
  </script>
</body>
</html>
