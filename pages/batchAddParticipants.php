<?php
session_start();
include '../processes/db_connection.php';

if (!isset($_SESSION['username'])) {
  header('Location: ../index.php');
  exit();
}

$trainingID = $_GET['id'] ?? '';
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
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Batch Add Participants</title>
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
          <div class="app-kicker mb-1">Batch add</div>
          <h1 class="app-section-title h3 mb-2">Participants</h1>
          <p class="app-muted mb-0">Add multiple participants in one form, then save them all at once.</p>
        </div>
        <div class="app-mini-card">
          <div class="small text-muted">Training days</div>
          <div class="h4 mb-0"><?php echo (int)$trainingDays; ?></div>
        </div>
      </div>

      <form action="../processes/batchAddParticipants.php" method="post" id="batchAddParticipantsForm" class="batch-edit-form">
        <input type="hidden" name="trainingID" value="<?php echo htmlspecialchars($trainingID); ?>">
        <div class="app-toolbar mb-4">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
              <div class="form-label fw-semibold mb-1">Participant rows</div>
              <div class="small text-muted">Fill in the fields for each participant you want to add.</div>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm app-btn-soft" id="addParticipantRowBtn"><i class="fas fa-plus me-1"></i>Add row</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="clearParticipantRowsBtn">Clear rows</button>
            </div>
          </div>
        </div>

        <div class="d-grid gap-3" id="participantRows">
          <?php for ($i = 0; $i < 5; $i++): ?>
            <div class="app-record-card participant-row-card">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                  <div class="app-kicker mb-1">Participant <?php echo $i + 1; ?></div>
                  <div class="small text-muted">Leave a row blank to skip it.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">Remove</button>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold">Last name</label>
                  <input type="text" class="form-control" name="participants[<?php echo $i; ?>][lastname]" placeholder="Dela Cruz">
                </div>
                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold">First name</label>
                  <input type="text" class="form-control" name="participants[<?php echo $i; ?>][firstname]" placeholder="Juan">
                </div>
                <div class="col-12 col-md-2">
                  <label class="form-label fw-semibold">Middle initial</label>
                  <input type="text" class="form-control" name="participants[<?php echo $i; ?>][middle_initial]" placeholder="G.">
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold">Agency</label>
                  <input type="text" class="form-control" name="participants[<?php echo $i; ?>][agency]" placeholder="CSC RO6">
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <div class="d-flex justify-content-end mt-4">
          <button type="submit" class="btn app-btn-primary">Save all participants</button>
        </div>
      </form>
    </div>
  </main>

  <script>
    const participantRows = document.getElementById('participantRows');
    const addParticipantRowBtn = document.getElementById('addParticipantRowBtn');
    const clearParticipantRowsBtn = document.getElementById('clearParticipantRowsBtn');

    function bindRemoveButtons() {
      document.querySelectorAll('.remove-row-btn').forEach(button => {
        button.onclick = () => {
          const row = button.closest('.participant-row-card');
          if (row) {
            row.remove();
          }
        };
      });
    }

    function getNextRowIndex() {
      return document.querySelectorAll('.participant-row-card').length;
    }

    function createRow(index) {
      const wrapper = document.createElement('div');
      wrapper.className = 'app-record-card participant-row-card';
      wrapper.innerHTML = `
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
          <div>
            <div class="app-kicker mb-1">Participant ${index + 1}</div>
            <div class="small text-muted">Leave a row blank to skip it.</div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">Remove</button>
        </div>
        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label class="form-label fw-semibold">Last name</label>
            <input type="text" class="form-control" name="participants[${index}][lastname]" placeholder="Dela Cruz">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label fw-semibold">First name</label>
            <input type="text" class="form-control" name="participants[${index}][firstname]" placeholder="Juan">
          </div>
          <div class="col-12 col-md-2">
            <label class="form-label fw-semibold">Middle initial</label>
            <input type="text" class="form-control" name="participants[${index}][middle_initial]" placeholder="G.">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Agency</label>
            <input type="text" class="form-control" name="participants[${index}][agency]" placeholder="CSC RO6">
          </div>
        </div>
      `;
      return wrapper;
    }

    if (addParticipantRowBtn) {
      addParticipantRowBtn.addEventListener('click', () => {
        const row = createRow(getNextRowIndex());
        participantRows.appendChild(row);
        bindRemoveButtons();
      });
    }

    if (clearParticipantRowsBtn) {
      clearParticipantRowsBtn.addEventListener('click', () => {
        participantRows.innerHTML = '';
        for (let i = 0; i < 5; i++) {
          participantRows.appendChild(createRow(i));
        }
        bindRemoveButtons();
      });
    }

    bindRemoveButtons();
  </script>
</body>
</html>
