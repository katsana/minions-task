<?php

namespace Minions\Task\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class TaskStatusFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed                                 $value
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        $completed = ['completed', 'cancelled', 'failed'];

        if (\in_array($value, $completed)) {
            return $query->whereIn('status', [$value]);
        }

        return $query->whereNotIn('status', $completed);
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(Request $request)
    {
        return [
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
            'Failed' => 'failed',
            'Pending' => 'pending',
        ];
    }
}
