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
$participantTable = "training-$trainingID-1";
$stmtParticipants = $conn->prepare("SELECT participant_id, lastname, firstname, middle_initial, agency FROM `$participantTable` ORDER BY participant_id ASC");
$stmtParticipants->execute();
$participants = $stmtParticipants->get_result()->fetch_all(MYSQLI_ASSOC);
$participantCount = count($participants);
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Batch Edit Participants</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="<?php echo $assetBase; ?>/script/js-bootstrap/bootstrap.min.js"></script>
</head>
<body class="app-shell">
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between">
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
          <h1 class="app-section-title h3 mb-2">Participants</h1>
          <p class="app-muted mb-0">Edit participant details once and apply them to every day of this training.</p>
        </div>
        <div class="app-mini-card">
          <div class="small text-muted">Training days</div>
          <div class="h4 mb-0"><?php echo (int)$trainingDays; ?></div>
        </div>
      </div>

      <form action="../processes/batchUpdateParticipants.php" method="post" class="batch-edit-form" id="batchEditParticipantsForm">
        <input type="hidden" name="trainingID" value="<?php echo htmlspecialchars($trainingID); ?>">
        <div class="app-toolbar mb-4 batch-search-toolbar">
          <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
            <label for="participantSearch" class="form-label fw-semibold mb-0">Search participants</label>
            <div class="small text-muted" id="searchCount">Showing <?php echo (int)$participantCount; ?> participants</div>
          </div>
          <div class="batch-search-group">
            <span class="batch-search-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
            <input type="search" id="participantSearch" class="form-control batch-search-input" placeholder="Type a name, ID, or agency">
            <button type="button" class="btn btn-outline-secondary batch-search-clear" id="clearSearchBtn">Clear</button>
          </div>
        </div>
        <div class="table-responsive d-none d-md-block">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width: 90px;">ID</th>
                <th>Last name</th>
                <th>First name</th>
                <th>Middle initial</th>
                <th>Agency</th>
                <th style="width: 120px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($participants as $p): ?>
                <tr class="participant-row" data-participant-id="<?php echo htmlspecialchars($p['participant_id']); ?>" data-search="<?php echo htmlspecialchars(strtolower($p['participant_id'] . ' ' . $p['lastname'] . ' ' . $p['firstname'] . ' ' . $p['middle_initial'] . ' ' . $p['agency'])); ?>">
                  <td class="fw-semibold"><?php echo htmlspecialchars($p['participant_id']); ?></td>
                  <td><input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][lastname]" value="<?php echo htmlspecialchars($p['lastname']); ?>"></td>
                  <td><input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][firstname]" value="<?php echo htmlspecialchars($p['firstname']); ?>"></td>
                  <td><input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][middle_initial]" value="<?php echo htmlspecialchars($p['middle_initial']); ?>"></td>
                  <td><input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][agency]" value="<?php echo htmlspecialchars($p['agency']); ?>"></td>
                  <td>
                    <button type="button" class="btn btn-outline-danger btn-sm participant-delete-btn" data-participant-id="<?php echo htmlspecialchars($p['participant_id']); ?>" data-participant-name="<?php echo htmlspecialchars($p['lastname'] . ', ' . $p['firstname']); ?>">Delete</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="d-grid gap-3 d-md-none">
          <?php foreach ($participants as $p): ?>
            <div class="app-record-card participant-card" data-participant-id="<?php echo htmlspecialchars($p['participant_id']); ?>" data-search="<?php echo htmlspecialchars(strtolower($p['participant_id'] . ' ' . $p['lastname'] . ' ' . $p['firstname'] . ' ' . $p['middle_initial'] . ' ' . $p['agency'])); ?>">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                  <div class="app-kicker mb-1">Participant ID</div>
                  <div class="h5 mb-0"><?php echo htmlspecialchars($p['participant_id']); ?></div>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-semibold">Last name</label>
                  <input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][lastname]" value="<?php echo htmlspecialchars($p['lastname']); ?>">
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold">First name</label>
                  <input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][firstname]" value="<?php echo htmlspecialchars($p['firstname']); ?>">
                </div>
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold">Middle initial</label>
                  <input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][middle_initial]" value="<?php echo htmlspecialchars($p['middle_initial']); ?>">
                </div>
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold">Agency</label>
                  <input class="form-control" name="participants[<?php echo htmlspecialchars($p['participant_id']); ?>][agency]" value="<?php echo htmlspecialchars($p['agency']); ?>">
                </div>
                <div class="col-12">
                  <button type="button" class="btn btn-outline-danger w-100 participant-delete-btn" data-participant-id="<?php echo htmlspecialchars($p['participant_id']); ?>" data-participant-name="<?php echo htmlspecialchars($p['lastname'] . ', ' . $p['firstname']); ?>">Delete participant</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="app-record-card text-center text-muted py-4 d-none" id="noResultsMessage">
          No participants match your search.
        </div>
      </form>
    </div>
  </main>
  <div class="batch-save-float">
    <button type="submit" form="batchEditParticipantsForm" class="btn app-btn-primary">
      <i class="fas fa-save me-1"></i>Save all participants
    </button>
  </div>

  <form id="deleteParticipantForm" action="../processes/deleteParticipant.php" method="post" class="d-none">
    <input type="hidden" name="trainingID" value="<?php echo htmlspecialchars($trainingID); ?>">
    <input type="hidden" name="participantID" id="deleteParticipantID">
  </form>

  <div class="modal fade" id="deleteParticipantModal" tabindex="-1" aria-labelledby="deleteParticipantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="deleteParticipantModalLabel">Delete participant</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">To confirm deletion, type <b>delete</b> below.</p>
          <p class="text-muted small mb-3" id="deleteParticipantPrompt"></p>
          <input type="text" class="form-control" id="deleteParticipantConfirmInput" placeholder="Type delete to confirm">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteParticipantBtn" disabled>Yes, delete</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const participantSearch = document.getElementById('participantSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const participantGroups = new Map();
    const searchCount = document.getElementById('searchCount');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const participantCount = <?php echo (int)$participantCount; ?>;
    const desktopParticipantTable = document.querySelector('.table-responsive.d-none.d-md-block');
    const mobileParticipantList = document.querySelector('.d-grid.gap-3.d-md-none');
    const deleteParticipantModalEl = document.getElementById('deleteParticipantModal');
    const deleteParticipantPrompt = document.getElementById('deleteParticipantPrompt');
    const deleteParticipantConfirmInput = document.getElementById('deleteParticipantConfirmInput');
    const confirmDeleteParticipantBtn = document.getElementById('confirmDeleteParticipantBtn');
    const deleteParticipantID = document.getElementById('deleteParticipantID');
    const deleteParticipantForm = document.getElementById('deleteParticipantForm');
    let deleteParticipantModal = null;
    let pendingDeleteParticipantName = '';

    function syncParticipantFieldVisibility() {
      const isDesktop = window.matchMedia('(min-width: 768px)').matches;

      if (desktopParticipantTable) {
        desktopParticipantTable.querySelectorAll('input').forEach(input => {
          input.disabled = !isDesktop;
        });
      }

      if (mobileParticipantList) {
        mobileParticipantList.querySelectorAll('input').forEach(input => {
          input.disabled = isDesktop;
        });
      }
    }

    document.querySelectorAll('.participant-row, .participant-card').forEach(item => {
      const participantId = item.dataset.participantId || item.dataset.search || '';
      if (!participantGroups.has(participantId)) {
        participantGroups.set(participantId, []);
      }
      participantGroups.get(participantId).push(item);
    });

    function updateSearchState(term) {
      let visibleCount = 0;
      participantGroups.forEach(items => {
        const haystack = (items[0] && items[0].dataset.search) ? items[0].dataset.search : '';
        const match = haystack.includes(term);
        items.forEach(item => {
          item.style.display = match ? '' : 'none';
        });
        if (match) {
          visibleCount++;
        }
      });
      if (searchCount) {
        searchCount.textContent = term ? `Showing ${visibleCount} participant${visibleCount === 1 ? '' : 's'}` : `Showing <?php echo count($participants); ?> participants`;
      }
      if (noResultsMessage) {
        noResultsMessage.classList.toggle('d-none', visibleCount !== 0 || !term);
      }
    }

    if (participantSearch) {
      participantSearch.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        updateSearchState(term);
      });
    }

    if (clearSearchBtn && participantSearch) {
      clearSearchBtn.addEventListener('click', () => {
        participantSearch.value = '';
        participantSearch.focus();
        updateSearchState('');
      });
    }

    if (deleteParticipantModalEl && window.bootstrap && typeof bootstrap.Modal === 'function') {
      deleteParticipantModal = new bootstrap.Modal(deleteParticipantModalEl);
    }

    function openDeleteParticipantModal(participantIDValue, participantName) {
      pendingDeleteParticipantName = participantName || '';
      if (deleteParticipantPrompt) {
        deleteParticipantPrompt.textContent = participantName ? `Participant: ${participantName}` : '';
      }
      if (deleteParticipantConfirmInput) {
        deleteParticipantConfirmInput.value = '';
      }
      if (confirmDeleteParticipantBtn) {
        confirmDeleteParticipantBtn.disabled = true;
      }
      if (deleteParticipantID) {
        deleteParticipantID.value = participantIDValue;
      }
      if (deleteParticipantModal) {
        deleteParticipantModal.show();
      }
    }

    document.querySelectorAll('.participant-delete-btn').forEach(button => {
      button.addEventListener('click', () => {
        openDeleteParticipantModal(button.dataset.participantId || '', button.dataset.participantName || '');
      });
    });

    if (deleteParticipantConfirmInput && confirmDeleteParticipantBtn) {
      deleteParticipantConfirmInput.addEventListener('input', () => {
        confirmDeleteParticipantBtn.disabled = deleteParticipantConfirmInput.value.trim().toLowerCase() !== 'delete';
      });
    }

    if (confirmDeleteParticipantBtn && deleteParticipantForm) {
      confirmDeleteParticipantBtn.addEventListener('click', () => {
        deleteParticipantForm.submit();
      });
    }

    syncParticipantFieldVisibility();
    window.addEventListener('resize', syncParticipantFieldVisibility);
  </script>
</body>
</html>
