<?php
// @changed 2026-04-24 — delegates to Auth; removed hardcoded credentials
declare(strict_types=1);

namespace Phppot;

require_once __DIR__ . '/../lib/bootstrap.php';

class Member
{
    public function getMemberById($memberId): array
    {
        return [
            'id' => (int) $memberId,
            'username' => \Auth::currentUsername(),
        ];
    }

    public function processLogin($username, $password): bool
    {
        $errorCode = null;
        return \Auth::attemptLogin((string) $username, (string) $password, $errorCode);
    }
}
