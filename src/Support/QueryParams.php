<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Support;

/**
 * Resolves the request key names used by the CRUD query features.
 *
 * Every key is configurable via config('fast-api.query.*'); the defaults
 * reproduce the conventional pattern (filters / sortBy / descending /
 * rowsPerPage / page / cursor / search, with include + trashed read from
 * inside the filters JSON).
 */
final class QueryParams
{
    public static function filters(): string
    {
        return self::key('filters', 'filters');
    }

    public static function search(): string
    {
        return self::key('search', 'search');
    }

    public static function sortBy(): string
    {
        return self::key('sort_by', 'sortBy');
    }

    public static function descending(): string
    {
        return self::key('descending', 'descending');
    }

    public static function perPage(): string
    {
        return self::key('per_page', 'rowsPerPage');
    }

    public static function page(): string
    {
        return self::key('page', 'page');
    }

    public static function cursor(): string
    {
        return self::key('cursor', 'cursor');
    }

    public static function includes(): string
    {
        return self::key('include', 'include');
    }

    public static function trashed(): string
    {
        return self::key('trashed', 'trashed');
    }

    private static function key(string $name, string $default): string
    {
        $value = config("fast-api.query.{$name}");

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
