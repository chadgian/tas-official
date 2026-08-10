<?php

require_once __DIR__ . '/db_connection.php';

$syncRoot = __DIR__ . '/../sync';
$pendingDir = $syncRoot . '/pending';
$mirrorFile = $syncRoot . '/mirror.json';

if (!is_dir($pendingDir)) {
    @mkdir($pendingDir, 0777, true);
}

function sync_ensure_array($value): array
{
    return is_array($value) ? $value : [];
}

function sync_write_mirror(mysqli $conn, string $mirrorFile): void
{
    if (!file_exists($mirrorFile)) {
        $blank = [
            'generated_at' => null,
            'trainings' => [],
            'users' => [],
        ];
        @file_put_contents($mirrorFile, json_encode($blank, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    $mirror = [
        'generated_at' => gmdate('c'),
        'trainings' => [],
        'users' => [],
    ];

    $trainingsResult = $conn->query('SELECT * FROM trainings ORDER BY training_id DESC');
    if ($trainingsResult instanceof mysqli_result) {
        while ($row = $trainingsResult->fetch_assoc()) {
            $mirror['trainings'][] = $row;
        }
    }

    $usersResult = $conn->query('SELECT id, username FROM users ORDER BY id ASC');
    if ($usersResult instanceof mysqli_result) {
        while ($row = $usersResult->fetch_assoc()) {
            $mirror['users'][] = $row;
        }
    }

    @file_put_contents($mirrorFile, json_encode($mirror, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function sync_apply_operation(mysqli $conn, array $operation): bool
{
    $type = $operation['type'] ?? '';
    $action = $operation['action'] ?? '';
    $payload = sync_ensure_array($operation['payload'] ?? []);

    if ($type === 'training' && $action === 'upsert') {
        $trainingId = (int)($payload['training_id'] ?? 0);
        if ($trainingId > 0) {
            $trainingName = (string)($payload['training_name'] ?? '');
            $trainingMonth = (string)($payload['training_month'] ?? '');
            $trainingStartDate = $payload['training_start_date'] ?? null;
            $trainingYear = (string)($payload['training_year'] ?? '');
            $trainingDays = (string)($payload['training_days'] ?? '');

            $stmt = $conn->prepare('UPDATE trainings SET training_name=?, training_month=?, training_start_date=?, training_year=?, training_days=? WHERE training_id=?');
            $stmt->bind_param('sssssi', $trainingName, $trainingMonth, $trainingStartDate, $trainingYear, $trainingDays, $trainingId);
            return $stmt->execute();
        }
        return false;
    }

    if ($type === 'attendance' && $action === 'upsert') {
        $trainingId = (int)($payload['training_id'] ?? 0);
        $day = (int)($payload['day'] ?? 0);
        $participantId = (int)($payload['participant_id'] ?? 0);
        $field = (string)($payload['field'] ?? '');
        $value = (string)($payload['value'] ?? '');
        if ($trainingId > 0 && $day > 0 && $participantId > 0 && in_array($field, ['login', 'logout'], true)) {
            $table = sprintf('training-%d-%d', $trainingId, $day);
            $sql = "UPDATE `$table` SET `$field` = ? WHERE participant_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $value, $participantId);
            return $stmt->execute();
        }
        return false;
    }

    if ($type === 'participant' && $action === 'upsert') {
        $trainingId = (int)($payload['training_id'] ?? 0);
        $day = (int)($payload['day'] ?? 1);
        $participantId = (int)($payload['participant_id'] ?? 0);
        $lastName = (string)($payload['lastname'] ?? '');
        $firstName = (string)($payload['firstname'] ?? '');
        $middleInitial = (string)($payload['middle_initial'] ?? '');
        $agency = (string)($payload['agency'] ?? '');
        if ($trainingId > 0 && $participantId > 0) {
            $table = sprintf('training-%d-%d', $trainingId, $day);
            $sql = "UPDATE `$table` SET lastname=?, firstname=?, middle_initial=?, agency=? WHERE participant_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssi', $lastName, $firstName, $middleInitial, $agency, $participantId);
            return $stmt->execute();
        }
        return false;
    }

    return false;
}

foreach (glob($pendingDir . '/*.json') ?: [] as $file) {
    $raw = file_get_contents($file);
    $operation = json_decode($raw ?: '', true);
    if (!is_array($operation)) {
        continue;
    }

    if (sync_apply_operation($conn, $operation)) {
        @unlink($file);
    }
}

sync_write_mirror($conn, $mirrorFile);
