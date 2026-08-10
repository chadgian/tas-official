<?php
require_once 'db_connection.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$trainingID = $_GET['id'] ?? '';

if ($trainingID === '') {
    http_response_code(400);
    echo 'Missing training ID.';
    exit();
}

$stmt = $conn->prepare("SELECT training_name, training_days, training_start_date, training_month, training_year FROM trainings WHERE training_id = ? LIMIT 1");
$stmt->bind_param('s', $trainingID);
$stmt->execute();
$result = $stmt->get_result();
$training = $result->fetch_assoc();
$stmt->close();

if (!$training) {
    http_response_code(404);
    echo 'Training not found.';
    exit();
}

$trainingName = $training['training_name'] ?? 'Training';
$trainingDays = (int)($training['training_days'] ?? 0);
$trainingStartDate = $training['training_start_date'] ?? '';

if ($trainingStartDate !== '') {
    $start = new DateTime($trainingStartDate);
    $end = (clone $start)->modify('+' . max($trainingDays - 1, 0) . ' days');
    $trainingDate = $start->format('F j') . ($trainingDays > 1 ? '-' . $end->format('j') : '') . ', ' . $start->format('Y');
} else {
    $trainingDate = trim(($training['training_month'] ?? '') . ' ' . ($training['training_year'] ?? ''));
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
            $participantId = (string)$row['participant_id'];
            if (!isset($participants[$participantId])) {
                $participants[$participantId] = [
                    'participant_id' => $participantId,
                    'lastname' => $row['lastname'] ?? '',
                    'firstname' => $row['firstname'] ?? '',
                    'middle_initial' => $row['middle_initial'] ?? '',
                    'agency' => $row['agency'] ?? '',
                ];
            }

            $dayData[$day][$participantId] = [
                'login' => !empty($row['login']) ? date('H:i', strtotime($row['login'])) : '',
                'logout' => !empty($row['logout']) ? date('H:i', strtotime($row['logout'])) : '',
            ];
        }
        $stmtDay->close();
    }
}

usort($participants, function ($a, $b) {
    return (int)$a['participant_id'] <=> (int)$b['participant_id'];
});

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance');

$sheet->setCellValue('A1', 'Training Attendance');
$sheet->setCellValue('A2', 'Training Name: ' . $trainingName);
$sheet->setCellValue('A3', 'Training Date: ' . $trainingDate);
$sheet->setCellValue('A4', 'Training ID: ' . $trainingID);

$sheet->setCellValue('A6', 'No.');
$sheet->setCellValue('B6', 'Participant Name');
$sheet->setCellValue('C6', 'Agency');

$col = 4;
for ($day = 1; $day <= $trainingDays; $day++) {
    $dayLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $nextLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
    $sheet->setCellValue($dayLetter . '6', "Day {$day}");
    $sheet->setCellValue($dayLetter . '7', 'In');
    $sheet->setCellValue($nextLetter . '7', 'Out');
    $sheet->mergeCells($dayLetter . '6:' . $nextLetter . '6');
    $col += 2;
}

$lastColumnIndex = max(3, 3 + ($trainingDays * 2));
$lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex);

$sheet->mergeCells('A1:' . $lastColumn . '1');
$sheet->mergeCells('A2:' . $lastColumn . '2');
$sheet->mergeCells('A3:' . $lastColumn . '3');
$sheet->mergeCells('A4:' . $lastColumn . '4');

$sheet->getStyle('A1:' . $lastColumn . '7')->getFont()->setName('Arial');
$sheet->getStyle('A1:' . $lastColumn . '7')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A6:' . $lastColumn . '7')->getFont()->setBold(true);
$sheet->getStyle('A6:' . $lastColumn . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A6:' . $lastColumn . '7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9EEF7');

$row = 8;
if (empty($participants)) {
    $sheet->setCellValue('A8', 'No participants found.');
    $sheet->mergeCells('A8:' . $lastColumn . '8');
    $sheet->getStyle('A8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
} else {
    foreach ($participants as $participant) {
        $fullName = trim($participant['lastname'] . ', ' . $participant['firstname'] . ' ' . $participant['middle_initial']);
        $sheet->setCellValue('A' . $row, $participant['participant_id']);
        $sheet->setCellValue('B' . $row, $fullName);
        $sheet->setCellValue('C' . $row, $participant['agency']);

        $col = 4;
        for ($day = 1; $day <= $trainingDays; $day++) {
            $dayLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $nextLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $login = $dayData[$day][$participant['participant_id']]['login'] ?? '';
            $logout = $dayData[$day][$participant['participant_id']]['logout'] ?? '';
            $sheet->setCellValue($dayLetter . $row, $login !== '' ? $login : '-');
            $sheet->setCellValue($nextLetter . $row, $logout !== '' ? $logout : '-');
            $col += 2;
        }
        $row++;
    }
}

$lastRow = max($row - 1, 8);

$sheet->getStyle("A6:{$lastColumn}{$lastRow}")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '777777'],
        ],
    ],
]);

for ($i = 1; $i <= $lastColumnIndex; $i++) {
    $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
}

$sheet->freezePane('A8');
$sheet->setAutoFilter("A6:{$lastColumn}{$lastRow}");

$writer = new Xlsx($spreadsheet);
$filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $trainingName) . '_Attendance_Report.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
$conn->close();
