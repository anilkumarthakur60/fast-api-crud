<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Contracts;

/**
 * Implement this interface on Eloquent models to enable
 * automatic Spatie permission middleware registration
 * on BaseController CRUD actions.
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
