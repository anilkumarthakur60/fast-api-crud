<?php

namespace Anil\FastApiCrud\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasDateScopes
{
    /**
     * Scope a query to only include models where the given column's date is today.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeToday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate("{$this->getTable()}.{$column}", Carbon::today());
    }

    /**
     * Scope a query to only include models where the given column's date is yesterday.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeYesterday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate("{$this->getTable()}.{$column}", Carbon::yesterday());
    }

    /**
     * Scope a query to include models where the given column's date is between
     * the first day of the current month and now.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeMonthToDate(Builder $query, string $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [$now->startOfMonth(), $now]
        );
    }

    /**
     * Scope a query to include models where the given column's date is between
     * the first day of the current quarter and now.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeQuarterToDate(Builder $query, string $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [$now->startOfQuarter(), $now]
        );
    }

    /**
     * Scope a query to include models where the given column's date is between
     * the first day of the current year and now.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeYearToDate(Builder $query, string $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [$now->startOfYear(), $now]
        );
    }

    /**
     * Scope a query to include models where the given column's date is between
     * seven days ago (inclusive) and now.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeLast7Days(Builder $query, string $column = 'created_at'): Builder
    {
        $start = Carbon::today()->subDays(6);
        $end = Carbon::now();

        return $query->whereBetween("{$this->getTable()}.{$column}", [$start, $end]);
    }

    /**
     * Scope a query to include models where the given column's date is between
     * thirty days ago (inclusive) and now.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeLast30Days(Builder $query, string $column = 'created_at'): Builder
    {
        $start = Carbon::today()->subDays(29);
        $end = Carbon::now();

        return $query->whereBetween("{$this->getTable()}.{$column}", [$start, $end]);
    }

    /**
     * Scope a query to include models where the given column's date is between
     * the first day of the previous quarter and the first day of the current quarter.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeLastQuarter(Builder $query, string $column = 'created_at'): Builder
    {
        $now = Carbon::now();
        $startQ = $now->startOfQuarter()->subMonths(3);
        $startCurr = $now->copy()->startOfQuarter();

        return $query->whereBetween("{$this->getTable()}.{$column}", [$startQ, $startCurr]);
    }

    /**
     * Scope a query to include models where the given column's date is between
     * one year ago (inclusive) and now.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeLastYear(Builder $query, string $column = 'created_at'): Builder
    {
        $start = Carbon::now()->subYear();
        $end = Carbon::now();

        return $query->whereBetween("{$this->getTable()}.{$column}", [$start, $end]);
    }

    /**
     * Scope a query to include models where the given column's date falls within
     * a custom date range string formatted as "YYYY-MM-DD to YYYY-MM-DD".
     *
     * The method will:
     * 1. Return the unmodified query if $search is null, empty, or not a string.
     * 2. Split $search by the literal " to " substring.
     * 3. Parse the first part as the "from" date, set time to start of day.
     * 4. Parse the last part as the "to" date, set time to end of day.
     *
     * @param  Builder<Model>  $query  Eloquent query builder instance.
     * @param  string|null  $search  Date range string, e.g. "2025-01-01 to 2025-01-31".
     * @param  string  $column  Column name to filter by (default: "created_at").
     * @return Builder<Model> Modified query builder.
     */
    public function scopeDate(Builder $query, ?string $search, string $column = 'created_at'): Builder
    {
        if (! is_string($search) || trim($search) === '') {
            return $query;
        }

        // Split on " to ", but allow missing second part
        $parts = explode(' to ', $search);
        $from = $parts[0] ?? '';
        $to = $parts[count($parts) - 1] ?? $from;

        try {
            $carbonFrom = Carbon::parse($from)->startOfDay()->toDateString();
            $carbonTo = Carbon::parse($to)->endOfDay()->toDateString();
        } catch (\Exception $e) {
            // If parsing fails, do not apply any date filtering
            return $query;
        }

        return $query
            ->whereDate("{$this->getTable()}.{$column}", '>=', $carbonFrom)
            ->whereDate("{$this->getTable()}.{$column}", '<=', $carbonTo);
    }
}
