<?php
require __DIR__ . '/core.php';
header('Content-Type: application/json');

try {
  $db = db();
  $subs = $db->query("SELECT endpoint, p256dh, auth FROM subscriptions")->fetchAll(PDO::FETCH_ASSOC);

  if (!$subs) {
    echo json_encode(['ok' => false, 'error' => 'No subscriptions in DB - turn notifications off + on in app']);
    exit;
  }

  // sendAlerts() already sends to all subs, reuse it with a fake article
  // easiest test: just call your normal cron, it will push if there's new news
  $result = runCron();

  echo json_encode(['ok' => true, 'subs' => count($subs), 'result' => $result]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}