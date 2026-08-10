<?php
session_start();
include '../processes/db_connection.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['training'], $_POST['days'], $_POST['inORout'])) {
  $_SESSION['training'] = $_POST['training'];
  $_SESSION['day_number'] = (int) $_POST['days'];
  $_SESSION['attendance_type'] = ((string) $_POST['inORout'] === 'out')
    ? "day{$_SESSION['day_number']}logout"
    : "day{$_SESSION['day_number']}login";
}

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '') {
  $refererPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?: '';
  if (str_contains($refererPath, '/pages/viewTraining.php')) {
    $_SESSION['scanner_return_url'] = $_SERVER['HTTP_REFERER'];
  } elseif (str_contains($refererPath, '/attendance/guest.php')) {
    $_SESSION['scanner_return_url'] = $_SERVER['HTTP_REFERER'];
  } elseif (str_contains($refererPath, '/attendance/login.php') || str_contains($refererPath, '/attendance/admin/login.php')) {
    $_SESSION['scanner_return_url'] = $_SERVER['HTTP_REFERER'];
  }
}

$isGuest = isset($_SESSION['guest_training_id'], $_SESSION['guest_day'], $_SESSION['guest_mode']);
$isUser = isset($_SESSION['training'], $_SESSION['attendance_type']);

if (!$isGuest && !$isUser) {
  header('Location: login.php');
  exit();
}

if ($isGuest) {
  $trainingID = $_SESSION['guest_training_id'];
  $trainingDay = (int) $_SESSION['guest_day'];
  $trainingMode = $_SESSION['guest_mode'];
  $token = $_SESSION['guest_token'] ?? '';
} else {
  $trainingID = $_SESSION['training'];
  $trainingType = $_SESSION['attendance_type'];
  $trainingDay = (int) ($_SESSION['day_number'] ?? substr($trainingType, 3, 1));
  $trainingMode = (strpos($trainingType, 'login') !== false) ? 'in' : 'out';
  $token = '';
}

$stmtTraining = $conn->prepare("SELECT training_name FROM trainings WHERE training_id = ? LIMIT 1");
$stmtTraining->bind_param('s', $trainingID);
$stmtTraining->execute();
$trainingRow = $stmtTraining->get_result()->fetch_assoc();
$trainingName = $trainingRow['training_name'] ?? 'Training';

$participantHistory = [];
for ($dayIndex = 1; $dayIndex <= (int)$trainingDay; $dayIndex++) {
  $tableName = "training-$trainingID-$dayIndex";
  $dayStmt = $conn->prepare("SELECT participant_id, login, logout FROM `$tableName`");
  if ($dayStmt && $dayStmt->execute()) {
    $dayResult = $dayStmt->get_result();
    while ($row = $dayResult->fetch_assoc()) {
      $participantID = (string)$row['participant_id'];
      if (!isset($participantHistory[$participantID])) {
        $participantHistory[$participantID] = [];
      }
      $participantHistory[$participantID][$dayIndex] = [
        'login' => $row['login'] ?? '',
        'logout' => $row['logout'] ?? '',
      ];
    }
    $dayStmt->close();
  }
}

$attendanceTable = "training-$trainingID-$trainingDay";
$stmt = $conn->prepare("SELECT * FROM `$attendanceTable`");
$stmt->execute();
$result = $stmt->get_result();
$participants = [];
while ($row = $result->fetch_assoc()) {
  $participants[] = [
    "id" => $row['participant_id'],
    "name" => trim("{$row['firstname']} {$row['middle_initial']} {$row['lastname']}"),
    "agency" => $row['agency']
  ];
}

$modeLabel = $trainingMode === 'in' ? 'Login' : 'Logout';
$storagePrefix = $isGuest
  ? "guest-attendance-$trainingID-$trainingMode-$trainingDay"
  : "attendance-$trainingID-$trainingMode-$trainingDay";
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
$returnUrl = $_SESSION['scanner_return_url'] ?? ($isGuest ? 'guest.php' : 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scanner</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="<?php echo $assetBase; ?>/script/jquery-3.6.0.min.js"></script>
  <script src="<?php echo $assetBase; ?>/script/js-bootstrap/bootstrap.bundle.min.js"></script>
</head>
<body class="app-shell">
  <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 1080;">
    <div id="scan-toast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="status" aria-live="polite" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="scan-toast-text">Scan successful</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between">
      <div class="brand-lockup">
        <img src="<?php echo $assetBase; ?>/src/img/csc-logo.png" alt="CSC Logo" width="48" height="48" class="rounded-3">
        <div>
          <div class="app-kicker text-white-50">Training Attendance System</div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="offcanvas" data-bs-target="#syncedParticipantsDrawer" aria-controls="syncedParticipantsDrawer">
          <i class="fas fa-list-check me-2"></i>Synced
        </button>
        <a class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" href="<?php echo htmlspecialchars($returnUrl); ?>" aria-label="Back" title="Back">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M12.5 8a.5.5 0 0 1-.5.5H4.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L4.707 7.5H12a.5.5 0 0 1 .5.5z"/>
          </svg>
        </a>
      </div>
    </div>
  </header>

  <main class="container-fluid py-3 py-lg-4">
    <div class="app-panel p-3 p-lg-4 mx-auto" style="max-width: 980px;">
      <div class="app-mini-card mb-3 py-2 text-center">
        <div class="small text-muted">
          <?php echo htmlspecialchars($trainingName); ?> · Day <?php echo (int)$trainingDay; ?> · <?php echo htmlspecialchars($modeLabel); ?>
        </div>
      </div>

      <div class="row g-3 justify-content-center">
        <div class="col-xl-10">
            <div class="app-mini-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="app-kicker">Scanner</div>
            </div>
            <div class="app-mini-card mb-3 text-center">
              <h3 id="status" class="mb-0">Ready to scan</h3>
            </div>
            <div class="app-mini-card scanner-frame">
              <div class="scanner-camera-frame">
                <div id="reader"></div>
              </div>
            </div>
            <div class="app-mini-card mt-3">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                <div>
                  <div class="small text-muted">Queued locally</div>
                  <div class="h3 mb-0" id="queuedCount">0</div>
                </div>
              </div>
              <div class="d-grid gap-2 mb-3">
                <button type="button" id="autoSaveBtn" class="btn btn-outline-primary w-100">
                  <i class="fas fa-toggle-off me-2" aria-hidden="true"></i>Auto Save: Off
                </button>
              </div>
              <button type="button" onclick="saveData()" class="btn btn-outline-success w-100 mb-3">
                <i class="fas fa-cloud-upload-alt me-2"></i>Save to server
              </button>
              <div id="queuedParticipantsList" class="app-card-list"></div>
              <div class="mt-3 pt-3 border-top">
                <button type="button" onclick="clearData()" class="btn btn-outline-danger w-100">Clear Data</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <div class="offcanvas offcanvas-end app-sidebar" tabindex="-1" id="syncedParticipantsDrawer" aria-labelledby="syncedParticipantsDrawerLabel">
    <div class="offcanvas-header border-bottom">
      <div>
        <div class="app-kicker mb-1">Synced participants</div>
        <h5 class="offcanvas-title mb-0" id="syncedParticipantsDrawerLabel"><?php echo htmlspecialchars($trainingName); ?></h5>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="app-mini-card mb-3">
        <div class="small text-muted mb-1">Training ID</div>
        <div class="fw-semibold"><?php echo htmlspecialchars($trainingID); ?></div>
        <div class="small text-muted mt-3 mb-1">Mode</div>
        <div class="fw-semibold"><?php echo htmlspecialchars($modeLabel); ?></div>
      </div>
      <div class="app-mini-card mb-3">
        <div class="small text-muted">Synced to server</div>
        <div class="h3 mb-0" id="drawerQueuedCount">0</div>
      </div>
      <div id="syncedParticipantsList" class="app-card-list"></div>
    </div>
  </div>

  <div class="modal fade" id="missingSessionModal" tabindex="-1" aria-labelledby="missingSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <div class="app-kicker mb-1">Missing sessions</div>
            <h1 class="modal-title fs-5 mb-0" id="missingSessionModalLabel">Complete unscanned attendance</h1>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-3" id="missingSessionParticipantLabel"></p>
          <div id="missingSessionList" class="d-grid gap-3"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn app-btn-primary" id="confirmMissingSessionsBtn">Save attendance</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../script/html5-qrcode.min.js"></script>
  <script>
    const attendanceTrainingID = <?php echo json_encode($trainingID); ?>;
    const participants = <?php echo json_encode($participants); ?>;
    const trainingDay = <?php echo json_encode($trainingDay); ?>;
    const trainingMode = <?php echo json_encode($trainingMode); ?>;
    const storageKey = <?php echo json_encode($storagePrefix); ?>;
    const isGuest = <?php echo json_encode($isGuest); ?>;
    const guestToken = <?php echo json_encode($token); ?>;
    const autoSaveKey = `${storageKey}-auto-save`;
    const participantHistory = <?php echo json_encode($participantHistory); ?>;
    let recordedData = [];
    let syncedData = [];
    let previousData = '';
    let qrCodeScanned = false;
    let autoSaveEnabled = localStorage.getItem(autoSaveKey) === '1';
    let pendingParticipant = null;
    let pendingSessions = [];

    const existingData = localStorage.getItem(storageKey);
    if (existingData) {
      try { recordedData = JSON.parse(existingData); } catch (e) { recordedData = []; }
    }

    function renderQueue() {
      const localList = document.getElementById('queuedParticipantsList');
      const syncedList = document.getElementById('syncedParticipantsList');
      const queuedCount = document.getElementById('queuedCount');
      const drawerQueuedCount = document.getElementById('drawerQueuedCount');
      const autoSaveBtn = document.getElementById('autoSaveBtn');
      if (queuedCount) queuedCount.textContent = recordedData.length;
      if (drawerQueuedCount) drawerQueuedCount.textContent = syncedData.length;
      if (autoSaveBtn) {
        autoSaveBtn.className = autoSaveEnabled ? 'btn btn-success w-100' : 'btn btn-outline-primary w-100';
        autoSaveBtn.innerHTML = autoSaveEnabled
          ? '<i class="fas fa-toggle-on me-2" aria-hidden="true"></i>Auto Save: On'
          : '<i class="fas fa-toggle-off me-2" aria-hidden="true"></i>Auto Save: Off';
      }
      renderParticipantList(localList, recordedData, 'No queued participants yet.', false);
      renderParticipantList(syncedList, syncedData, 'No synced participants yet.', true);
    }

    function renderParticipantList(container, items, emptyMessage, showTrainingInfo) {
      if (!container) return;
      container.innerHTML = '';
      if (!items.length) {
        container.innerHTML = `<div class="app-record-card text-center text-muted py-4">${emptyMessage}</div>`;
        return;
      }
      items.forEach(participant => {
        const card = document.createElement('div');
        card.className = 'app-record-card';
        card.innerHTML = `
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
              <div class="fw-semibold mb-1">${participant.name}</div>
              <div class="small text-muted">Participant ID: ${participant.numID}</div>
              ${showTrainingInfo ? `<div class="small text-muted mt-1">Training ID: ${attendanceTrainingID}</div>` : ''}
              ${participant.source === 'manual' ? `<div class="mt-2"><span class="badge text-bg-warning text-dark">${participant.label || 'Manually encoded'}</span></div>` : ''}
            </div>
            <div class="text-end">
              <div class="badge text-bg-light border text-dark">${participant.timestamp}</div>
            </div>
          </div>
        `;
        container.appendChild(card);
      });
    }
    renderQueue();

    function setStatus(text) { document.getElementById('status').textContent = text; }

    function showScanSuccess(name) {
      const toastEl = document.getElementById('scan-toast');
      const toastText = document.getElementById('scan-toast-text');
      if (toastText) {
        toastText.textContent = name ? `Scanned: ${name}` : 'Scan successful';
      }
      if (toastEl && window.bootstrap && bootstrap.Toast) {
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { autohide: true, delay: 4000 });
        toast.show();
      }
      if (navigator.vibrate) {
        navigator.vibrate([60, 40, 60]);
      }
    }

    function persistRecordedData() {
      localStorage.setItem(storageKey, JSON.stringify(recordedData));
      renderQueue();
    }

    function queueParticipant(participant) {
      const participantKey = `${parseInt(participant.numID, 10)}-${participant.day || trainingDay}-${participant.mode || trainingMode}`;
      recordedData = recordedData.filter(item => {
        const itemKey = `${parseInt(item.numID, 10)}-${item.day || trainingDay}-${item.mode || trainingMode}`;
        return itemKey !== participantKey;
      });
      recordedData.unshift(participant);
      persistRecordedData();
    }

    function markSynced(participant) {
      syncedData = syncedData.filter(item => item.numID !== parseInt(participant.numID, 10));
      syncedData.unshift(participant);
      renderQueue();
    }

    function saveToServer(participant) {
      const payload = isGuest
        ? { trainingID: attendanceTrainingID, day: participant.day || trainingDay, mode: participant.mode || trainingMode, participants: [participant] }
        : { inorout: participant.mode || trainingMode, trainingID: attendanceTrainingID, day: participant.day || trainingDay, participants: [participant] };

      return $.ajax({
        url: isGuest ? 'guestProcessAttendance.php' : '../processes/saveRecordedData.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload)
      });
    }

    function saveRecordedData(id, name, timestamp, day = trainingDay, mode = trainingMode) {
      const participant = { numID: id, name: name, timestamp: timestamp, day: day, mode: mode, source: 'current' };
      queueParticipant(participant);
      if (autoSaveEnabled && navigator.onLine) {
        window.setTimeout(() => saveData(true), 0);
      }
    }

    function clearData() {
      const confirmation = prompt('Type "Clear Data" to confirm clearing the queue.');
      if (confirmation === 'Clear Data' && confirm('This will permanently clear the local queue. Click Yes to confirm.')) {
        recordedData = [];
        localStorage.setItem(storageKey, JSON.stringify(recordedData));
        renderQueue();
        return;
      }
      if (confirmation !== null) {
        alert('Queue was not cleared. Confirmation text did not match.');
      }
    }

    function saveData(silent) {
      if (!silent && !confirm('Save the queued scans to the server now?')) {
        return;
      }
      if (!recordedData.length) {
        return;
      }
      const grouped = {};
      recordedData.forEach(item => {
        const day = item.day || trainingDay;
        const mode = item.mode || trainingMode;
        const key = `${day}-${mode}`;
        if (!grouped[key]) {
          grouped[key] = { day, mode, participants: [] };
        }
        grouped[key].participants.push(item);
      });

      const requests = Object.values(grouped).map(group => $.ajax({
        url: isGuest ? 'guestProcessAttendance.php' : '../processes/saveRecordedData.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(
          isGuest
            ? { trainingID: attendanceTrainingID, day: group.day, mode: group.mode, participants: group.participants }
            : { inorout: group.mode, trainingID: attendanceTrainingID, day: group.day, participants: group.participants }
        )
      }));

      $.when.apply($, requests).done(() => {
        syncedData = recordedData.concat(syncedData);
        recordedData = [];
        localStorage.setItem(storageKey, JSON.stringify(recordedData));
        renderQueue();
      });
    }

    function addAttendance(numID) {
      const participant = participants.find(p => p.id === parseInt(numID, 10));
      if (!participant) return;
      const now = new Date();
      const timestamp = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
      saveRecordedData(participant.id, participant.name, timestamp);
    }

    const autoSaveBtn = document.getElementById('autoSaveBtn');
    if (autoSaveBtn) {
      autoSaveBtn.addEventListener('click', () => {
        autoSaveEnabled = !autoSaveEnabled;
        localStorage.setItem(autoSaveKey, autoSaveEnabled ? '1' : '0');
        renderQueue();
        if (autoSaveEnabled && navigator.onLine) {
          saveData(true);
        }
      });
    }

    const missingSessionModalEl = document.getElementById('missingSessionModal');
    const missingSessionParticipantLabel = document.getElementById('missingSessionParticipantLabel');
    const missingSessionList = document.getElementById('missingSessionList');
    const confirmMissingSessionsBtn = document.getElementById('confirmMissingSessionsBtn');
    const missingSessionModal = missingSessionModalEl && window.bootstrap && bootstrap.Modal
      ? bootstrap.Modal.getOrCreateInstance(missingSessionModalEl)
      : null;

    function getParticipantHistory(participantID) {
      return participantHistory[String(participantID)] || {};
    }

    function buildMissingSessions(participantID, currentMode) {
      const history = getParticipantHistory(participantID);
      const missing = [];
      const currentDay = Number(trainingDay);
      const isCurrentLogin = currentMode === 'in';

      for (let dayIndex = 1; dayIndex < currentDay; dayIndex++) {
        const session = history[dayIndex] || { login: '', logout: '' };
        if (!session.login) {
          missing.push({ day: dayIndex, mode: 'in', label: `Day ${dayIndex} Login` });
        }
        if (!session.logout) {
          missing.push({ day: dayIndex, mode: 'out', label: `Day ${dayIndex} Logout` });
        }
      }

      if (currentDay > 0) {
        const currentSession = history[currentDay] || { login: '', logout: '' };
        if (!isCurrentLogin && !currentSession.login) {
          missing.push({ day: currentDay, mode: 'in', label: `Day ${currentDay} Login` });
        }
      }
      return missing;
    }

    function openMissingSessionModal(participant, missingSessions, currentRecord) {
      pendingParticipant = participant;
      pendingSessions = missingSessions;
      if (!missingSessionModal || !missingSessionList || !missingSessionParticipantLabel) {
        return false;
      }

      missingSessionParticipantLabel.textContent = `${participant.name} (${participant.id})`;
      missingSessionList.innerHTML = '';

      missingSessions.forEach((session, index) => {
        const card = document.createElement('div');
        card.className = 'app-record-card';
        card.innerHTML = `
          <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
              <div class="fw-semibold">${session.label}</div>
              <div class="small text-muted">Participant will be saved after you confirm the missing time.</div>
            </div>
            <span class="badge text-bg-light border text-dark">${session.mode === 'in' ? 'Login' : 'Logout'}</span>
          </div>
          <input type="time" step="1" class="form-control missing-session-time" data-index="${index}" value="">
        `;
        missingSessionList.appendChild(card);
      });

      if (currentRecord) {
        queueParticipant(currentRecord);
      }

      confirmMissingSessionsBtn.disabled = false;
      missingSessionModal.show();
      return true;
    }

    if (missingSessionModalEl) {
      missingSessionModalEl.addEventListener('hidden.bs.modal', () => {
        pendingParticipant = null;
        pendingSessions = [];
        qrCodeScanned = false;
        setStatus('Ready to scan');
      });
    }

    if (confirmMissingSessionsBtn) {
      confirmMissingSessionsBtn.addEventListener('click', () => {
        if (!pendingParticipant || !pendingSessions.length) {
          return;
        }
        const inputs = missingSessionList ? Array.from(missingSessionList.querySelectorAll('.missing-session-time')) : [];
        pendingSessions.forEach((session, index) => {
          const input = inputs[index];
          const timestamp = input && input.value ? `${input.value.length === 5 ? input.value + ':00' : input.value}` : '';
          if (!timestamp) {
            return;
          }
          queueParticipant({
            numID: pendingParticipant.id,
            name: pendingParticipant.name,
            timestamp: timestamp,
            day: session.day,
            mode: session.mode,
            source: 'manual',
            label: session.label
          });
        });
        pendingParticipant = null;
        pendingSessions = [];
        if (missingSessionModal) {
          missingSessionModal.hide();
        }
        if (autoSaveEnabled && navigator.onLine) {
          saveData(true);
        }
        qrCodeScanned = false;
      });
    }

    const scanner = new Html5QrcodeScanner("reader", {
      fps: 10,
      qrbox: (w, h) => {
        const minEdge = Math.min(w, h);
        return { width: minEdge * 0.8, height: minEdge * 0.8 };
      },
      supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
      rememberLastUsedCamera: true,
    });

    function onScanSuccess(decodedText) {
      if (qrCodeScanned) return;
      qrCodeScanned = true;
      if (previousData === decodedText) {
        qrCodeScanned = false;
        return;
      }
      previousData = decodedText;

      const parts = decodedText.split('::');
      const scannedTrainingID = parts[0];
      const participantId = parts[1];
      const participant = participants.find(p => p.id === parseInt(participantId, 10));
      const now = new Date();
      const timestamp = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
      const currentRecord = {
        numID: parseInt(participantId, 10),
        name: participant ? participant.name : `Participant ${participantId}`,
        timestamp: timestamp,
        day: trainingDay,
        mode: trainingMode,
        source: 'current'
      };

      if (scannedTrainingID !== String(attendanceTrainingID)) {
        alert('Wrong training!');
        setStatus('Ready to scan');
        qrCodeScanned = false;
        return;
      }

      if (!participantId) {
        setStatus('Invalid QR code');
        qrCodeScanned = false;
        return;
      }

      const missingSessions = buildMissingSessions(participantId, trainingMode);
      if (missingSessions.length > 0) {
        setStatus('Missing session detected...');
        const opened = openMissingSessionModal({
          id: parseInt(participantId, 10),
          name: participant ? participant.name : `Participant ${participantId}`,
          agency: participant ? participant.agency : ''
        }, missingSessions, currentRecord);
        if (!opened) {
          saveRecordedData(currentRecord.numID, currentRecord.name, currentRecord.timestamp, currentRecord.day, currentRecord.mode);
        }
      } else {
        setStatus('Recording locally...');
        saveRecordedData(currentRecord.numID, currentRecord.name, currentRecord.timestamp, currentRecord.day, currentRecord.mode);
        showScanSuccess(participant ? participant.name : '');
        qrCodeScanned = false;
        return;
      }
      setStatus('Ready to scan');
    }

    scanner.render(onScanSuccess);
  </script>
</body>
</html>
