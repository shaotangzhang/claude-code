<?php

declare(strict_types=1);

return [
    // How many historical PageVersions to keep per Page; null = unlimited.
    'version_history_limit' => env('ACME_CMS_VERSION_LIMIT'),

    // When user opens the editor on a published page, automatically spawn
    // a draft version they can mutate.
    'auto_draft_on_edit' => true,
];
