<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module registry log path
    |--------------------------------------------------------------------------
    |
    | When you add a module in Module settings, a line is appended here in the
    | form: alias,DisplayName,sequence@ (same idea as sysql_module.log).
    |
    | Deleted modules are kept as #deleted,alias,DisplayName,sequence,is_active,sidebar@ lines
    | at the bottom of the file so you can copy them to another machine.
    |
    | Default: storage/logs/sys_module.log
    |
    | The sidebar reads the database, not this file. The log is for your own
    | "fetch modules" / tooling. Vite/npm build does not read or write this file.
    |
    */
    'registry_log' => storage_path('logs/sys_module.log'),

    /*
    |--------------------------------------------------------------------------
    | Pages registry log (WealthInvest sysql_pages.log format)
    |--------------------------------------------------------------------------
    |
    | Each line: page_name, page_alias, module_id, sequence_number, icon, is_active, module_alias@
    | (commas with spaces; module_alias is required for migration — module_id differs per machine.)
    |
    | Deleted pages are kept as #deleted, ... , module_alias@ lines at the bottom for migration.
    |
    | Twist vs a flat module log: the third field is the parent module's **id**
    | from the `modules` table (FK), not the module slug. Files live under:
    | modules/{module_alias}/pages/{page_alias}/
    |
    */
    'pages_registry_log' => storage_path('logs/sysql_pages.log'),

];
