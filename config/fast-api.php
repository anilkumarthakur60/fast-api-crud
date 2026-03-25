<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the package handles pagination for index endpoints.
    | The "type" option determines the default pagination strategy used
    | by BaseController (can be overridden per controller).
    |
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'allow_all' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Configuration
    |--------------------------------------------------------------------------
    |
    | When anonymize_unique_columns is true, unique column values get
    | _{timestamp} appended on soft delete to prevent constraint violations
    | when a new record is created with the same value.
    |
    */
    'soft_delete' => [
        'anonymize_unique_columns' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the JSON response envelope keys used by the ApiResponder trait.
    |
    */
    'response' => [
        'success_key' => 'data',
        'error_key' => 'errors',
        'message_key' => 'message',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, BaseController automatically registers Spatie permission
    | middleware for models that implement the HasPermissionSlug contract.
    | Requires spatie/laravel-permission to be installed.
    |
    */
    'permissions' => [
        'enabled' => true,
    ],
];
