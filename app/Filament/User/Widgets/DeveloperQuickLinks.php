<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;

class DeveloperQuickLinks extends Widget
{
    protected string $view = 'filament.user.widgets.developer-quick-links';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;
}
