<?php

declare(strict_types=1);

namespace Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HttpFilter
{
    /**
     * Column names are alphanumeric strings that can contain
     * underscores (`_`) but can't start with a number.
     */
    private const VALID_COLUMN_NAME_REGEX = '/^(?![\d])[A-Za-z0-9_>-]*$/';
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Collection
     */
    protected $filters;
    /**
     * @var Collection
     */
    protected $sorts;
    /**
     * Model options and allowed params.
     *
     * @var Collection
     */
    protected $options;

    /**
     * Filter constructor.
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? request();

        $this->filters = $this->request->collect('filter')
            ->map(fn ($item) => $this->parseHttpValue($item))
            ->filter(fn ($item) => $item !== null);

        $this->sorts = collect($this->request->get('sort', []));
    }

    /**
     * @param string|null|array $query
     *
     * @return string|array|null
     */
    protected function parseHttpValue($query)
    {
        if (is_string($query)) {
            $item = explode(',', $query);

            if (count($item) > 1) {
                return $item;
            }
        }

        return $query;
    }

    public static function sanitize(string $column): string
    {
        abort_unless(preg_match(self::VALID_COLUMN_NAME_REGEX, $column), Response::HTTP_BAD_REQUEST);

        return $column;
    }

    public function build(Builder $builder): Builder
    {
        $this->options = $builder->getModel()->getOptionsFilter();

        $this->addFiltersToQuery($builder);
        $this->addSortsToQuery($builder);

        return $builder;
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return mixed
     */
    protected function addFiltersToQuery(Builder $builder)
    {
        $this->automaticFiltersExact($builder);

        $allowedFilters = $this->options->get('allowedFilters')
            ->filter(fn ($value, $key) => ! is_int($key))
            ->map(fn ($filter, string $column) => app()->make($filter, ['column' => $column]));

        return $builder->filtersApply($allowedFilters->toArray());
    }

    /**
     * @deprecated
     *
     * @return void
     */
    protected function automaticFiltersExact(Builder $builder)
    {
        $allowedAutomaticFilters = $this->options->get('allowedFilters')
            ->filter(fn ($value, $key) => is_int($key));

        $this->filters->each(function ($value, $property) use ($builder, $allowedAutomaticFilters) {
            $allowProperty = $property;

            if (str_contains($property, '.')) {
                $allowProperty = strstr($property, '.', true);
            }

            if ($allowedAutomaticFilters->contains($allowProperty)) {
                $property = str_replace('.', '->', $property);
                $this->filtersExact($builder, $value, $property);
            }
        });
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     */
    protected function filtersExact(Builder $query, $value, string $property): Builder
    {
        $property = self::sanitize($property);
        $model = $query->getModel();

        if ($this->isDate($model, $property)) {
            $query->when($value['start'] ?? null, fn (Builder $query) => $query->whereDate($property, '>=', $value['start']));
            $query->when($value['end'] ?? null, fn (Builder $query) => $query->whereDate($property, '<=', $value['end']));
        } elseif (is_array($value) && (isset($value['min']) || isset($value['max']))) {
            $query->when($value['min'] ?? null, fn (Builder $query) => $query->where($property, '>=', $value['min']));
            $query->when($value['max'] ?? null, fn (Builder $query) => $query->where($property, '<=', $value['max']));
        } elseif (is_array($value)) {
            $query->whereIn($property, $value);
        } elseif ($model->hasCast($property, ['bool', 'boolean'])) {
            $query->where($property, (bool) $value);
        } elseif (is_numeric($value) && ! $model->hasCast($property, ['string'])) {
            $query->where($property, $value);
        } else {
            $query->where($property, 'like', "%$value%");
        }

        return $query;
    }

    protected function addSortsToQuery(Builder $builder)
    {
        $allowedSorts = $this->options->get('allowedSorts');

        $this->sorts
            ->each(function (string $sort) use ($builder, $allowedSorts) {
                $descending = str_starts_with($sort, '-');
                $key = ltrim($sort, '-');
                $property = Str::before($key, '.');
                $key = str_replace('.', '->', $key);

                if ($allowedSorts->contains($property)) {
                    $key = $this->sanitize($key);
                    $builder->orderBy($key, $descending ? 'desc' : 'asc');
                }
            });
    }

    public function isSort(string $property = null): bool
    {
        if ($property === null) {
            return $this->sorts->isEmpty();
        }

        if ($this->sorts->search($property, true) !== false) {
            return true;
        }

        if ($this->sorts->search('-'.$property, true) !== false) {
            return true;
        }

        return false;
    }

    public function revertSort(string $property): string
    {
        return $this->getSort($property) === 'asc'
            ? '-'.$property
            : $property;
    }

    public function getSort(string $property): string
    {
        return $this->sorts->search($property, true) !== false
            ? 'asc'
            : 'desc';
    }

    /**
     * @return mixed
     */
    public function getFilter(string $property)
    {
        return Arr::get($this->filters, $property);
    }

    private function isDate(Model $model, string $property): bool
    {
        return $model->hasCast($property, ['date', 'datetime', 'immutable_date', 'immutable_datetime'])
            || in_array($property, [$model->getCreatedAtColumn(), $model->getUpdatedAtColumn()], true);
    }
}
