<?php
  include '../processes/db_connection.php';
?>
<div class="app-panel p-4 mb-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
    <div>
      <div class="app-kicker">Training</div>
      <h2 class="app-section-title mb-1"><?php echo htmlspecialchars($trainingName); ?></h2>
      <p class="app-muted mb-0">Attendance for Day <?php echo htmlspecialchars($day); ?></p>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100 w-lg-auto">
      <div class="app-mini-card flex-grow-1" style="min-width: 220px;">
        <div class="app-kicker mb-1">View</div>
        <div class="fw-semibold">Live attendance table</div>
      </div>
    </div>
  </div>
</div>

<div class="app-toolbar mb-3" id="attendanceSearchPanel">
  <div class="batch-search-group">
    <span class="batch-search-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
    <input type="search" id="attendanceSearch" class="form-control batch-search-input" placeholder="Search by ID, name, or agency">
    <button type="button" class="btn btn-outline-secondary batch-search-clear" id="clearAttendanceSearchBtn">Clear</button>
  </div>
</div>

<section class="app-table-wrap" id="attendance-table">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th class="text-center attendance-col-no">No.</th>
          <th>Name</th>
          <th class="text-center attendance-col-agency">Agency</th>
          <th class="text-center">In</th>
          <th class="text-center">Out</th>
        </tr>
      </thead>
      <tbody id="table-body">
        <tr>
          <td colspan="5" class="py-4">
            <div class="batch-loading" id="attendanceLoadingState" aria-live="polite">
              <div class="batch-loading-text">Loading attendance...</div>
              <div class="app-loading-list">
                <div class="app-loading-card"><div class="app-loading-line mid mb-2"></div><div class="app-loading-line short"></div></div>
                <div class="app-loading-card"><div class="app-loading-line mid mb-2"></div><div class="app-loading-line short"></div></div>
                <div class="app-loading-card"><div class="app-loading-line mid mb-2"></div><div class="app-loading-line short"></div></div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<script>
  $(document).ready(function(){
    var trainingID = <?php echo json_encode($trainingID); ?>;
    var day = <?php echo json_encode($day); ?>;
    var attendanceSearchTerm = '';
    var attendanceSearchPanel = document.getElementById('attendanceSearchPanel');
    var attendanceSearchInput = document.getElementById('attendanceSearch');
    var clearAttendanceSearchBtn = document.getElementById('clearAttendanceSearchBtn');

    function filterAttendanceRows() {
      var normalizedTerm = attendanceSearchTerm.trim().toLowerCase();
      $('#table-body tr').each(function() {
        var haystack = (this.dataset.search || '').toLowerCase();
        var match = !normalizedTerm || haystack.indexOf(normalizedTerm) !== -1;
        this.style.display = match ? '' : 'none';
      });
    }

    function fetchData() {
      $.ajax({
        url: '../processes/fetchAttendanceRows.php',
        type: 'GET',
        data: {
          id: trainingID,
          days: day
        },
        success: function(response) {
          var data = response;
          if (typeof response === 'string') {
            try {
              data = JSON.parse(response);
            } catch (e) {
              data = null;
            }
          }
          if (data && data.success) {
            $('#table-body').html(data.html || '<tr><td colspan="5" class="text-center py-5 app-muted">No participants found.</td></tr>');
            filterAttendanceRows();
          } else {
            $('#table-body').html('<tr><td colspan="5" class="text-center py-5 app-muted">Unable to load attendance.</td></tr>');
          }
        },
        error: function() {
          $('#table-body').html('<tr><td colspan="5" class="text-center py-5 app-muted">Unable to load attendance.</td></tr>');
        }
      });
    }

    if (attendanceSearchInput) {
      attendanceSearchInput.addEventListener('input', function () {
        attendanceSearchTerm = this.value;
        filterAttendanceRows();
      });
    }

    if (clearAttendanceSearchBtn && attendanceSearchInput) {
      clearAttendanceSearchBtn.addEventListener('click', function () {
        attendanceSearchInput.value = '';
        attendanceSearchTerm = '';
        filterAttendanceRows();
        attendanceSearchInput.focus();
      });
    }

    fetchData();
    setInterval(fetchData, 1000);
  });
</script>
