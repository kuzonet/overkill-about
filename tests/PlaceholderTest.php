<?php

use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Kuzonet\OverkillAbout\Tests\Fixtures\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = $this->signIn();
});

/**
 * @param  array<string, mixed>  $items
 * @return array{label: string, value: ?string, icon: ?string, url: ?string, new_tab: bool}
 */
function placeholderEntry(array $items): array
{
    config()->set('overkill-about.admin.sections', ['me' => ['items' => $items]]);

    return aboutAction()->getAboutSections()[0]['items'][0];
}

it('fills in user attributes in item values', function () {
    expect(placeholderEntry(['User' => '{user.name}'])['value'])->toBe('Test Admin')
        ->and(placeholderEntry(['Email' => '{ user.email }'])['value'])->toBe('admin@test.com');
});

it('fills in placeholders inside other text', function () {
    expect(placeholderEntry(['Signed In' => 'Hello {user.name} ({user.email})'])['value'])
        ->toBe('Hello Test Admin (admin@test.com)');
});

it('fills in placeholders in item titles and urls', function () {
    $entry = placeholderEntry(['Email' => [
        'title' => '{user.name} email',
        'value' => '{user.email}',
        'url' => 'mailto:{user.email}',
    ]]);

    expect($entry['label'])->toBe('Test Admin email')
        ->and($entry['url'])->toBe('mailto:admin@test.com');
});

it('fills in placeholders in section titles and descriptions', function () {
    config()->set('overkill-about.admin.sections', ['me' => [
        'title' => 'Account for {user.name}',
        'description' => 'Signed in as {user.email}',
        'items' => ['A' => 'one'],
    ]]);

    [$section] = aboutAction()->getAboutSections();

    expect($section['title'])->toBe('Account for Test Admin')
        ->and($section['description'])->toBe('Signed in as admin@test.com');
});

it('fills in placeholders in the modal heading, description and menu label', function () {
    config()->set('overkill-about.admin.menu.label', 'Hi {user.name}');
    config()->set('overkill-about.admin.modal', [
        'heading' => 'About for {user.name}',
        'description' => 'Signed in as {user.email}',
    ]);

    $action = aboutAction();

    expect($action->getLabel())->toBe('Hi Test Admin')
        ->and($action->getModalHeading())->toBe('About for Test Admin')
        ->and($action->getModalDescription())->toBe('Signed in as admin@test.com');
});

it('renders the filled in value in the modal', function () {
    config()->set('overkill-about.admin.sections', ['me' => ['items' => ['User' => '{user.name}']]]);

    Livewire::test(Topbar::class)
        ->mountAction('overkill-about')
        ->assertMountedActionModalSee('Test Admin')
        ->assertMountedActionModalDontSee('{user.name}');
});

it('never shows hidden attributes', function () {
    expect(placeholderEntry(['Password' => '{user.password}'])['value'])->toBeNull();

    $this->user->setHidden(['email']);

    expect(placeholderEntry(['Email' => '{user.email}'])['value'])->toBeNull();
});

it('shows an empty value for an unknown attribute', function () {
    $entry = placeholderEntry(['Team' => '{user.team.name}']);

    expect($entry['label'])->toBe('Team')
        ->and($entry['value'])->toBeNull();
});

it('shows an empty value for guests', function () {
    config()->set('overkill-about.admin.sections', ['me' => ['items' => ['User' => '{user.name}']]]);
    $action = aboutAction();

    Filament::auth()->logout();

    expect($action->getAboutSections()[0]['items'][0]['value'])->toBeNull();
});

it('leaves unknown placeholders untouched', function () {
    expect(placeholderEntry(['Raw' => 'Use {app.name} or {something}'])['value'])
        ->toBe('Use {app.name} or {something}');
});

it('does not link a url made unsafe by user data', function () {
    $this->user->forceFill(['name' => 'javascript:alert(1)'])->save();

    $entry = placeholderEntry(['Site' => ['value' => 'My site', 'url' => '{user.name}']]);

    expect($entry['url'])->toBeNull();
});

it('escapes user data in the modal', function () {
    $this->user->forceFill(['name' => '<b>Bold</b>'])->save();
    config()->set('overkill-about.admin.sections', ['me' => ['items' => ['User' => '{user.name}']]]);

    Livewire::test(Topbar::class)
        ->mountAction('overkill-about')
        ->assertMountedActionModalDontSeeHtml('<b>Bold</b>')
        ->assertMountedActionModalSeeHtml('&lt;b&gt;Bold&lt;/b&gt;');
});

it('fills in attributes of the current tenant', function () {
    // Any model can be a Filament tenant; the user fixture keeps the test self-contained.
    Filament::setTenant(User::create(['name' => 'Acme Inc.', 'email' => 'acme@test.com', 'password' => 'secret']), isQuiet: true);

    $entry = placeholderEntry(['Tenant' => [
        'title' => 'Workspace',
        'value' => '{tenant.name} ({user.name})',
        'url' => 'mailto:{tenant.email}',
    ]]);

    expect($entry['value'])->toBe('Acme Inc. (Test Admin)')
        ->and($entry['url'])->toBe('mailto:acme@test.com');
});

it('never shows hidden tenant attributes', function () {
    Filament::setTenant(User::create(['name' => 'Acme Inc.', 'email' => 'acme@test.com', 'password' => 'secret']), isQuiet: true);

    expect(placeholderEntry(['Secret' => '{tenant.password}'])['value'])->toBeNull();
});

it('shows an empty value when there is no tenant', function () {
    Filament::setTenant(null);

    expect(placeholderEntry(['Tenant' => '{tenant.name}'])['value'])->toBeNull();
});
