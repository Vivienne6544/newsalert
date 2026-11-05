<?php
$url = getenv('DATABASE_URL');
$o = parse_url($url);
$dbname = explode('?', ltrim($o['path'], '/'))[0];
$endpointId = explode('.', $o['host'])[0];

$connStr = "host=" . $o['host'] . " port=" . ($o['port'] ?? 5432) . " dbname=" . $dbname . " user=" . urldecode($o['user']) . " password=" . urldecode($o['pass']) . " sslmode=require options='--endpoint=" . $endpointId . "'";
$conn = @pg_connect($connStr);
if (!$conn) {
    echo "pg_connect failed: " . pg_last_error();
} else {
    echo "pg_connect OK - endpoint works!";
    $r = pg_query($conn, "SELECT 1 as test");
    print_r(pg_fetch_assoc($r));
}
