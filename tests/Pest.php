<?php

use Filament\Facades\Filament;
use Kuzonet\OverkillAbout\Actions\AboutAction;
use Kuzonet\OverkillAbout\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * The about action registered on a panel, including when it is hidden.
 */
function aboutAction(string $panel = 'admin', string $name = 'overkill-about'): AboutAction
{
    return collect(Filament::getPanel($panel)->getUserMenuItemGroups())->collapse()[$name];
}
