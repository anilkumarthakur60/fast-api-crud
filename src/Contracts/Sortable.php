<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Contracts;

/**
 * Implement this interface on Eloquent models to provide
 * default sort configuration for the initializer() macro.
 */
interface Sortable
{
    /**
     * Return default sort configuration.
     *
     * @return array{sortBy: string, sortByDesc: bool}
     */
    public function sortByDefaults(): array;
}
