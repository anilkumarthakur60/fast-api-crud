<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Contracts;

/**
 * Implement this interface on Eloquent models to provide a permission slug.
 *
 * The slug is used by the static permissionMiddleware() helper in BaseController
 * and BaseWebController to generate Spatie permission middleware definitions.
 *
 * Usage in a controller:
 *   public static function middleware(): array
 *   {
 *       return static::permissionMiddleware((new Post)->getPermissionSlug());
 *   }
 */
interface HasPermissionSlug
{
    /**
     * Return the permission slug used to build middleware names.
     *
     * Example: returning "posts" generates middleware like
     * "permission:view-posts", "permission:store-posts", etc.
     */
    public function getPermissionSlug(): string;
}
