<?php
/**
 * ============================================
 * Flash Message System
 * ============================================
 *
 * Session-based flash messages for displaying one-time notifications.
 * Supports: success, error, warning, info types.
 *
 * @package  DataForge
 */

/**
 * Set a flash message in the session.
 *
 * @param string $type    Message type: 'success', 'error', 'warning', 'info'.
 * @param string $message The message text.
 * @return void
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Display and clear the flash message if one exists.
 * Outputs a styled toast notification div.
 *
 * @return void
 */
function displayFlash(): void
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);

        $iconMap = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];
        $icon = $iconMap[$type] ?? 'ℹ';

        echo <<<HTML
        <div class="toast toast-{$type}" id="flashToast" role="alert">
            <span class="toast-icon">{$icon}</span>
            <span class="toast-message">{$message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('flashToast');
                if (toast) {
                    toast.classList.add('toast-fade-out');
                    setTimeout(() => toast.remove(), 500);
                }
            }, 5000);
        </script>
        HTML;

        unset($_SESSION['flash']);
    }
}
