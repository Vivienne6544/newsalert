<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json');
try {
  // Take the very latest article and pretend it's brand new + never notified
  db()->prepare("UPDATE articles SET notified_at = NULL, published_at = NOW() WHERE id = (SELECT id FROM articles ORDER BY published_at DESC LIMIT 1)")->execute();
  // Clear the 5-min gap so it sends immediately
  db()->prepare("UPDATE articles SET notified_at = '2020-01-01 00:00:00' WHERE notified_at IS NOT NULL AND id != (SELECT id FROM articles ORDER BY published_at DESC LIMIT 1)")->execute();

  $sent = sendAlerts();
  echo json_encode(['forced_real_news_sent_to' => $sent, 'note' => 'This used sendAlerts(), NOT test-push. Check your phone now.']);
} catch (Throwable $e) {
  echo json_encode(['error' => $e->getMessage()]);
}