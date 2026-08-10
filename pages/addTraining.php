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
?>
<?php $assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'); ?>

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
  <header class="d-flex align-items-center py-2 px-3 sticky-top justify-content-between mb-4"
    style="background-color: #183153; color: white; height: 5rem;">
    <div>
      <img src="<?php echo $assetBase; ?>/src/img/csc-logo.png" alt="CSC Logo" width="60">
    </div>
    <div class="d-flex flex-column align-items-center">
      <a href="../index.php" class="text-white d-flex flex-column align-items-center" style="text-decoration: none;">
        <h1>Training</h1>
        <h6>Attendance</h6>
      </a>
    </div>
    <div>
      <a class="btn" href="main.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-x-lg"
          viewBox="0 0 16 16">
          <path
            d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
        </svg>
      </a>
    </div>
  </header>
  <main class="container py-5">
    <div class="app-panel p-4 p-lg-5 mx-auto" style="max-width: 760px;">
      <div class="app-kicker mb-2">Training setup</div>
      <h1 class="app-section-title mb-2">Add Training</h1>
      <p class="app-muted mb-4">Create a new training record before assigning participants or generating QR IDs.</p>
      <form action="../processes/addTrainingProcess.php" method="post" class="row g-3">
        <div class="col-12">
          <label for="training-name" class="form-label fw-semibold">Training Name</label>
          <input type="text" name="training-name" id="training-name" placeholder="Training Name" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label for="training-month" class="form-label fw-semibold">Month</label>
          <input type="text" name="training-month" id="training-month" placeholder="January" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label for="training-start-date" class="form-label fw-semibold">Start Date</label>
          <input type="date" name="training-start-date" id="training-start-date" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label for="training-year" class="form-label fw-semibold">Year</label>
          <input type="number" name="training-year" id="training-year" placeholder="2026" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label for="training-days" class="form-label fw-semibold">Days</label>
          <input type="number" name="training-days" id="training-days" placeholder="3" class="form-control" required>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2 pt-2">
          <a href="main.php" class="btn btn-light">Cancel</a>
          <input type="submit" value="Save training" class="btn btn-primary app-btn-primary">
        </div>
      </form>
    </div>
  </main>
</body>

</html>
