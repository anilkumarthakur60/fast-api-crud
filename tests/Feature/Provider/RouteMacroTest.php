<?php

use Illuminate\Support\Facades\Route;

describe('fastApiResource route macro', function () {
    it('registers the full CRUD route set', function () {
        $actions = [
            'index', 'store', 'delete', 'restoreAll', 'updateColumn',
            'changeStatus', 'restore', 'permanentDelete', 'show', 'update', 'destroy',
        ];

        foreach ($actions as $action) {
            expect(Route::has("widgets.{$action}"))->toBeTrue("missing route widgets.{$action}");
        }
    });

    it('maps verbs and URIs correctly', function () {
        $routes = Route::getRoutes();

        $show = $routes->getByName('widgets.show');
        expect($show->uri())->toBe('widgets/{id}')
            ->and($show->methods())->toContain('GET');

        $update = $routes->getByName('widgets.update');
        expect($update->uri())->toBe('widgets/{id}')
            ->and($update->methods())->toContain('PUT')
            ->and($update->methods())->toContain('PATCH');

        $status = $routes->getByName('widgets.changeStatus');
        expect($status->uri())->toBe('widgets/{id}/status')
            ->and($status->methods())->toContain('PATCH');

        $column = $routes->getByName('widgets.updateColumn');
        expect($column->uri())->toBe('widgets/{id}/status/{column}');

        $force = $routes->getByName('widgets.permanentDelete');
        expect($force->uri())->toBe('widgets/{id}/force')
            ->and($force->methods())->toContain('DELETE');
    });

    it('respects the only option', function () {
        expect(Route::has('gadgets.index'))->toBeTrue()
            ->and(Route::has('gadgets.show'))->toBeTrue()
            ->and(Route::has('gadgets.store'))->toBeFalse()
            ->and(Route::has('gadgets.destroy'))->toBeFalse();
    });

    it('respects the except option', function () {
        expect(Route::has('gizmos.index'))->toBeTrue()
            ->and(Route::has('gizmos.update'))->toBeTrue()
            ->and(Route::has('gizmos.delete'))->toBeFalse()
            ->and(Route::has('gizmos.restoreAll'))->toBeFalse();
    });
});
