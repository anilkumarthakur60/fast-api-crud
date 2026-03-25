<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

trait HasDateScopes
{
    /**
     * Scope: records from today.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeToday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate("{$this->getTable()}.{$column}", Carbon::today());
    }

    /**
     * Scope: records from yesterday.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeYesterday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate("{$this->getTable()}.{$column}", Carbon::yesterday());
    }

    /**
     * Scope: records from this week (Monday to now).
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeThisWeek(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::now()->startOfWeek(), Carbon::now()],
        );
    }

    /**
     * Scope: records from last week (Monday to Sunday).
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeLastWeek(Builder $query, string $column = 'created_at'): Builder
    {
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();

        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [$startOfLastWeek, $endOfLastWeek],
        );
    }

    /**
     * Scope: records from the first day of the current month to now.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeMonthToDate(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::now()->startOfMonth(), Carbon::now()],
        );
    }

    /**
     * Scope: records from this month.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeThisMonth(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        );
    }

    /**
     * Scope: records from last month.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeLastMonth(Builder $query, string $column = 'created_at'): Builder
    {
        $startOfLastMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonthNoOverflow()->endOfMonth();

        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [$startOfLastMonth, $endOfLastMonth],
        );
    }

    /**
     * Scope: records from the first day of the current quarter to now.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeQuarterToDate(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::now()->startOfQuarter(), Carbon::now()],
        );
    }

    /**
     * Scope: records from the first day of the current year to now.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeYearToDate(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::now()->startOfYear(), Carbon::now()],
        );
    }

    /**
     * Scope: records from the last 7 days.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeLast7Days(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::today()->subDays(6), Carbon::now()],
        );
    }

    /**
     * Scope: records from the last 30 days.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeLast30Days(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::today()->subDays(29), Carbon::now()],
        );
    }

    /**
     * Scope: records from the previous quarter.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeLastQuarter(Builder $query, string $column = 'created_at'): Builder
    {
        $currentQuarterStart = Carbon::now()->startOfQuarter();
        $previousQuarterStart = $currentQuarterStart->copy()->subQuarter()->startOfQuarter();

        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [$previousQuarterStart, $currentQuarterStart],
        );
    }

    /**
     * Scope: records from the last 12 months.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeLastYear(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween(
            "{$this->getTable()}.{$column}",
            [Carbon::now()->subYear(), Carbon::now()],
        );
    }

    /**
     * Scope: records within a custom date range string "YYYY-MM-DD to YYYY-MM-DD".
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeDate(Builder $query, ?string $search, string $column = 'created_at'): Builder
    {
        if (! is_string($search) || trim($search) === '') {
            return $query;
        }

        $parts = explode(' to ', $search);
        $from = $parts[0] ?? '';
        $to = $parts[count($parts) - 1] ?? $from;

        try {
            $carbonFrom = Carbon::parse($from)->startOfDay()->toDateString();
            $carbonTo = Carbon::parse($to)->endOfDay()->toDateString();
        } catch (\Exception) {
            return $query;
        }

        return $query
            ->whereDate("{$this->getTable()}.{$column}", '>=', $carbonFrom)
            ->whereDate("{$this->getTable()}.{$column}", '<=', $carbonTo);
    }
}
