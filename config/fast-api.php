<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'allow_all' => true, // allow rowsPerPage=0 to return all records
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Configuration
    |--------------------------------------------------------------------------
    */
    'soft_delete' => [
        // Append _{timestamp} to unique column values on soft delete
        // to prevent unique constraint violations on restore
        'anonymize_unique_columns' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
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
    */
    'permissions' => [
        // Automatically register Spatie permission middleware when model
        // defines getPermissionSlug(). Requires spatie/laravel-permission.
        'enabled' => true,
    ],
];
