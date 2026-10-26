<?php

namespace Kuzonet\OverkillAbout\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Kuzonet\OverkillAbout\OverkillAboutPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->plugin(OverkillAboutPlugin::make());
    }
}
