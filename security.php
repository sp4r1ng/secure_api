<?php
require_once __DIR__ . '/db.php';

const APP_SESSION_NAME = 'photo_atb_session';
const ROLE_USER = 'user';
const ROLE_ADMIN = 'admin';
const ROLE_SUPERADMIN = 'superadmin';

/**
 * Start a secure PHP session with appropriate cookie settings
 * @return void
 */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name(APP_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Standardized JSON response with HTTP code
 * @param array $payload response data
 * @param int $statusCode HTTP code (200 by default)
 * @return void
 */
function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

/**
 * Function to retrieve the currently logged-in user from the server session
 * @return array|null User data or null if invalid or not logged in
 */
function getCurrentUserFromSession(): ?array
{
    secure_session_start();

    $uuid = $_SESSION['user_uuid'] ?? null;
    if (!$uuid) {
        return null;
    }
    // Fetch user data from the database using the UUID stored in the session
    $user = DB::fetch(
        "SELECT uuid, userId, name, firstName, mode, blocked FROM USER WHERE uuid = :uuid",
        ['uuid' => $uuid]
    );

    if (!$user || (int) $user['blocked'] === 1) {
        unset($_SESSION['user_uuid']);
        return null;
    }

    return $user;
}

/**
 * Function to require login for a page
 * @param string $redirectTo URL of the page to redirect to if not logged in
 * @return array The logged-in user or null if not logged in
 */
function require_login_for_page(string $redirectTo = '/connexion.php'): array
{
    $user = getCurrentUserFromSession();
    if (!$user) {
        header('Location: ' . $redirectTo);
        exit;
    }

    return $user;
}

/**
 * Function to require login for API endpoints
 * @return array The logged-in user or null if not logged in
 */
function require_login_for_api(): array
{
    $user = getCurrentUserFromSession();
    if (!$user) {
        json_response(['status' => 'error', 'message' => 'Authentification requise'], 401);
    }

    return $user;
}

/**
 * Function to check if a user has an allowed role
 * @param array $user The current user
 * @param array $allowedRoles The list of allowed roles
 * @return bool
 */
function user_has_role(array $user, array $allowedRoles): bool
{
    return in_array($user['mode'] ?? '', $allowedRoles, true);
}

/**
 * Function to require specific roles for a page
 * @param array $allowedRoles The list of allowed roles
 * @param string $redirectTo The URL to redirect to if the user does not have the required role
 * @return array The logged-in user or null if not logged in
 */
function require_roles_for_page(array $allowedRoles, string $redirectTo = '/donnee.php'): array
{
    $user = require_login_for_page();
    if (!user_has_role($user, $allowedRoles)) {
        header('Location: ' . $redirectTo);
        exit;
    }

    return $user;
}

/**
 * Function to require specific roles for API endpoints
 * @param array $allowedRoles The list of allowed roles
 * @return array The logged-in user or null if not logged in
 */
function require_roles_for_api(array $allowedRoles): array
{
    $user = require_login_for_api();
    if (!user_has_role($user, $allowedRoles)) {
        json_response(['status' => 'error', 'message' => 'Accès interdit'], 403);
    }

    return $user;
}

/**
 * Login an user in the session and regenerate the session ID
 * @param array $user The logged-in user
 * @return void
 */
function login_user(array $user): void
{
    secure_session_start();
    session_regenerate_id(true);
    $_SESSION['user_uuid'] = $user['uuid'];
}

/**
 * Disconnect the user by deleting the server session/cookie
 * @return void
 */
function logout_user(): void
{
    secure_session_start();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}
?>
