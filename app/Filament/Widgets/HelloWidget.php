<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class HelloWidget extends Widget
{
    protected static string $view = 'filament.widgets.hello';

    protected int|string|array $columnSpan = 'full';
}

