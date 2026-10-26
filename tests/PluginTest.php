<?php

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Kuzonet\OverkillAbout\Actions\AboutAction;
use Kuzonet\OverkillAbout\OverkillAboutPlugin;
use Livewire\Livewire;

beforeEach(function () {
    $this->signIn();
});

it('has the overkill-about id', function () {
    expect(OverkillAboutPlugin::make()->getId())->toBe('overkill-about');
});

it('derives its id and config key from the key passed to make', function () {
    $plugin = OverkillAboutPlugin::make('support');

    expect($plugin->getId())->toBe('overkill-about-support')
        ->and($plugin->getConfigKey('admin'))->toBe('support');
});

it('can add several menu items to one panel', function () {
    $panel = Filament::getPanel('admin')->plugin(OverkillAboutPlugin::make('support'));

    expect(array_keys($panel->getUserMenuItems()))->toContain('overkill-about', 'overkill-about-support')
        ->and($panel->getPlugin('overkill-about'))->not->toBe($panel->getPlugin('overkill-about-support'));
});

it('shows each menu item its own config entry', function () {
    config()->set('overkill-about.admin.sections', ['about' => ['items' => ['Version' => 'v1.2.3']]]);
    config()->set('overkill-about.support.sections', ['help' => ['items' => ['Phone' => '555-0100']]]);
    Filament::getPanel('admin')->plugin(OverkillAboutPlugin::make('support'));

    Livewire::test(Topbar::class)
        ->mountAction('overkill-about')
        ->assertMountedActionModalSee('v1.2.3')
        ->assertMountedActionModalDontSee('555-0100');

    Livewire::test(Topbar::class)
        ->mountAction('overkill-about-support')
        ->assertMountedActionModalSee('555-0100')
        ->assertMountedActionModalDontSee('v1.2.3');
});

it('applies menu settings and enabled per menu item', function () {
    config()->set('overkill-about.support', ['enabled' => false, 'menu' => ['label' => 'Support']]);
    Filament::getPanel('admin')->plugin(OverkillAboutPlugin::make('support'));

    expect(aboutAction('admin', 'overkill-about-support')->getLabel())->toBe('Support')
        ->and(aboutAction('admin', 'overkill-about-support')->isVisible())->toBeFalse()
        ->and(aboutAction()->isVisible())->toBeTrue();
});

it('can resolve a keyed instance for the current panel', function () {
    Filament::getPanel('admin')->plugin($plugin = OverkillAboutPlugin::make('support'));

    expect(OverkillAboutPlugin::get('support'))->toBe($plugin);
});

it('is registered on each panel', function () {
    expect(Filament::getPanel('admin')->getPlugin('overkill-about'))->toBeInstanceOf(OverkillAboutPlugin::class)
        ->and(Filament::getPanel('app')->getPlugin('overkill-about'))->toBeInstanceOf(OverkillAboutPlugin::class);
});

it('can be resolved for the current panel', function () {
    expect(OverkillAboutPlugin::get())->toBe(Filament::getPanel('admin')->getPlugin('overkill-about'));
});

it('adds its action to the user menu of each panel', function () {
    expect(aboutAction('admin'))->toBeInstanceOf(AboutAction::class)
        ->and(aboutAction('app'))->toBeInstanceOf(AboutAction::class);
});

it('keeps user menu items the host registered', function () {
    $panel = Filament::getPanel('admin');
    $panel->userMenuItems([
        'profile' => Action::make('profile')->url('/profile'),
    ]);

    expect(array_keys($panel->getUserMenuItems()))->toContain('overkill-about', 'profile');
});

it('reads its own panel config by default', function () {
    expect(Filament::getPanel('app')->getPlugin('overkill-about')->getConfigKey())->toBe('app');
});

it('can read another panel config with configKey', function () {
    config()->set('overkill-about.support.app.name', 'Support Portal');

    $plugin = Filament::getPanel('admin')->getPlugin('overkill-about')->configKey('support');

    expect($plugin->getScreen()['app']['name'])->toBe('Support Portal');
});

it('lets fluent overrides beat config', function () {
    config()->set('overkill-about.admin.menu', ['label' => 'From config', 'icon' => 'heroicon-o-star', 'sort' => 1]);
    config()->set('overkill-about.admin.modal.width', 'sm');

    Filament::getPanel('admin')->getPlugin('overkill-about')
        ->label(fn () => 'From plugin')
        ->icon('heroicon-o-lifebuoy')
        ->sort(9)
        ->modalWidth('3xl');

    $action = aboutAction();

    expect($action->getLabel())->toBe('From plugin')
        ->and($action->getIcon())->toBe('heroicon-o-lifebuoy')
        ->and($action->getSort())->toBe(9)
        ->and($action->getModalWidth())->toBe('3xl');
});

it('can be hidden with visible()', function () {
    Filament::getPanel('admin')->getPlugin('overkill-about')->visible(fn () => false);

    expect(aboutAction()->isVisible())->toBeFalse();
});

it('can be hidden with authorize()', function () {
    Filament::getPanel('admin')->getPlugin('overkill-about')->authorize(false);

    expect(aboutAction()->isVisible())->toBeFalse();
});

it('fails clearly when the action has no plugin', function () {
    AboutAction::make()->getPlugin();
})->throws(LogicException::class);
