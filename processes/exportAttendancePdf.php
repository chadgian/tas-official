<?php
require_once 'db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

date_default_timezone_set('Asia/Manila');

$trainingID = $_GET['id'] ?? null;

if (!$trainingID) {
    http_response_code(400);
    echo 'Missing training ID.';
    exit();
}

$stmt = $conn->prepare("SELECT * FROM trainings WHERE training_id = ?");
$stmt->bind_param('s', $trainingID);
$stmt->execute();
$result = $stmt->get_result();
$training = $result->fetch_assoc();

if (!$training) {
    http_response_code(404);
    echo 'Training not found.';
    exit();
}

$trainingName = $training['training_name'];
$trainingDays = (int)$training['training_days'];
$trainingStartDate = $training['training_start_date'] ?? '';
$trainingVenue = $training['training_venue'] ?? '';
$exportedOn = date('F j, Y h:i A');
$exportedAtIso = date('c');

if ($trainingStartDate === '' || trim((string)$trainingVenue) === '') {
    header("Location: ../pages/viewTraining.php?id=$trainingID");
    exit();
}

$participants = [];
$dayData = [];

for ($day = 1; $day <= $trainingDays; $day++) {
    $tableName = "training-$trainingID-$day";
    $dayData[$day] = [];

    $stmtDay = $conn->prepare("SELECT * FROM `$tableName` ORDER BY participant_id ASC");
    if ($stmtDay && $stmtDay->execute()) {
        $resultDay = $stmtDay->get_result();
        while ($row = $resultDay->fetch_assoc()) {
            $participantId = $row['participant_id'];
            if (!isset($participants[$participantId])) {
                $participants[$participantId] = [
                    'participant_id' => $participantId,
                    'lastname' => $row['lastname'],
                    'firstname' => $row['firstname'],
                    'middle_initial' => $row['middle_initial'],
                    'agency' => $row['agency'],
                ];
            }

            $dayData[$day][$participantId] = [
                'login' => !empty($row['login']) ? date('H:i', strtotime($row['login'])) : '',
                'logout' => !empty($row['logout']) ? date('H:i', strtotime($row['logout'])) : '',
            ];
        }
    }
}

$participants = array_values($participants);
usort($participants, function ($a, $b) {
    return (int)$a['participant_id'] <=> (int)$b['participant_id'];
});

$reportFingerprint = hash('sha256', implode('|', [
    $trainingID,
    $trainingName,
    $trainingDays,
    $exportedAtIso,
    count($participants),
]));

$verificationToken = substr($reportFingerprint, 0, 16);

$createExportTable = $conn->prepare("
    CREATE TABLE IF NOT EXISTS exported_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        training_id INT NOT NULL,
        training_name VARCHAR(255) NOT NULL,
        training_date VARCHAR(255) NOT NULL,
        participant_count INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        exported_on DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$createExportTable->execute();
$createExportTable->close();

$conn->query("ALTER TABLE exported_documents ADD COLUMN IF NOT EXISTS training_date VARCHAR(255) NOT NULL");
$conn->query("ALTER TABLE exported_documents ADD COLUMN IF NOT EXISTS participant_count INT NOT NULL");

$insertExport = $conn->prepare("
    INSERT INTO exported_documents (training_id, training_name, training_date, participant_count, token, exported_on)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        training_name = VALUES(training_name),
        training_date = VALUES(training_date),
        participant_count = VALUES(participant_count),
        exported_on = VALUES(exported_on)
");
$trainingDate = '';
if ($trainingStartDate !== '') {
    $start = new DateTime($trainingStartDate);
    $end = (clone $start)->modify('+' . max($trainingDays - 1, 0) . ' days');
    $trainingDate = $start->format('F j') . ($trainingDays > 1 ? '-' . $end->format('j') : '') . ', ' . $start->format('Y');
} else {
    $trainingDate = trim($training['training_month'] . ' ' . $training['training_year']);
}
$participantCount = count($participants);
$participantCountValue = (string)$participantCount;
$insertExport->bind_param('ssssss', $trainingID, $trainingName, $trainingDate, $participantCountValue, $verificationToken, $exportedAtIso);
$insertExport->execute();
$insertExport->close();

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$verifyUrl = $scheme . '://' . $host . $basePath . '/../verify.php?t=' . urlencode($verificationToken);
$verificationPayload = $verifyUrl;

$qrOptions = new QROptions([
    'eccLevel' => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'version' => 6,
    'scale' => 7,
    'quietzoneSize' => 1,
    'addQuietzone' => true,
]);

$qrCode = new QRCode($qrOptions);
$qrCodePath = $qrCode->render($verificationPayload);
$qrImageBase64 = base64_encode(file_get_contents($qrCodePath));

$html = '
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #222; }
        .header { border-bottom: 2px solid #1f3b73; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 18pt; font-weight: bold; color: #1f3b73; margin: 0; }
        .subtitle { font-size: 10pt; margin: 2px 0; }
        .meta { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .details { margin-top: 8px; }
        .qrbox { float: right; text-align: center; width: 150px; font-size: 8pt; }
        .qrbox img { width: 120px; height: 120px; }
        .clear { clear: both; }
        table.attendance { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.attendance th, table.attendance td { border: 1px solid #777; padding: 5px; }
        table.attendance th { background: #e9eef7; font-size: 8.5pt; }
        table.attendance td { font-size: 8.5pt; }
        .center { text-align: center; }
        .right { text-align: right; }
        .small { font-size: 8pt; color: #555; }
        .note { margin-top: 12px; font-style: italic; font-size: 9pt; }
        .sign { margin-top: 24px; width: 100%; border-collapse: collapse; }
        .sign td { width: 50%; padding-top: 28px; }
        .line { border-top: 1px solid #333; width: 85%; margin-top: 26px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="qrbox">
            <img src="data:image/png;base64,' . $qrImageBase64 . '" alt="Verification QR">
            <div>Verification QR</div>
        </div>
        <p class="title">Training Attendance Report</p>
        <div class="subtitle"><b>Training:</b> ' . htmlspecialchars($trainingName) . '</div>
        <div class="subtitle"><b>Venue:</b> ' . htmlspecialchars($trainingVenue) . '</div>
        <div class="subtitle"><b>Training ID:</b> ' . htmlspecialchars($trainingID) . '</div>
        <div class="subtitle"><b>Exported On:</b> ' . htmlspecialchars($exportedOn) . '</div>
        <div class="clear"></div>
    </div>

    <div class="details">
        <table class="meta">
            <tr>
                <td><b>Training Duration:</b> ' . (int)$trainingDays . ' days</td>
                <td class="right"><b>Total Participants:</b> ' . count($participants) . '</td>
            </tr>
        </table>
    </div>

    <table class="attendance">
        <thead>
            <tr>
                <th rowspan="2" class="center">No.</th>
                <th rowspan="2" class="center">Participant Name</th>
                <th rowspan="2" class="center">Agency</th>';

for ($day = 1; $day <= $trainingDays; $day++) {
    $html .= '<th colspan="2" class="center">Day ' . $day . '</th>';
}

$html .= '
            </tr>
            <tr>';

for ($day = 1; $day <= $trainingDays; $day++) {
    $html .= '<th class="center">In</th><th class="center">Out</th>';
}

$html .= '
            </tr>
        </thead>
        <tbody>';

if (count($participants) === 0) {
    $colspan = 3 + ($trainingDays * 2);
    $html .= '<tr><td colspan="' . $colspan . '" class="center">No participants found.</td></tr>';
} else {
    foreach ($participants as $participant) {
        $fullName = trim($participant['lastname'] . ', ' . $participant['firstname'] . ' ' . $participant['middle_initial']);
        $html .= '<tr>';
        $html .= '<td class="center">' . htmlspecialchars($participant['participant_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($fullName) . '</td>';
        $html .= '<td>' . htmlspecialchars($participant['agency']) . '</td>';
        for ($day = 1; $day <= $trainingDays; $day++) {
            $login = $dayData[$day][$participant['participant_id']]['login'] ?? '';
            $logout = $dayData[$day][$participant['participant_id']]['logout'] ?? '';
            $html .= '<td class="center">' . htmlspecialchars($login !== '' ? $login : '-') . '</td>';
            $html .= '<td class="center">' . htmlspecialchars($logout !== '' ? $logout : '-') . '</td>';
        }
        $html .= '</tr>';
    }
}

$html .= '
        </tbody>
    </table>

    <div class="note">This is to certify that this attendance report has been generated from the CSC Regional Office VI Training Attendance System (TAS). The names appearing in this report are the actual participants who attended the training.</div>
    <div class="note" style="font-style: normal; font-weight: bold; margin-top: 18px;">Certified Correct:</div>
</body>
</html>';

$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4-L',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 12,
    'margin_bottom' => 15,
    'margin_header' => 5,
    'margin_footer' => 8,
]);

$mpdf->SetTitle("Attendance Report - $trainingName");
$mpdf->SetAuthor('Training Attendance System');
$mpdf->SetSubject('Attendance export');
$mpdf->SetCreator('Training Attendance System');
$mpdf->SetFooter('{PAGENO} of {nbpg}');

$mpdf->WriteHTML($html);
$filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $trainingName) . '_Attendance_Report.pdf';
$mpdf->Output($filename, 'D');

@unlink($qrCodePath);

$conn->close();
?>
