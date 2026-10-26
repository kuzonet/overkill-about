<?php

use Filament\Livewire\Topbar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Kuzonet\OverkillAbout\OverkillAboutPlugin;
use Kuzonet\OverkillAbout\Tests\Fixtures\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = $this->signIn();
    User::$calls = 0;
});

/**
 * @param  array<string, mixed>  $items
 * @return array{label: string, value: ?string, icon: ?string, url: ?string, new_tab: bool}
 */
function hardeningEntry(array $items): array
{
    config()->set('overkill-about.admin.sections', ['x' => ['items' => $items]]);

    return aboutAction()->getAboutSections()[0]['items'][0];
}

it('never calls model methods through a placeholder', function () {
    expect(hardeningEntry(['Call' => '{user.dangerous}'])['value'])->toBeNull()
        ->and(User::$calls)->toBe(0);
});

it('does not read methods that belong to eloquent itself', function () {
    expect(hardeningEntry(['Call' => '{user.toJson}'])['value'])->toBeNull()
        ->and(hardeningEntry(['Call' => '{user.delete}'])['value'])->toBeNull();
});

it('reads attributes of a relation', function () {
    $buddy = User::create(['name' => 'Pal', 'email' => 'pal@test.com', 'password' => 'secret']);
    $this->user->forceFill(['buddy_id' => $buddy->id])->save();
    $this->actingAs(User::find($this->user->id));

    expect(hardeningEntry(['Buddy' => '{user.buddy.name}'])['value'])->toBe('Pal')
        ->and(hardeningEntry(['Buddy' => '{user.buddy.password}'])['value'])->toBeNull();
});

it('shows an empty value instead of throwing when lazy loading is prevented', function () {
    $buddy = User::create(['name' => 'Pal', 'email' => 'pal@test.com', 'password' => 'secret']);
    $this->user->forceFill(['buddy_id' => $buddy->id])->save();

    // Eloquent only flags models hydrated as part of a collection of two or more, and does it
    // at load time, so switch the prevention on before loading both users.
    Model::preventLazyLoading();

    try {
        $this->actingAs(User::all()->firstWhere('id', $this->user->id));

        expect(hardeningEntry(['Buddy' => '{user.buddy.name}'])['value'])->toBeNull();
    } finally {
        Model::preventLazyLoading(false);
    }
});

it('only reads attributes a model lists as visible', function () {
    $this->user->setVisible(['name']);

    expect(hardeningEntry(['Name' => '{user.name}'])['value'])->toBe('Test Admin')
        ->and(hardeningEntry(['Email' => '{user.email}'])['value'])->toBeNull();
});

it('rejects urls that browsers read as another host', function (string $url) {
    expect(hardeningEntry(['Link' => ['value' => 'x', 'url' => $url]])['url'])->toBeNull();
})->with([
    'backslash after slash' => '/\evil.example',
    'protocol relative' => '//evil.example',
    'backslashes' => '\\\\evil.example',
    'tab inside scheme' => "java\tscript:alert(1)",
    'newline in path' => "/ok\nevil",
]);

it('links app relative paths', function () {
    expect(hardeningEntry(['Link' => ['value' => 'x', 'url' => '/admin/profile']])['url'])->toBe('/admin/profile');
});

it('does not link a user value that starts a path with a backslash', function () {
    $this->user->forceFill(['name' => '\evil.example'])->save();

    expect(hardeningEntry(['Link' => ['value' => 'x', 'url' => '/{user.name}']])['url'])->toBeNull();
});

it('reads the right panel when one plugin instance is shared', function () {
    config()->set('overkill-about.one.sections', ['a' => ['title' => 'From One', 'items' => ['K' => 'v']]]);
    config()->set('overkill-about.two.sections', ['a' => ['title' => 'From Two', 'items' => ['K' => 'v']]]);

    $plugin = OverkillAboutPlugin::make();
    $one = Panel::make()->id('one')->plugin($plugin);
    $two = Panel::make()->id('two')->plugin($plugin);

    $heading = fn (Panel $panel): string => collect($panel->getUserMenuItemGroups())->collapse()['overkill-about']
        ->getAboutSections()[0]['title'];

    expect($heading($one))->toBe('From One')
        ->and($heading($two))->toBe('From Two');
});

it('cannot mount the action through livewire when it is hidden', function () {
    config()->set('overkill-about.admin.enabled', false);

    Livewire::test(Topbar::class)
        ->mountAction('overkill-about')
        ->assertActionNotMounted();
});

it('lets a Gate::before callback allow an undefined ability', function () {
    Gate::before(fn () => true);
    config()->set('overkill-about.admin.sections', ['x' => ['enabled' => 'notDefined', 'items' => ['K' => 'v']]]);

    expect(aboutAction()->getAboutSections())->toHaveCount(1);
});
