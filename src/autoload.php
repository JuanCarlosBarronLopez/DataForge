<?php
/**
 * ============================================
 * DataForge — PSR-4 Style Autoloader
 * ============================================
 *
 * Simple class autoloader for the DataForge\* namespace.
 * Maps DataForge\ClassName to src/ClassName.php
 *
 * No Composer dependency required for runtime.
 *
 * @package  DataForge
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'DataForge\\';
    $baseDir = __DIR__ . '/';

    // Check if the class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relativeClass = substr($class, $len);

    // Map namespace separators to directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
