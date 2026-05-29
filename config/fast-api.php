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
        'max_per_page'     => 100,
        'allow_all'        => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Configuration
    |--------------------------------------------------------------------------
    |
    | The maximum number of IDs accepted by the bulk delete endpoint in a
    | single request. Set to 0 (or any value <= 0) to disable the limit.
    |
    */
    'bulk' => [
        'max_rows' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Parameters
    |--------------------------------------------------------------------------
    |
    | The keys, read from inside the "filters" JSON object, that drive
    | client-side eager loading and soft-delete filtering on index/show.
    | e.g. ?filters={"include":"author,tags","trashed":"with"}
    |
    */
    'query' => [
        'include' => 'include',
        'trashed' => 'trashed',
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
    | Customize the JSON response envelope keys used by the HasApiResponse trait.
    |
    */
    'response' => [
        'success_key' => 'data',
        'error_key'   => 'errors',
        'message_key' => 'message',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, permissionMiddleware() returns middleware definitions for
    | controllers that call it from their static middleware() method.
    | Requires spatie/laravel-permission to be installed.
    |
    */
    'permissions' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Web (Blade) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the flash message session keys used by BaseWebController
    | when redirecting after CRUD operations.
    |
    */
    'web' => [
        'flash_key_success' => 'success',
        'flash_key_error'   => 'error',
    ],
];
