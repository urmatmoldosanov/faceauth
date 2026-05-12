<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/UserStore.php';

function faceauth_user_store(): UserStore
{
    return new UserStore(Config::get('FACEAUTH_USERS_FILE', __DIR__ . '/../storage/users.json'));
}

function faceauth_current_user(): ?array
{
    $providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
    $providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

    $store = faceauth_user_store();
    if ($store->isInstalled()) {
        return $store->verify($providedUser, $providedPass);
    }

    $adminUser = Config::get('FACEAUTH_ADMIN_USER');
    $adminPassHash = Config::get('FACEAUTH_ADMIN_PASS_HASH');
    if ($adminUser !== null && $adminPassHash !== null && $providedUser === $adminUser && password_verify($providedPass, $adminPassHash)) {
        return ['username' => $adminUser, 'role' => 'superadmin', 'source' => 'env'];
    }

    $viewerUser = Config::get('FACEAUTH_VIEWER_USER');
    $viewerPassHash = Config::get('FACEAUTH_VIEWER_PASS_HASH');
    if ($viewerUser !== null && $viewerPassHash !== null && $providedUser === $viewerUser && password_verify($providedPass, $viewerPassHash)) {
        return ['username' => $viewerUser, 'role' => 'viewer', 'source' => 'env'];
    }

    return null;
}

function faceauth_require_user(): array
{
    $user = faceauth_current_user();
    if ($user !== null) {
        return $user;
    }

    header('WWW-Authenticate: Basic realm="FaceAuth Backend Admin"');
    http_response_code(401);
    echo 'Authentication required';
    exit;
}

function faceauth_can_manage_users(array $user): bool
{
    return ($user['role'] ?? '') === 'superadmin';
}
