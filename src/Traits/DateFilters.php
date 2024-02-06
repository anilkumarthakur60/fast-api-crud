<?php

namespace Anil\FastApiCrud\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait DateFilters
{
    public function scopeToday(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereDate($this->getTable().'.'.$column, Carbon::today());
    }

    public function scopeYesterday(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereDate($this->getTable().'.'.$column, Carbon::yesterday());
    }

    public function scopeMonthToDate(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::now()->startOfMonth(), Carbon::now()]);
    }

    public function scopeQuarterToDate(Builder $query, $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween($this->getTable().'.'.$column, [$now->startOfQuarter(), $now]);
    }

    public function scopeYearToDate(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::now()->startOfYear(), Carbon::now()]);
    }

    public function scopeLast7Days(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::today()->subDays(6), Carbon::now()]);
    }

    public function scopeLast30Days(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::today()->subDays(29), Carbon::now()]);
    }

    public function scopeLastQuarter(Builder $query, $column = 'created_at'): Builder
    {
        $now = Carbon::now();

        return $query->whereBetween($this->getTable().'.'.$column, [$now->startOfQuarter()->subMonths(3), $now->startOfQuarter()]);
    }

    public function scopeLastYear(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($this->getTable().'.'.$column, [Carbon::now()->subYear(), Carbon::now()]);
    }

    public function scopeDate($query, $search, $column = 'created_at')
    {
        return empty($search) ? $query : $query
            ->whereDate($this->getTable().'.'.$column, '>=', Carbon::parse(current(explode(' to ', $search)))
                ->startOfDay()
                ->toDateString())
            ->whereDate($this->getTable().'.'.$column, '<=', Carbon::parse(last(explode(' to ', $search)))->endOfDay()->toDateString());
    }
}
