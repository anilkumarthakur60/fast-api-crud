<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Support;

use Closure;

/**
 * Static helpers for resolving pagination configuration from config and request.
 */
final class Pagination
{
    public static function configInt(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function configBool(string $key, bool $default): bool
    {
        $value = config($key, $default);

        return is_bool($value) ? $value : (bool) $value;
    }

    public static function requestedPerPage(int $default): int
    {
        $raw = request()->query(QueryParams::perPage(), $default);

        return is_numeric($raw) ? (int) $raw : $default;
    }

    /**
     * Resolve the effective per-page value from request + config.
     *
     * Returns 0 when "all records" is requested and allowed.
     */
    public static function resolvePerPage(): int
    {
        $defaultPerPage = self::configInt('fast-api.pagination.default_per_page', 15);
        $maxPerPage = self::configInt('fast-api.pagination.max_per_page', 100);
        $allowAll = self::configBool('fast-api.pagination.allow_all', true);
        $requested = self::requestedPerPage($defaultPerPage);

        if ($allowAll && $requested === 0) {
            return 0;
        }

        if ($requested <= 0) {
            return $defaultPerPage;
        }

        return min($requested, $maxPerPage);
    }

    public static function defaultPerPage(): int
    {
        return self::configInt('fast-api.pagination.default_per_page', 15);
    }

    public static function maxPerPage(): int
    {
        return self::configInt('fast-api.pagination.max_per_page', 100);
    }

    /**
     * Resolve per-page for offset-based paginators (paginates / simplePaginates).
     *
     * The $countFn closure is only invoked when "show all" is requested (rowsPerPage=0
     * and allow_all=true), avoiding an extra COUNT query on normal paginated requests.
     *
     * @param Closure(): int $countFn
     */
    public static function resolveEffectivePerPage(Closure $countFn): int
    {
        $perPage = self::resolvePerPage();

        if ($perPage === 0) {
            $count = $countFn();

            return $count > 0 ? $count : self::defaultPerPage();
        }

        return $perPage;
    }
}
