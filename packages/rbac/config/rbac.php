<?php

declare(strict_types=1);

return [
    'super_role' => 'super-admin',

    // When true, the super role bypasses every Gate check.
    'super_bypasses_all' => true,

    // Sync capabilities to DB at boot (in addition to in-memory registration).
    'sync_to_db' => env('ACME_RBAC_SYNC_DB', true),
];
