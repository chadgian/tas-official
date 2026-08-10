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

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
  $_SESSION['trainingID'] = $_POST['training'];
  $_SESSION['days'] = $_POST['days'];
  $_SESSION['inORout'] = $_POST['inORout'];
}

$trainingID = $_SESSION['trainingID'] ?? null;
$trainingDay = $_SESSION['days'] ?? null;
$trainingInORout = $_SESSION['inORout'] ?? null;

if (!$trainingID || !$trainingDay || !$trainingInORout) {
  header('Location: viewTraining.php?id=' . urlencode($trainingID ?: ''));
  exit();
}

$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');

$trainingTable = "training-$trainingID-$trainingDay";
$getParticipantStmt = $conn->prepare("SELECT * FROM `$trainingTable`");
$attendanceData = [];

if ($getParticipantStmt->execute()) {
  $getParticipantResult = $getParticipantStmt->get_result();
  while ($row = $getParticipantResult->fetch_assoc()) {
    $attendanceData[] = [
      'numID' => $row['participant_id'],
      'name' => trim("{$row['firstname']} {$row['middle_initial']} {$row['lastname']}"),
      'agency' => $row['agency'],
      'timestamp' => '00:00:00'
    ];
  }
}

$stmtTraining = $conn->prepare("SELECT training_name FROM trainings WHERE training_id = ?");
$stmtTraining->bind_param('s', $trainingID);
$stmtTraining->execute();
$trainingRow = $stmtTraining->get_result()->fetch_assoc();
$trainingName = $trainingRow['training_name'] ?? 'Training';
$scanMode = $trainingInORout === 'in' ? 'Login' : 'Logout';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scan Attendance</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
  <script src="<?php echo $assetBase; ?>/script/js-bootstrap/bootstrap.bundle.min.js"></script>
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
        <a class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" href="viewTraining.php?id=<?php echo urlencode($trainingID); ?>" aria-label="Back to training" title="Back to training">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M12.5 8a.5.5 0 0 1-.5.5H4.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L4.707 7.5H12a.5.5 0 0 1 .5.5z"/>
          </svg>
        </a>
      </div>
    </div>
  </header>

  <main class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="app-panel p-4 p-lg-5">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-4">
            <div>
              <div class="app-kicker mb-2">Scan attendance</div>
              <h1 class="app-section-title mb-2"><?php echo htmlspecialchars($trainingName); ?></h1>
              <p class="app-muted mb-0">Day <?php echo htmlspecialchars($trainingDay); ?> - <?php echo htmlspecialchars($scanMode); ?></p>
            </div>
            <div class="app-section-grid" style="min-width:min(100%, 420px);">
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Training ID</div>
                <div class="h5 mb-0"><?php echo htmlspecialchars($trainingID); ?></div>
              </div>
              <div class="app-mini-card">
                <div class="app-kicker mb-1">Mode</div>
                <div class="h5 mb-0"><?php echo htmlspecialchars($scanMode); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="app-panel p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <div class="app-kicker">Scanner</div>
              <h2 class="h4 mb-0">QR capture area</h2>
            </div>
            <button type="button" onclick="scanAgain()" id="again-btn" style="display:none;" class="btn app-btn-soft">
              <i class="fas fa-redo me-2"></i>Scan again
            </button>
          </div>
          <div class="app-mini-card mb-3 text-center">
            <h3 id="status" class="mb-0">Ready to scan</h3>
          </div>
          <div class="app-mini-card">
            <div id="my-qr-reader"></div>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="app-panel p-4 h-100">
          <div class="app-kicker mb-2">Queue</div>
          <h2 class="h4 mb-2">Recorded locally</h2>
          <p class="app-muted mb-3">Scans are kept here first, then you can push them to the server.</p>
          <div class="app-mini-card mb-3">
            <div class="small text-muted">Total queued</div>
            <div class="h3 mb-0" id="queuedCount">0</div>
          </div>
          <div class="d-grid gap-2 mb-3">
            <button type="button" onclick="clearData()" class="btn btn-outline-danger">Clear queue</button>
            <button type="button" onclick="saveData()" class="btn app-btn-primary">Save to server</button>
          </div>
          <div class="app-mini-card">
            <div class="app-kicker mb-2">Queued records</div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody id="recorded-body">
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <div class="modal fade" id="statusModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog d-flex justify-content-center align-items-center modal-dialog-centered">
      <div class="app-mini-card text-center p-4">
        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
        <p class="mb-0">Processing attendance...</p>
        <div id="testing"></div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="resultModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="resultModalLabel">Attendance recorded</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body bg-white d-flex flex-column justify-content-center align-items-center">
          <div id="paxName" class="h3 mb-4 text-center"></div>
          <img src="img/checked.png" alt="Success" width="100" class="mb-4">
          <div id="paxAgency" class="text-muted mb-3"></div>
          <div id="scanTime" class="text-muted small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="scanAgain()">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../script/html5-qrcode.min.js"></script>
  <script src="../script/crypto-js.min.js"></script>
  <script src="../script/jquery-3.6.0.min.js"></script>
  <script>
    const attendanceTrainingID = <?php echo json_encode($trainingID); ?>;
    const participants = <?php echo json_encode($attendanceData); ?>;
    const username = <?php echo json_encode($_SESSION['username']); ?>;
    const currentDate = <?php echo json_encode((new DateTime())->format('Y-m-d')); ?>;
    const currentTime = <?php echo json_encode((new DateTime())->format('H:i:s')); ?>;

    let qrCodeScanned = false;
    let previousData = "";
    let recordedData = [];

    const existingData = localStorage.getItem("attendance-<?php echo $trainingID; ?>-<?php echo $trainingInORout; ?>-<?php echo $trainingDay; ?>");
    if (existingData) {
      try {
        recordedData = JSON.parse(existingData);
      } catch (error) {
        recordedData = [];
      }
    }

    function renderQueue() {
      const tbody = document.getElementById('recorded-body');
      tbody.innerHTML = '';
      document.getElementById('queuedCount').textContent = recordedData.length;
      if (!recordedData.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No queued records yet.</td></tr>';
        return;
      }
      recordedData.forEach(participant => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${participant.numID}</td><td>${participant.name}</td><td>${participant.timestamp}</td>`;
        tbody.appendChild(row);
      });
    }

    renderQueue();

    function addAttendance(numID) {
      const participant = participants.find(p => p.numID === parseInt(numID, 10));
      if (!participant) {
        return;
      }
      const now = new Date();
      const timestamp = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
      saveRecordedData(participant.numID, participant.name, timestamp);
    }

    function saveRecordedData(id, name, timestamp) {
      recordedData = recordedData.filter(item => item.numID !== parseInt(id, 10));
      recordedData.unshift({ numID: id, name: name, timestamp: timestamp });
      localStorage.setItem("attendance-<?php echo $trainingID; ?>-<?php echo $trainingInORout; ?>-<?php echo $trainingDay; ?>", JSON.stringify(recordedData));
      renderQueue();
    }

    function saveData() {
      const trainingID = <?php echo json_encode($trainingID); ?>;
      const trainingDay = <?php echo json_encode($trainingDay); ?>;
      const inorout = <?php echo json_encode($trainingInORout); ?>;

      $.ajax({
        url: '../processes/saveRecordedData.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          inorout: inorout,
          trainingID: trainingID,
          day: trainingDay,
          participants: recordedData
        }),
        success: function () {
          recordedData = [];
          localStorage.setItem("attendance-<?php echo $trainingID; ?>-<?php echo $trainingInORout; ?>-<?php echo $trainingDay; ?>", JSON.stringify(recordedData));
          renderQueue();
        }
      });
    }

    function clearData() {
      if (confirm("Are you sure you want to DELETE ALL DATA?")) {
        recordedData = [];
        localStorage.setItem("attendance-<?php echo $trainingID; ?>-<?php echo $trainingInORout; ?>-<?php echo $trainingDay; ?>", JSON.stringify(recordedData));
        renderQueue();
      }
    }

    function scanAgain() {
      if (window.htmlscanner) {
        htmlscanner.resume();
      }
      const againBtn = document.getElementById('again-btn');
      if (againBtn) againBtn.style.display = 'none';
    }

    function setStatus(status) {
      const statusElement = document.getElementById('status');
      switch (status) {
        case "ready":
          statusElement.innerHTML = 'Ready to scan';
          break;
        case "decrypting":
          statusElement.innerHTML = 'Checking QR code...';
          break;
        case "recording":
          statusElement.innerHTML = 'Recording attendance...';
          break;
        default:
          statusElement.innerHTML = 'Error. Refresh the page.';
      }
    }

    setStatus("ready");

    function addRow(id, name, timestamp) {
      const tbody = document.querySelector('#recorded-body');
      if (!tbody) return;
      const newRow = document.createElement('tr');
      newRow.innerHTML = `<td>${id}</td><td>${name}</td><td>${timestamp}</td>`;
      tbody.prepend(newRow);
    }

    function domReady(fn) {
      if (document.readyState === "complete" || document.readyState === "interactive") {
        setTimeout(fn, 500);
      } else {
        document.addEventListener("DOMContentLoaded", fn);
      }
    }

    var htmlscanner = new Html5QrcodeScanner("my-qr-reader", { fps: 20, qrbos: 250 });
    window.htmlscanner = htmlscanner;

    domReady(function () {
      async function onScanSuccess(decodeText) {
        if (qrCodeScanned) return;
        qrCodeScanned = true;
        if (previousData === decodeText) {
          qrCodeScanned = false;
          return;
        }
        previousData = decodeText;
        setStatus("decrypting");
        addAttendance(decodeText.split("::")[1]);
        setStatus("ready");
        qrCodeScanned = false;
      }
      htmlscanner.render(onScanSuccess);
    });
  </script>
</body>
</html>
