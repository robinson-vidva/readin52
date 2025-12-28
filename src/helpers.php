<?php
/**
 * Helper functions
 */

/**
 * Escape output for HTML
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Get request method
 */
function getMethod(): string
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Check if request is POST
 */
function isPost(): bool
{
    return getMethod() === 'POST';
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get POST data
 */
function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 */
function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Render a template
 */
function render(string $template, array $data = []): void
{
    extract($data);
    $templatePath = TEMPLATE_PATH . '/' . $template . '.php';

    if (!file_exists($templatePath)) {
        throw new Exception("Template not found: $template");
    }

    require $templatePath;
}

/**
 * Get base URL
 */
function baseUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * Generate URL
 */
function url(string $path = ''): string
{
    return rtrim(baseUrl(), '/') . '/' . ltrim($path, '/');
}

/**
 * Generate asset URL
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Flash message functions
 */
function setFlash(string $type, string $message): void
{
    Auth::startSession();
    $_SESSION['flash'][$type] = $message;
}

function getFlash(string $type): ?string
{
    Auth::startSession();
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function hasFlash(string $type): bool
{
    Auth::startSession();
    return isset($_SESSION['flash'][$type]);
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'M j, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Format relative time
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

/**
 * Get current route
 */
function currentRoute(): string
{
    return $_GET['route'] ?? '';
}

/**
 * Check if current route matches
 */
function isRoute(string $route): bool
{
    return currentRoute() === ltrim($route, '/');
}

/**
 * Active class helper
 */
function activeClass(string $route, string $class = 'active'): string
{
    return isRoute($route) ? $class : '';
}

/**
 * Generate CSRF input field
 */
function csrfField(): string
{
    $token = Auth::generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e($token) . '">';
}

/**
 * Validate CSRF token from POST
 */
function validateCsrf(): bool
{
    $token = post(CSRF_TOKEN_NAME, '');
    return Auth::verifyCsrfToken($token);
}

/**
 * Truncate string
 */
function truncate(string $string, int $length = 100, string $append = '...'): string
{
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $append;
}

/**
 * Get client IP address
 */
function getClientIp(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string
{
    return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
}

/**
 * Generate random string
 */
function randomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get user's preferred translation
 */
function getPreferredTranslation(): string
{
    $user = Auth::getUser();
    return $user['preferred_translation'] ?? DEFAULT_TRANSLATION;
}

/**
 * Get user initials from name
 */
function getUserInitials(string $name): string
{
    $name = trim($name);
    if (empty($name)) {
        return '?';
    }

    $parts = preg_split('/\s+/', $name);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Get avatar color based on name (consistent color per user)
 */
function getAvatarColor(string $name): string
{
    $colors = [
        '#5D4037', // Brown
        '#1565C0', // Blue
        '#2E7D32', // Green
        '#C62828', // Red
        '#6A1B9A', // Purple
        '#EF6C00', // Orange
        '#00838F', // Teal
        '#AD1457', // Pink
        '#37474F', // Blue Grey
        '#558B2F', // Light Green
    ];

    $hash = crc32(strtolower(trim($name)));
    return $colors[abs($hash) % count($colors)];
}
