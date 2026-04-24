<?php
// @since  2026-04-24 — central VPN command service; all shell calls isolated here (argument whitelist enforced)
declare(strict_types=1);

final class VpnService
{
    private const VPN_ADMIN_BINARY = '/usr/local/bin/vpnadmin.sh';
    private const OPENVPN_LOG_PATH = '/var/log/openvpn/ovpn.log';

    public function getDashboardState(int $logLines = 60): array
    {
        $status = $this->getStatusSnapshot();
        $connections = $this->listConnections();
        $selectedAction = $this->resolveSelectedAction($status);

        return [
            'activeState' => $status['activeState'],
            'ipForwardEnabled' => $status['ipForwardEnabled'],
            'currentConnection' => $status['currentConnection'],
            'routeMode' => $status['routeMode'],
            'internetReachable' => $status['internetReachable'],
            'publicIpAddress' => $status['publicIpAddress'],
            'connectivityMode' => $status['connectivityMode'],
            'selectedAction' => $selectedAction,
            'connections' => $connections,
            'logLines' => $this->getLogTail($logLines),
            'updatedAt' => gmdate('c'),
        ];
    }

    public function applySelection(string $selection): array
    {
        $normalizedSelection = $this->normalizeConnectionId($selection);

        if ($normalizedSelection === 'stop') {
            $result = $this->runCommand(['stop']);
            $this->assertCommandSucceeded($result, 'Unable to stop VPN routing.');
            return $result;
        }

        if ($normalizedSelection === 'stop-route-local') {
            $result = $this->runCommand(['stop', 'route-local']);
            $this->assertCommandSucceeded($result, 'Unable to switch to local routing.');
            return $result;
        }

        if (!$this->connectionExists($normalizedSelection, $this->listConnections())) {
            throw new InvalidArgumentException('Unknown VPN profile selected.');
        }

        $result = $this->runCommand(['start', $normalizedSelection]);
        $this->assertCommandSucceeded($result, 'Unable to start selected VPN profile.');
        return $result;
    }

    public function getStatusSnapshot(): array
    {
        $result = $this->runCommand(['status']);
        $this->assertCommandSucceeded($result, 'Unable to retrieve VPN service status.');
        $activeState = 'unknown';
        $ipForwardEnabled = false;
        $currentConnection = null;
        $internetReachable = false;
        $publicIpAddress = null;

        foreach ($result['output'] as $line) {
            $line = trim($line);

            if (preg_match('/^ActiveState=(.+)$/', $line, $match)) {
                $activeState = trim($match[1]);
                continue;
            }

            if (preg_match('/net\.ipv4\.ip_forward\s*=\s*([01])/', $line, $match)) {
                $ipForwardEnabled = $match[1] === '1';
                continue;
            }

            if ($currentConnection === null && preg_match('/^[A-Za-z0-9._-]+$/', $line)) {
                $currentConnection = $this->normalizeConnectionId($line);
                continue;
            }

            if (preg_match('/^internet_reachable:(true|false)$/', $line, $match)) {
                $internetReachable = $match[1] === 'true';
                continue;
            }

            if (preg_match('/^public_ip:(.+)$/', $line, $match)) {
                $value = trim($match[1]);
                $publicIpAddress = ($value === '' || strtolower($value) === 'null') ? null : $value;
            }
        }

        $routeMode = 'vpn';
        if ($activeState !== 'active' && $ipForwardEnabled) {
            $routeMode = 'local';
        } elseif ($activeState !== 'active') {
            $routeMode = 'stopped';
        }

        $connectivityMode = 'stopped';
        if ($activeState === 'active' && $internetReachable) {
            $connectivityMode = 'vpn';
        } elseif ($activeState === 'active') {
            $connectivityMode = 'degraded';
        } elseif ($ipForwardEnabled) {
            $connectivityMode = 'local';
        }

        return [
            'activeState' => $activeState,
            'ipForwardEnabled' => $ipForwardEnabled,
            'currentConnection' => $currentConnection,
            'routeMode' => $routeMode,
            'internetReachable' => $internetReachable,
            'publicIpAddress' => $publicIpAddress,
            'connectivityMode' => $connectivityMode,
            'exitCode' => $result['exitCode'],
            'rawOutput' => $result['output'],
        ];
    }

    public function listConnections(): array
    {
        $result = $this->runCommand(['ls']);
        $this->assertCommandSucceeded($result, 'Unable to list available VPN profiles.');
        $connections = [];

        foreach ($result['output'] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $active = false;
            if (strpos($line, '* ') === 0) {
                $active = true;
                $line = substr($line, 2);
            }

            $normalizedId = $this->normalizeConnectionId($line);

            if (!$this->isValidCommandArgument($normalizedId)) {
                continue;
            }

            $connections[] = [
                'id' => $normalizedId,
                'label' => $this->formatConnectionLabel($normalizedId),
                'active' => $active,
            ];
        }

        usort($connections, static function (array $left, array $right): int {
            return strcmp($left['label'], $right['label']);
        });

        return $connections;
    }

    public function getLogTail(int $limit = 80): array
    {
        $safeLimit = max(1, min(300, $limit));

        $command = 'tail -n ' . (int) $safeLimit . ' ' . escapeshellarg(self::OPENVPN_LOG_PATH) . ' 2>/dev/null';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return [];
        }

        return $output;
    }

    private function resolveSelectedAction(array $status): string
    {
        if ($status['activeState'] === 'active' && !empty($status['currentConnection'])) {
            return (string) $status['currentConnection'];
        }

        if ($status['activeState'] !== 'active' && $status['ipForwardEnabled']) {
            return 'stop-route-local';
        }

        return 'stop';
    }

    private function connectionExists(string $connectionId, array $connections): bool
    {
        foreach ($connections as $connection) {
            if (!empty($connection['id']) && $connection['id'] === $connectionId) {
                return true;
            }
        }

        return false;
    }

    private function normalizeConnectionId(string $connectionId): string
    {
        $connectionId = trim($connectionId);
        $connectionId = preg_replace('/\.conf$/', '', $connectionId);

        return (string) $connectionId;
    }

    private function formatConnectionLabel(string $connectionId): string
    {
        $label = str_replace(['_', '-'], ' ', $connectionId);
        $label = preg_replace('/\s+/', ' ', $label);

        return ucwords(trim((string) $label));
    }

    private function runCommand(array $arguments): array
    {
        $command = 'sudo ' . escapeshellarg(self::VPN_ADMIN_BINARY);

        foreach ($arguments as $argument) {
            if (!$this->isValidCommandArgument($argument)) {
                throw new InvalidArgumentException('Invalid command argument provided.');
            }
            $command .= ' ' . escapeshellarg($argument);
        }

        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        return [
            'output' => $output,
            'exitCode' => $exitCode,
            'command' => $command,
        ];
    }

    private function assertCommandSucceeded(array $result, string $errorMessage): void
    {
        if ((int) ($result['exitCode'] ?? 1) === 0) {
            return;
        }

        throw new RuntimeException($errorMessage);
    }

    private function isValidCommandArgument(string $argument): bool
    {
        return preg_match('/^[A-Za-z0-9._-]+$/', $argument) === 1;
    }
}
