<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Macros;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Registers Collection macros provided by the package.
 */
final class CollectionMacros
{
    public static function register(): void
    {
        self::registerPaginate();
    }

    private static function registerPaginate(): void
    {
        /**
         * Paginate an in-memory collection.
         *
         * @return Paginator
         */
        Collection::macro('paginate', function (int $perPage, ?int $total = null, ?int $page = null, string $pageName = 'page'): Paginator {
            /** @var Collection<array-key, mixed> $this */
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }
}
