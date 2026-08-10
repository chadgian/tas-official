<?php
header('Content-Type: text/plain; charset=utf-8');

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
echo "env_path=" . $envPath . PHP_EOL;
echo "env_exists=" . (is_file($envPath) ? 'yes' : 'no') . PHP_EOL;
echo "env_readable=" . (is_readable($envPath) ? 'yes' : 'no') . PHP_EOL;

$env = array();
if (is_file($envPath) && is_readable($envPath)) {
    $handle = fopen($envPath, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
        fclose($handle);
    }
}

$host = isset($env['DB_HOST']) && $env['DB_HOST'] !== '' ? $env['DB_HOST'] : 'localhost';
$user = isset($env['DB_USER']) && $env['DB_USER'] !== '' ? $env['DB_USER'] : 'root';
$name = isset($env['DB_NAME']) && $env['DB_NAME'] !== '' ? $env['DB_NAME'] : 'tas_db';

echo "db_host=" . $host . PHP_EOL;
echo "db_user=" . $user . PHP_EOL;
echo "db_name=" . $name . PHP_EOL;
