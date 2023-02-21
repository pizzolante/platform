<?php

declare(strict_types=1);

namespace Orchid\Tests\App\Screens;

use Orchid\Screen\Action;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Tests\App\Layouts\DependentSumListener;

class DependentListenerScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(): array
    {
        return [
            'first' => 100,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Test Dependent';
    }

    /**
     * Button commands.
     *
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [];
    }

    /**
     * @return int[]
     */
    public function asyncSum(int $first = null, int $second = null): array
    {
        return [
            'first'  => $first,
            'second' => $second,
            'sum'    => $first + $second,
        ];
    }

    /**
     * Views.
     *
     * @throws \Throwable
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): array
    {
        return [
            Layout::rows([
                Input::make('first')
                    ->title('First argument')
                    ->type('number'),

                Input::make('second')
                    ->title('Second argument')
                    ->type('number'),
            ]),

            DependentSumListener::class,
        ];
    }
}
