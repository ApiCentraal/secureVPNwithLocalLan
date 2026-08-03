<?php
// @changed 2026-04-24 — VpnService::getDashboardState, try/catch; removed shell_exec
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

if (!Auth::isLoggedIn()) {
  http_response_code(401);
  exit();
}

$service = new VpnService();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $selection = trim((string) ($_POST['conf'] ?? ''));
  $csrfToken = (string) ($_POST['csrf_token'] ?? '');

  if ($selection !== '' && Csrf::validate($csrfToken)) {
    try {
      $service->applySelection($selection);
    } catch (Throwable $exception) {
      // Keep backward compatibility by silently ignoring invalid legacy submissions.
    }
  }
}

$state = [
  'selectedAction' => 'stop',
  'connections' => [],
];

try {
  $state = $service->getDashboardState(20);
} catch (Throwable $exception) {
  // Keep endpoint available for legacy consumers even if VPN status command fails.
}
$selected = (string) ($state['selectedAction'] ?? 'stop');
$csrfToken = Csrf::token();

header('Content-Type: text/html; charset=utf-8');

echo '<form name="vpn" method="post">';
echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';

foreach ($state['connections'] as $connection) {
  $id = htmlspecialchars((string) $connection['id'], ENT_QUOTES, 'UTF-8');
  $label = htmlspecialchars((string) $connection['label'], ENT_QUOTES, 'UTF-8');
  $checked = ($selected === $connection['id']) ? ' checked="checked"' : '';

  echo '<input type="radio" id="conf-' . $id . '" name="conf" value="' . $id . '"' . $checked . '>' . $label . '<br>';
}

$stopChecked = ($selected === 'stop') ? ' checked="checked"' : '';
$localChecked = ($selected === 'stop-route-local') ? ' checked="checked"' : '';

echo '<input type="radio" id="conf-stop" name="conf" value="stop"' . $stopChecked . '>Stop VPN routing<br>';
echo '<input type="radio" id="conf-stop-local" name="conf" value="stop-route-local"' . $localChecked . '>Stop VPN and route local<br>';
echo '<button type="submit">Submit</button>';
echo '</form>';

