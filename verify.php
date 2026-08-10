<?php
require_once __DIR__ . '/processes/db_connection.php';

$token = $_GET['t'] ?? '';
$isValid = false;
$record = null;
$trainingName = '';
$trainingDate = '';
$participantCount = '';
$trainingVenue = '';

if ($token !== '') {
    $stmt = $conn->prepare("SELECT * FROM exported_documents WHERE token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $isValid = (bool)$record;

    if ($isValid) {
        $trainingId = (int)$record['training_id'];

        $stmtTraining = $conn->prepare("SELECT training_name, training_month, training_year, training_venue FROM trainings WHERE training_id = ? LIMIT 1");
        $stmtTraining->bind_param('i', $trainingId);
        $stmtTraining->execute();
        $trainingResult = $stmtTraining->get_result();
        $trainingRow = $trainingResult->fetch_assoc();

        if ($trainingRow) {
            $trainingName = $trainingRow['training_name'] ?? '';
            $trainingVenue = $trainingRow['training_venue'] ?? '';
            $stmtTrainingDate = $conn->prepare("SELECT training_start_date, training_days FROM trainings WHERE training_id = ? LIMIT 1");
            $stmtTrainingDate->bind_param('i', $trainingId);
            $stmtTrainingDate->execute();
            $trainingDateResult = $stmtTrainingDate->get_result();
            $trainingDateRow = $trainingDateResult->fetch_assoc();

            $trainingStartDate = $trainingDateRow['training_start_date'] ?? '';
            $trainingDays = (int)($trainingDateRow['training_days'] ?? 0);
            if ($trainingStartDate !== '') {
                $start = new DateTime($trainingStartDate);
                $end = (clone $start)->modify('+' . max($trainingDays - 1, 0) . ' days');
                $trainingDate = $start->format('F j') . ($trainingDays > 1 ? '-' . $end->format('j') : '') . ', ' . $start->format('Y');
            } else {
                $trainingMonth = trim((string)($trainingRow['training_month'] ?? ''));
                $trainingYear = trim((string)($trainingRow['training_year'] ?? ''));
                if ($trainingMonth !== '' && $trainingYear !== '') {
                    $trainingDate = trim($trainingMonth . ' ' . $trainingYear);
                }
            }
        }

        $stmtParticipants = $conn->prepare("SELECT COUNT(*) AS total FROM `training-$trainingId-1`");
        if ($stmtParticipants && $stmtParticipants->execute()) {
            $participantsResult = $stmtParticipants->get_result();
            $participantsRow = $participantsResult->fetch_assoc();
            $participantCount = (string)($participantsRow['total'] ?? '');
        }

        if ($participantCount === '' && isset($record['participant_count'])) {
            $participantCount = (string)$record['participant_count'];
        }

        if ($trainingDate === '' && isset($record['training_date'])) {
            $trainingDate = (string)$record['training_date'];
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document Verification</title>
  <link rel="stylesheet" href="css/css-bootstrap/bootstrap.min.css">
  <link rel="icon" href="src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="src/img/csc-logo.png">
  <style>
    body {
      background:
        radial-gradient(circle at top left, rgba(13, 110, 253, 0.12), transparent 34%),
        radial-gradient(circle at top right, rgba(25, 135, 84, 0.10), transparent 30%),
        linear-gradient(180deg, #f7f9fc 0%, #eef2f7 100%);
      min-height: 100vh;
      color: #1f2937;
    }

    .page-shell {
      max-width: 920px;
      margin: 0 auto;
      padding: 2.5rem 1rem 3rem;
    }

    .verify-panel {
      background: rgba(255, 255, 255, 0.94);
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 1.5rem;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
      overflow: hidden;
      backdrop-filter: blur(8px);
    }

    .verify-header {
      background: linear-gradient(135deg, #0f172a, #1e3a8a);
      color: #fff;
      padding: 1.5rem 1.75rem;
    }

    .verify-brand {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .verify-mark {
      width: 3.25rem;
      height: 3.25rem;
      border-radius: 0.9rem;
      background: rgba(255, 255, 255, 0.14);
      display: grid;
      place-items: center;
      font-weight: 800;
      letter-spacing: 0.04em;
    }

    .eyebrow {
      text-transform: uppercase;
      letter-spacing: 0.16em;
      font-size: 0.75rem;
      opacity: 0.8;
      margin-bottom: 0.25rem;
    }

    .title {
      margin: 0;
      font-size: clamp(1.4rem, 2vw, 2rem);
      font-weight: 700;
    }

    .body-wrap {
      padding: 1.75rem;
    }

    .status-card {
      border-radius: 1rem;
      padding: 1rem 1.1rem;
      border: 1px solid;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.9rem;
    }

    .status-ok {
      background: #ecfdf3;
      border-color: #b7ebc6;
      color: #166534;
    }

    .status-bad {
      background: #fef2f2;
      border-color: #fecaca;
      color: #991b1b;
    }

    .status-icon {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 999px;
      display: grid;
      place-items: center;
      background: rgba(255, 255, 255, 0.7);
      font-size: 1.15rem;
      font-weight: 700;
      flex: 0 0 auto;
    }

    .doc-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .meta-block {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 1rem;
      padding: 0.95rem 1rem;
      min-height: 5.25rem;
    }

    .meta-label {
      color: #6b7280;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 0.3rem;
    }

    .meta-value {
      font-weight: 700;
      font-size: 1rem;
      line-height: 1.35;
      word-break: break-word;
    }

    .doc-footer {
      margin-top: 1.25rem;
      padding-top: 1rem;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
      color: #6b7280;
      font-size: 0.88rem;
    }

    .verify-note {
      margin-top: 1rem;
      color: #6b7280;
      font-size: 0.9rem;
    }

    @media (max-width: 640px) {
      .doc-grid { grid-template-columns: 1fr; }
      .body-wrap { padding: 1.1rem; }
      .verify-header { padding: 1.25rem; }
    }
  </style>
</head>
<body>
  <main class="page-shell">
    <section class="verify-panel">
      <header class="verify-header">
        <div class="verify-brand">
          <div class="verify-mark">HRD</div>
          <div>
            <div class="eyebrow">Document Verification</div>
            <h1 class="title">Training Attendance Verification</h1>
          </div>
        </div>
      </header>
      <div class="body-wrap">
        <?php if ($token === ''): ?>
          <div class="status-card status-bad">
            <div class="status-icon">!</div>
            <div>
              <div class="fw-bold">Verification code missing</div>
              <div>This document cannot be checked because no QR token was supplied.</div>
            </div>
          </div>
        <?php elseif ($isValid): ?>
          <div class="status-card status-ok">
            <div class="status-icon">✓</div>
            <div>
              <div class="fw-bold">Verified document</div>
              <div>This attendance record matches an issued system export.</div>
            </div>
          </div>

          <div class="doc-grid">
            <div class="meta-block">
              <div class="meta-label">Training Name</div>
              <div class="meta-value"><?php echo htmlspecialchars($trainingName); ?></div>
            </div>
            <div class="meta-block">
              <div class="meta-label">Total Participants</div>
              <div class="meta-value"><?php echo htmlspecialchars($participantCount); ?></div>
            </div>
            <div class="meta-block">
              <div class="meta-label">Date of Training</div>
              <div class="meta-value"><?php echo htmlspecialchars($trainingDate); ?></div>
            </div>
            <div class="meta-block">
              <div class="meta-label">Venue</div>
              <div class="meta-value"><?php echo htmlspecialchars($trainingVenue); ?></div>
            </div>
          </div>

          <div class="doc-footer">
            <div>This document is system-generated in the Training Attendance System</div>
            <div>Public verification enabled</div>
          </div>
          <div class="verify-note">If any detail looks incorrect, please contact the issuing office for confirmation.</div>
        <?php else: ?>
          <div class="status-card status-bad">
            <div class="status-icon">!</div>
            <div>
              <div class="fw-bold">Not verified</div>
              <div>This QR code is not recognized by the system.</div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>
<?php
$conn->close();
?>
