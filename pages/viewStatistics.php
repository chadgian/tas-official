<?php
session_start();
include_once '../processes/db_connection.php';

if (!isset($_SESSION['username'])) {
  header('Location: ../index.php');
  exit();
}

$trainingID = $_GET['id'] ?? '';
$day = (int)($_GET['day'] ?? 1);

if ($trainingID === '') {
  header('Location: main.php');
  exit();
}

$stmtTraining = $conn->prepare("SELECT training_name, training_days FROM trainings WHERE training_id = ? LIMIT 1");
$stmtTraining->bind_param('s', $trainingID);
$stmtTraining->execute();
$training = $stmtTraining->get_result()->fetch_assoc();
if (!$training) {
  header('Location: main.php');
  exit();
}

$trainingName = $training['training_name'] ?? 'Training';
$trainingDays = (int)($training['training_days'] ?? 0);
if ($day < 1 || $day > $trainingDays) {
  $day = 1;
}

$trainingTable = "training-$trainingID-$day";
$stmt = $conn->prepare("SELECT participant_id, agency, login FROM `$trainingTable` ORDER BY participant_id ASC");
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalParticipants = count($rows);
$presentParticipants = 0;
$agencyStats = [];

foreach ($rows as $row) {
  $agency = trim((string)($row['agency'] ?? ''));
  $isPresent = !empty($row['login']);

  if (!isset($agencyStats[$agency])) {
    $agencyStats[$agency] = [
      'agency' => $agency !== '' ? $agency : 'Unassigned',
      'present' => 0,
      'total' => 0,
    ];
  }

  $agencyStats[$agency]['total']++;
  if ($isPresent) {
    $presentParticipants++;
    $agencyStats[$agency]['present']++;
  }
}

foreach ($agencyStats as &$stat) {
  $stat['percentage'] = $stat['total'] > 0 ? round(($stat['present'] / $stat['total']) * 100, 1) : 0;
}
unset($stat);

usort($agencyStats, function ($a, $b) {
  if ($a['percentage'] === $b['percentage']) {
    return strcmp($a['agency'], $b['agency']);
  }
  return $b['percentage'] <=> $a['percentage'];
});

$overallPercentage = $totalParticipants > 0 ? round(($presentParticipants / $totalParticipants) * 100, 1) : 0;
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Statistics</title>
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/css-bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
  <link rel="icon" href="<?php echo $assetBase; ?>/src/img/csc-logo.png" type="image/png">
</head>
<body class="app-shell">
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between">
      <div class="brand-lockup">
        <img src="<?php echo $assetBase; ?>/src/img/csc-logo.png" alt="CSC Logo" width="48" height="48" class="rounded-3">
        <div>
          <div class="app-kicker text-white-50">Training Attendance System</div>
          <div class="fw-semibold text-white"><?php echo htmlspecialchars($trainingName); ?></div>
        </div>
      </div>
      <a class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" href="viewTraining.php?id=<?php echo urlencode($trainingID); ?>">Back</a>
    </div>
  </header>

  <main class="container-fluid py-4 py-lg-5">
    <div class="app-panel p-4 p-lg-5 mx-auto" style="max-width: 1200px;">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
          <div class="app-kicker mb-1">Statistics</div>
          <h1 class="app-section-title h3 mb-2">Attendance overview</h1>
          <p class="app-muted mb-0">Day <?php echo (int)$day; ?> attendance compared against total participants.</p>
        </div>
        <div class="app-mini-card">
          <div class="small text-muted">Day</div>
          <div class="h4 mb-0"><?php echo (int)$day; ?></div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="app-mini-card h-100">
            <div class="app-kicker mb-1">Present</div>
            <div class="h2 mb-1"><?php echo (int)$presentParticipants; ?></div>
            <div class="text-muted">Participants marked present today</div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="app-mini-card h-100">
            <div class="app-kicker mb-1">Total participants</div>
            <div class="h2 mb-1"><?php echo (int)$totalParticipants; ?></div>
            <div class="text-muted">Registered for this training day</div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="app-mini-card h-100">
            <div class="app-kicker mb-1">Attendance rate</div>
            <div class="h2 mb-1"><?php echo number_format($overallPercentage, 1); ?>%</div>
            <div class="text-muted">Present divided by total participants</div>
          </div>
        </div>
      </div>

      <div class="app-table-wrap">
        <div class="p-3 p-lg-4 border-bottom">
          <div class="fw-semibold">Agency breakdown</div>
          <div class="text-muted small">Sorted from highest attendance percentage to lowest.</div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Agency</th>
                <th class="text-center">Present</th>
                <th class="text-center">Total</th>
                <th class="text-center">Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($agencyStats)): ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">No participants found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($agencyStats as $stat): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($stat['agency']); ?></td>
                    <td class="text-center"><?php echo (int)$stat['present']; ?></td>
                    <td class="text-center"><?php echo (int)$stat['total']; ?></td>
                    <td class="text-center fw-semibold"><?php echo number_format($stat['percentage'], 1); ?>%</td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
