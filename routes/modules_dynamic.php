<?php

declare(strict_types=1);

// Loads module route files, then per-page route files under each module's pages directory.

foreach (glob(base_path('modules/*/web.php')) ?: [] as $routesFile) {
    try {
        require $routesFile;
    } catch (Throwable $e) {
        report($e);
    }
}

foreach (glob(base_path('modules/*/pages/*/web.php')) ?: [] as $routesFile) {
    try {
        require $routesFile;
    } catch (Throwable $e) {
        report($e);
    }
}
