<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Auto Configuration
    |--------------------------------------------------------------------------
    |
    | Auto table engine for dynamic schema creation.
    | Drives AutoTableCreator for JSON-driven dynamic table creation.
    |
    */

    'enabled' => env('DB_AUTO_ENABLED', false),

    'schemas_path' => env('DB_AUTO_SCHEMAS_PATH', storage_path('schemas')),

    'default_engine' => env('DB_AUTO_DEFAULT_ENGINE', 'InnoDB'),

    'index_strategy' => env('DB_AUTO_INDEX_STRATEGY', 'auto'),
];
