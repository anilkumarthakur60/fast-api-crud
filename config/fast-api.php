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
    | "max_rows" caps how many IDs the bulk delete endpoint accepts in one
    | request (set to 0 to disable). "field" is the request key holding the
    | array of IDs to delete.
    |
    */
    'bulk' => [
        'max_rows' => 1000,
        'field'    => 'delete_rows',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Parameter Keys
    |--------------------------------------------------------------------------
    |
    | Rename any request key the index/show endpoints read, so the public API
    | matches your conventions. Defaults reproduce the pattern:
    |
    |   ?filters={"active":1,"include":"author,tags","trashed":"with"}
    |    &sortBy=created_at&descending=true&rowsPerPage=25&page=2&search=foo
    |
    | The first group are top-level query-string keys. "include" and "trashed"
    | are keys read from inside the filters JSON object.
    |
    */
    'query' => [
        'filters'    => 'filters',
        'search'     => 'search',
        'sort_by'    => 'sortBy',
        'descending' => 'descending',
        'per_page'   => 'rowsPerPage',
        'page'       => 'page',
        'cursor'     => 'cursor',

        // Read from inside the filters JSON object.
        'include' => 'include',
        'trashed' => 'trashed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sorting
    |--------------------------------------------------------------------------
    |
    | Applied by the index query when the request has no sort key and the model
    | does not implement the Sortable contract.
    |
    */
    'sorting' => [
        'default_column'     => 'id',
        'default_descending' => true,
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
