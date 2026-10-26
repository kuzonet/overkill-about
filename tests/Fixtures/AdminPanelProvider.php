<?php

namespace Kuzonet\OverkillAbout\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Kuzonet\OverkillAbout\OverkillAboutPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(OverkillAboutPlugin::make());
    }
}
