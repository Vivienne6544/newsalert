<?php
error_reporting(0);
$url = getenv('DATABASE_URL');
$o = parse_url($url);
$dbname = explode('?', ltrim($o['path'], '/'))[0];
$endpointId = explode('.', $o['host'])[0];

// Try 1: options with --endpoint
$connStr = "host=" . $o['host'] . " port=" . ($o['port'] ?? 5432) . " dbname=" . $dbname . " user=" . urldecode($o['user']) . " password=" . urldecode($o['pass']) . " sslmode=require options='--endpoint=" . $endpointId . "'";
$conn = @pg_connect($connStr);
if ($conn) {
    echo "TRY1 OK";
    exit;
}
$e1 = error_get_last();
echo "TRY1 failed: " . ($e1['message'] ?? 'unknown') . "<br><br>";

// Try 2: options with endpoint=
$connStr2 = "host=" . $o['host'] . " port=" . ($o['port'] ?? 5432) . " dbname=" . $dbname . " user=" . urldecode($o['user']) . " password=" . urldecode($o['pass']) . " sslmode=require options='endpoint=" . $endpointId . "'";
$conn2 = @pg_connect($connStr2);
if ($conn2) {
    echo "TRY2 OK";
    exit;
}
$e2 = error_get_last();
echo "TRY2 failed: " . ($e2['message'] ?? 'unknown');
