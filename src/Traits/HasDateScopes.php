<?php

namespace Anil\FastApiCrud\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasDateScopes
{
    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeToday(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereDate($this->getTable().'.'.$column, Carbon::today());
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeYesterday(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereDate($this->getTable().'.'.$column, Carbon::yesterday());
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeMonthToDate(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::now()->startOfMonth(), Carbon::now()]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeQuarterToDate(Builder $query, $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween($this->getTable().'.'.$column, [$now->startOfQuarter(), $now]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeYearToDate(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::now()->startOfYear(), Carbon::now()]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeLast7Days(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::today()->subDays(6), Carbon::now()]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeLast30Days(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::today()->subDays(29), Carbon::now()]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeLastQuarter(Builder $query, $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween($this->getTable().'.'.$column, [$now->startOfQuarter()->subMonths(3), $now->startOfQuarter()]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeLastYear(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::now()->subYear(), Carbon::now()]);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string  $search
     * @param  string  $column
     * @return Builder<Model>
     */
    public function scopeDate($query, $search, $column = 'created_at')
    {
        $from = current(explode(' to ', $search));
        $carbonFrom = Carbon::parse($from)->startOfDay()->toDateString();
        $to = last(explode(' to ', $search));
        $carbonTo = Carbon::parse($to)->endOfDay()->toDateString();

        return empty($search) ? $query : $query
            ->whereDate($this->getTable().'.'.$column, '>=', $carbonFrom)
            ->whereDate($this->getTable().'.'.$column, '<=', $carbonTo);
    }
}
