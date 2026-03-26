# Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Anil\FastApiCrud\FastApiCrudServiceProvider" --tag=config
```

## Full Config Reference

```php
// config/fast-api.php

return [
    'pagination' => [
        'default_per_page' => 15,    // Default records per page
        'max_per_page'     => 100,   // Maximum allowed via ?rowsPerPage
        'allow_all'        => true,  // Allow ?rowsPerPage=0 to return all records
    ],

    'soft_delete' => [
        'anonymize_unique_columns' => true,
        // When true, unique columns get _{timestamp} appended on soft delete
        // to prevent constraint violations when recreating records
    ],

    'response' => [
        'success_key' => 'data',     // Envelope key for success responses
        'error_key'   => 'errors',   // Envelope key for error data
        'message_key' => 'message',  // Envelope key for error messages
    ],

    'permissions' => [
        'enabled' => true,
        // When true, auto-registers Spatie permission middleware
        // for models implementing HasPermissionSlug
    ],

    'web' => [
        'flash_key_success' => 'success',  // Session key for success flash
        'flash_key_error'   => 'error',    // Session key for error flash
    ],
];
```

## Pagination

The `rowsPerPage` query parameter controls pagination:

```
GET /posts?rowsPerPage=25    → 25 per page
GET /posts?rowsPerPage=0     → All records (if allow_all is true)
GET /posts?rowsPerPage=999   → Clamped to max_per_page (100)
GET /posts                   → Uses default_per_page (15)
```

## Response Envelope

The response keys are configurable. Default responses look like:

**Success:**
```json
{
  "data": { ... }
}
```

**Error:**
```json
{
  "errors": [],
  "message": "Something went wrong"
}
```

Change the keys:

```php
'response' => [
    'success_key' => 'result',
    'error_key'   => 'error_data',
    'message_key' => 'error_message',
],
```

Now responses use:
```json
{
  "result": { ... }
}
```

## Soft Delete Anonymization

When `anonymize_unique_columns` is `true` and a model with `SoftDeletes` is deleted:

```
Before: email = "john@example.com"
After:  email = "john@example.com_1705312800"
```

This prevents unique constraint violations when creating a new record with the same email while the old one is soft-deleted.

Requires the model to use the `HandlesDeleteEvents` trait. See [Model Traits](./model-traits#handlesdeleteevents).

## Web Flash Keys

The `BaseWebController` uses these config keys for session flash messages:

::: v-pre
```blade
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
```
:::

If you change the keys:

```php
'web' => [
    'flash_key_success' => 'status',
    'flash_key_error'   => 'warning',
],
```

Update your Blade templates to use `session('status')` and `session('warning')`.
