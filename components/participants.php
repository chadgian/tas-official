<?php
  include '../processes/db_connection.php';

  $participantCount = 0;
  if (!empty($trainingID)) {
    $trainingTable = "training-$trainingID-1";
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `$trainingTable`");
    if ($countStmt && $countStmt->execute()) {
      $countResult = $countStmt->get_result();
      if ($countRow = $countResult->fetch_assoc()) {
        $participantCount = (int) ($countRow['total'] ?? 0);
      }
    }
  }
?>
<div class="app-panel p-4 mb-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
    <div>
      <div class="app-kicker">Training</div>
      <h2 class="app-section-title mb-1"><?php echo htmlspecialchars($trainingName); ?></h2>
      <p class="app-muted mb-0">Manage the participant roster and export-ready IDs.</p>
    </div>
    <div class="w-100 w-lg-auto" style="max-width: 420px;">
      <input id="search-participants" class="form-control" type="search" placeholder="Search participant or agency" aria-label="Search">
    </div>
  </div>
</div>

<?php if ($participantCount === 0): ?>
  <div class="app-panel p-4 p-lg-5 text-center">
    <i class="fas fa-users fa-3x text-muted mb-3"></i>
    <h3 class="mb-2">No participants yet</h3>
    <p class="app-muted mb-4">
      Add participants one by one or upload an Excel file to populate the roster faster.
    </p>

    <div class="row g-3 justify-content-center mb-4 text-start">
      <div class="col-12 col-lg-5">
        <div class="app-mini-card h-100">
          <div class="app-kicker mb-2">Excel format</div>
          <ul class="mb-0 ps-3 app-muted">
            <li>Row 1 should contain headers.</li>
            <li>Start participant data on row 2.</li>
            <li>Columns should be: no., last name, first name, middle initial, agency.</li>
            <li>Keep one participant per row.</li>
          </ul>
        </div>
      </div>
      <div class="col-12 col-lg-5">
        <div class="app-mini-card h-100">
          <div class="app-kicker mb-2">Quick add</div>
          <form action="../processes/exportExcel.php" method="post" enctype="multipart/form-data" class="d-grid gap-3">
            <input type="hidden" value="<?php echo $trainingID; ?>" name="trainingID" id="trainingID">
            <input type="file" name="excelFile" id="excelFile" class="form-control" accept=".xlsx,.xls,.csv">
            <button type="submit" class="btn app-btn-primary">Upload Excel file</button>
          </form>
          <hr>
          <a href="viewTraining.php?id=<?php echo $trainingID; ?>&open=add-participant" class="btn app-btn-soft w-100">Add participant</a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($participantCount > 0): ?>
  <section class="app-table-wrap" id="participants-table">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th class="text-center">No.</th>
            <th>Name</th>
            <th class="text-center">Agency</th>
            <th class="text-center">ID</th>
          </tr>
        </thead>
        <tbody id="table-body">
          <tr>
            <td colspan="4">
              <div class="app-loading-list" id="participantsLoadingState">
                <div class="app-loading-card">
                  <div class="app-loading-line mid mb-2"></div>
                  <div class="app-loading-line short"></div>
                </div>
                <div class="app-loading-card">
                  <div class="app-loading-line mid mb-2"></div>
                  <div class="app-loading-line short"></div>
                </div>
                <div class="app-loading-card">
                  <div class="app-loading-line mid mb-2"></div>
                  <div class="app-loading-line short"></div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<script>
  $(document).ready(function(){
    const searchInput = document.getElementById("search-participants");
    var trainingID = <?php echo json_encode($trainingID); ?>;
    renderLoadingState();
    fetchData(""); // Fetch all participants for the first time
    searchInput.addEventListener("input", function(event){
      var searchValue = event.target.value;
      renderLoadingState();
      fetchData(searchValue);
    });

    function renderLoadingState() {
      const tableBody = document.getElementById('table-body');
      if (!tableBody) {
        return;
      }
      $('#table-body').html(`
        <tr>
          <td colspan="4" class="py-4">
            <div class="app-loading-list">
              <div class="app-loading-card">
                <div class="app-loading-line mid mb-2"></div>
                <div class="app-loading-line short"></div>
              </div>
              <div class="app-loading-card">
                <div class="app-loading-line mid mb-2"></div>
                <div class="app-loading-line short"></div>
              </div>
              <div class="app-loading-card">
                <div class="app-loading-line mid mb-2"></div>
                <div class="app-loading-line short"></div>
              </div>
            </div>
          </td>
        </tr>
      `);
    }

    function fetchData(searchValue) {
        const tableBody = document.getElementById('table-body');
        if (!tableBody) {
          return;
        }
        $.ajax({
            url: '../processes/fetchParticipants.php',
            type: 'GET',
            data:{
                search: searchValue,
                id: trainingID
            },
            success: function(data) {
                const normalized = (data || '').trim();
                if (!normalized) {
                  $('#table-body').html('<tr><td colspan="4" class="text-center py-5 app-muted">No participants found.</td></tr>');
                } else {
                  $('#table-body').html(data);
                }
            },
            error: function () {
                $('#table-body').html('<tr><td colspan="4" class="text-center py-5 app-muted">Unable to load participants.</td></tr>');
            }
        });
    }
  });

</script>
