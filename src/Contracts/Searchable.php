<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Contracts;

/**
 * Implement this interface on Eloquent models to enable
 * automatic search support in BaseController's index action.
 *
 * When the request contains a "search" parameter, the controller
 * will apply a LIKE query across the columns returned by this method.
 */
interface Searchable
{
    /**
     * Return the columns that should be searched.
     *
     * Supports relation columns using colon syntax: 'relation:column1,column2'
     *
     * @return array<int, string>
     */
    public function searchableColumns(): array;
}
