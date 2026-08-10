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

$stmt = $conn->prepare("SELECT training_name, training_days FROM trainings WHERE training_id = ? LIMIT 1");
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
$sheet->setCellValue('A3', 'Training ID: ' . $trainingID);

$sheet->setCellValue('A5', 'No.');
$sheet->setCellValue('B5', 'Participant Name');
$sheet->setCellValue('C5', 'Agency');

$col = 4;
for ($day = 1; $day <= $trainingDays; $day++) {
    $dayLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $nextLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
    $sheet->setCellValue($dayLetter . '5', "Day {$day}");
    $sheet->setCellValue($dayLetter . '6', 'In');
    $sheet->setCellValue($nextLetter . '6', 'Out');
    $sheet->mergeCells($dayLetter . '5:' . $nextLetter . '5');
    $col += 2;
}

$sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(3, 3 + ($trainingDays * 2))) . '1');
$sheet->mergeCells('A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(3, 3 + ($trainingDays * 2))) . '2');
$sheet->mergeCells('A3:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(3, 3 + ($trainingDays * 2))) . '3');

$lastColumnIndex = max(3, 3 + ($trainingDays * 2));
$lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex);

$sheet->getStyle('A1:' . $lastColumn . '6')->getFont()->setName('Arial');
$sheet->getStyle('A1:' . $lastColumn . '6')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A5:' . $lastColumn . '6')->getFont()->setBold(true);
$sheet->getStyle('A5:' . $lastColumn . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A5:' . $lastColumn . '6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9EEF7');

$row = 7;
if (empty($participants)) {
    $sheet->setCellValue('A7', 'No participants found.');
    $sheet->mergeCells('A7:' . $lastColumn . '7');
    $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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

$lastRow = max($row - 1, 7);

$sheet->getStyle("A5:{$lastColumn}{$lastRow}")->applyFromArray([
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

$sheet->freezePane('A7');
$sheet->setAutoFilter("A5:{$lastColumn}{$lastRow}");

$writer = new Xlsx($spreadsheet);
$filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $trainingName) . '_Attendance_Report.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
$conn->close();
