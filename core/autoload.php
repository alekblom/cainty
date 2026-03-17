<?php
/**
 * Cainty CMS — PSR-4-like Autoloader
 *
 * Maps Cainty\ namespace to core/ directory.
 * e.g. Cainty\Database\Database -> core/database/Database.php
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'Cainty\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    // Convert namespace separators to directory separators
    // and lowercase the directory parts (not the class file)
    $parts = explode('\\', $relativeClass);
    $classFile = array_pop($parts);

    $directories = array_map('strtolower', $parts);
    $path = CAINTY_ROOT . '/core/' . implode('/', $directories) . '/' . $classFile . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});
