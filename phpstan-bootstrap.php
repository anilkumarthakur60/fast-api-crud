<?php

/**
 * PHPStan bootstrap file that registers package macros so Larastan
 * can detect them via runtime reflection during static analysis.
 */

use Anil\FastApiCrud\Macros\BuilderMacros;
use Anil\FastApiCrud\Macros\CollectionMacros;
use Illuminate\Database\Eloquent\Builder;

BuilderMacros::register();
CollectionMacros::register();

// Register SoftDeletes query builder methods so PHPStan recognises them
// on generic Builder<Model> (at runtime, SoftDeletingScope provides these).
if (! Builder::hasGlobalMacro('onlyTrashed')) {
    Builder::macro('onlyTrashed', fn (): Builder => $this);
}
if (! Builder::hasGlobalMacro('restore')) {
    Builder::macro('restore', fn (): int => 0);
}
