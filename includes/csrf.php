<?php
/**
 * ============================================
 * CSRF Token Protection
 * ============================================
 *
 * Generates and validates CSRF tokens for form submissions.
 * Prevents cross-site request forgery attacks.
 *
 * @package  DataForge
 */

/**
 * Generate a CSRF token and store it in the session.
 *
 * @return string The generated CSRF token.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden HTML input field with the CSRF token.
 *
 * @return void
 */
function csrfField(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Validate the CSRF token from a form submission.
 *
 * @param string $token The token submitted with the form.
 * @return bool True if the token is valid, false otherwise.
 */
function validateCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    // Regenerate token after validation to prevent reuse
    unset($_SESSION['csrf_token']);
    return $valid;
}

/**
 * Validate CSRF token from POST request. Redirects with error on failure.
 *
 * @param string $redirectUrl URL to redirect to on failure.
 * @return void
 */
function requireCsrfToken(string $redirectUrl = '/'): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        setFlash('error', 'Token de seguridad inválido. Intenta de nuevo.');
        header('Location: ' . $redirectUrl);
        exit();
    }
}
