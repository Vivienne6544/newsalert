<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json');
try {
  // Same fresh article as before, but reset it so it will try again
  db()->prepare("UPDATE articles SET notified_at = NULL, published_at = NOW() WHERE id = (SELECT id FROM articles ORDER BY published_at DESC LIMIT 1)")->execute();
  db()->prepare("UPDATE articles SET notified_at = '2020-01-01 00:00:00' WHERE notified_at IS NOT NULL AND id != (SELECT id FROM articles ORDER BY published_at DESC LIMIT 1)")->execute();

  $since = date('Y-m-d H:i:s', time() - 3*3600);
  $fresh = db()->prepare('SELECT title, link, topic FROM articles WHERE notified_at IS NULL AND published_at > ? ORDER BY published_at DESC');
  $fresh->execute([$since]);
  $fresh = $fresh->fetchAll();

  $webPush = new Minishlink\WebPush\WebPush(['VAPID' => [
    'subject' => VAPID_SUBJECT, 'publicKey' => VAPID_PUBLIC, 'privateKey' => VAPID_PRIVATE,
  ]]);

  $subs = db()->query('SELECT endpoint, p256dh, auth, topics FROM subscriptions')->fetchAll();
  $queued = 0;
  foreach ($subs as $s) {
    $wanted = array_filter(explode(',', $s['topics']));
    $mine = array_values(array_filter($fresh, fn($a) => in_array('all', $wanted) || in_array($a['topic'], $wanted)));
    if (!$mine) continue;
    $payload = ['title' => 'Breaking: ' . ucfirst($mine[0]['topic']), 'body' => $mine[0]['title'], 'url' => $mine[0]['link']];
    $webPush->queueNotification(
      Minishlink\WebPush\Subscription::create(['endpoint' => $s['endpoint'], 'keys' => ['p256dh' => $s['p256dh'], 'auth' => $s['auth']]]),
      json_encode($payload)
    );
    $queued++;
  }

  $results = [];
  foreach ($webPush->flush() as $report) {
    $results[] = ['ok' => $report->isSuccess(), 'expired' => $report->isSubscriptionExpired(), 'reason' => $report->getReason(), 'status' => $report->getResponse() ? $report->getResponse()->getStatusCode() : null];
  }

  echo json_encode(['fresh_title' => $fresh[0]['title'] ?? null, 'fresh_topic' => $fresh[0]['topic'] ?? null, 'fresh_url' => $fresh[0]['link'] ?? null, 'queued' => $queued, 'results' => $results]);
} catch (Throwable $e) {
  echo json_encode(['error' => $e->getMessage()]);
}