<?php

use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->signIn();

    config()->set('app.name', 'Kuzo Portal');
});

function mountAbout(): mixed
{
    return Livewire::test(Topbar::class)->mountAction('overkill-about');
}

/**
 * @return list<string>
 */
function aboutHeadings(): array
{
    return array_column(aboutAction()->getAboutSections(), 'title');
}

/**
 * @param  array<string, mixed>  $sections
 */
function aboutSections(array $sections, string $panel = 'admin'): void
{
    config()->set("overkill-about.{$panel}.sections", $sections);
}

it('adds the about item to the user menu', function () {
    Livewire::test(Topbar::class)
        ->assertActionExists('overkill-about')
        ->assertActionVisible('overkill-about')
        ->assertSee('About');
});

it('does not render the item for guests', function () {
    auth()->logout();

    Livewire::test(Topbar::class)->assertDontSee('overkill-about');
});

it('uses the app name in the modal heading', function () {
    mountAbout()->assertMountedActionModalSee('About Kuzo Portal');
});

it('uses a plain heading when there is no app name', function () {
    config()->set('overkill-about', []);
    config()->set('app.name', '');

    mountAbout()->assertMountedActionModalSee('About');
});

it('renders a section for each configured section in order', function () {
    aboutSections([
        'first' => ['title' => 'First Section', 'items' => ['A' => 'one']],
        'second' => ['title' => 'Second Section', 'items' => ['B' => 'two']],
    ]);

    expect(aboutHeadings())->toBe(['First Section', 'Second Section']);

    mountAbout()->assertMountedActionModalSee(['First Section', 'Second Section', 'one', 'two']);
});

it('uses the section title', function () {
    aboutSections(['contact' => ['title' => 'Contact Us', 'items' => ['Email' => 'a@b.c']]]);

    mountAbout()
        ->assertMountedActionModalSee('Contact Us')
        ->assertMountedActionModalSee('a@b.c');
});

it('falls back to the section key in proper case', function () {
    aboutSections([
        'technicals' => ['items' => ['PHP' => '8.4']],
        'support_info' => ['items' => ['Hours' => '9-5']],
        'contact-us' => ['items' => ['Email' => 'a@b.c']],
        'PHP' => ['items' => ['Version' => '8.4']],
    ]);

    expect(aboutHeadings())->toBe(['Technicals', 'Support Info', 'Contact Us', 'PHP']);
});

it('uses the item title and falls back to the item key in proper case', function () {
    aboutSections(['app' => ['items' => [
        'version' => ['title' => 'Application Version', 'value' => '1.2.3'],
        'build_number' => ['value' => '42'],
    ]]]);

    mountAbout()
        ->assertMountedActionModalSee('Application Version')
        ->assertMountedActionModalSee('1.2.3')
        ->assertMountedActionModalSee('Build Number')
        ->assertMountedActionModalSee('42');
});

it('accepts label => value shorthand items', function () {
    aboutSections(['licensing' => ['items' => ['Licensed to' => 'Acme Inc.', 'Seats' => 50, 'Trial' => false]]]);

    mountAbout()
        ->assertMountedActionModalSee('Licensed To')
        ->assertMountedActionModalSee('Acme Inc.')
        ->assertMountedActionModalSee('50')
        ->assertMountedActionModalSee('No');
});

it('applies section and item properties', function () {
    aboutSections(['contact' => [
        'icon' => 'heroicon-o-phone',
        'description' => 'Reach the support team',
        'collapsible' => true,
        'collapsed' => true,
        'items' => [
            'Email' => ['value' => 'a@b.c', 'icon' => 'heroicon-o-envelope', 'url' => 'mailto:a@b.c'],
            'Website' => ['value' => 'example.com', 'url' => 'https://example.com'],
            'Portal' => ['value' => 'portal', 'url' => 'https://portal.example.com', 'new_tab' => false],
        ],
    ]]);

    [$section] = aboutAction()->getAboutSections();
    [$email, $website, $portal] = $section['items'];

    expect($section['icon'])->toBe('heroicon-o-phone')
        ->and($section['description'])->toBe('Reach the support team')
        ->and($section['collapsible'])->toBeTrue()
        ->and($section['collapsed'])->toBeTrue()
        ->and($email['icon'])->toBe('heroicon-o-envelope')
        ->and($email['url'])->toBe('mailto:a@b.c')
        ->and($email['new_tab'])->toBeFalse()
        ->and($website['new_tab'])->toBeTrue()
        ->and($portal['new_tab'])->toBeFalse();

    config()->set('overkill-about.admin.modal.layout', 'component');

    mountAbout()->assertMountedActionModalSee('Reach the support team');
});

it('uses the standard callout layout by default', function () {
    aboutSections(['contact' => ['items' => ['Email' => 'a@b.c']]]);

    expect(aboutAction()->getLayoutView())->toBe('overkill-about::about');

    mountAbout()
        ->assertMountedActionModalSeeHtml('fi-callout')
        ->assertMountedActionModalDontSeeHtml('fi-section');
});

it('uses the component layout when configured', function () {
    config()->set('overkill-about.admin.modal.layout', 'component');
    aboutSections(['contact' => ['items' => ['Email' => 'a@b.c']]]);

    expect(aboutAction()->getLayoutView())->toBe('overkill-about::about-component');

    mountAbout()
        ->assertMountedActionModalSeeHtml('fi-section')
        ->assertMountedActionModalDontSeeHtml('fi-callout')
        ->assertMountedActionModalSee('a@b.c');
});

it('falls back to the standard layout for an unknown layout', function () {
    config()->set('overkill-about.admin.modal.layout', 'fancy');

    expect(aboutAction()->getLayoutView())->toBe('overkill-about::about');
});

it('opens http links in a new tab in the modal', function () {
    aboutSections(['contact' => ['items' => [
        'Website' => ['value' => 'example.com', 'url' => 'https://example.com'],
        'Email' => ['value' => 'support@example.com', 'url' => 'mailto:support@example.com'],
    ]]]);

    mountAbout()
        ->assertMountedActionModalSeeHtml('href="https://example.com" target="_blank" rel="noopener noreferrer"')
        ->assertMountedActionModalDontSeeHtml('href="mailto:support@example.com" target="_blank"');
});

it('links items with a url', function () {
    aboutSections(['contact' => ['items' => [
        'Email' => ['value' => 'support@example.com', 'url' => 'mailto:support@example.com'],
        'Website' => ['value' => 'example.com', 'url' => 'https://example.com'],
        'Docs' => ['value' => 'Docs', 'url' => '/docs'],
    ]]]);

    mountAbout()
        ->assertMountedActionModalSeeHtml('href="mailto:support@example.com"')
        ->assertMountedActionModalSeeHtml('href="https://example.com"')
        ->assertMountedActionModalSeeHtml('href="/docs"');
});

it('does not link unsafe urls but still shows the value', function () {
    aboutSections(['links' => ['items' => [
        'Bad' => ['value' => 'Click me', 'url' => 'javascript:alert(1)'],
    ]]]);

    mountAbout()
        ->assertMountedActionModalSee('Click me')
        ->assertMountedActionModalDontSeeHtml('javascript:alert(1)');
});

it('shows items with a null or blank value as an empty value', function () {
    aboutSections(['app' => ['title' => 'App Details', 'items' => [
        'Version' => null,
        'Build' => ['title' => 'Build Number', 'value' => null, 'url' => 'https://example.com/builds'],
        'Notes' => ['value' => '  '],
        'Owner' => ['title' => 'Owner'],
    ]]]);

    $items = aboutAction()->getAboutSections()[0]['items'];

    expect(array_column($items, 'label'))->toBe(['Version', 'Build Number', 'Notes', 'Owner'])
        ->and(array_column($items, 'value'))->toBe([null, null, null, null])
        ->and($items[1]['url'])->toBeNull();

    mountAbout()
        ->assertMountedActionModalSee(['App Details', 'Version', 'Build Number', 'Notes', 'Owner'])
        ->assertMountedActionModalDontSeeHtml('https://example.com/builds');
});

it('shows a section whose items all have null values', function () {
    aboutSections(['pending' => ['title' => 'Pending Section', 'items' => ['Nothing' => null]]]);

    expect(aboutHeadings())->toBe(['Pending Section']);
});

it('omits sections without items', function () {
    aboutSections([
        'kept' => ['title' => 'Kept Section', 'items' => ['Shown' => 'yes']],
        'empty' => ['title' => 'Empty Section', 'items' => []],
        'no_items' => ['title' => 'No Items Section'],
        'broken' => 'not an array',
    ]);

    expect(aboutHeadings())->toBe(['Kept Section']);
});

it('ignores items that are not scalar or arrays of values', function () {
    aboutSections(['odd' => ['title' => 'Odd', 'items' => [
        'Nested' => ['value' => ['not', 'scalar']],
        'Ok' => 'fine',
    ]]]);

    mountAbout()
        ->assertMountedActionModalSee('fine')
        ->assertMountedActionModalDontSee('Nested');
});

it('escapes html in config values', function () {
    aboutSections(['danger' => ['items' => ['Note' => '<script>alert(1)</script>']]]);

    mountAbout()
        ->assertMountedActionModalDontSeeHtml('<script>alert(1)</script>')
        ->assertMountedActionModalSeeHtml('&lt;script&gt;');
});

it('renders with no config at all', function () {
    config()->set('overkill-about', []);

    mountAbout()->assertMountedActionModalSee('About Kuzo Portal');
});

it('hides a section that is disabled', function () {
    aboutSections([
        'shown' => ['title' => 'Shown', 'enabled' => true, 'items' => ['A' => 'one']],
        'hidden' => ['title' => 'Hidden', 'enabled' => false, 'items' => ['B' => 'two']],
    ]);

    expect(aboutHeadings())->toBe(['Shown']);

    mountAbout()->assertMountedActionModalDontSee('two');
});

it('shows a section when its gate ability allows', function () {
    Gate::define('viewTechnicalDetails', fn () => true);
    aboutSections(['technicals' => ['enabled' => 'viewTechnicalDetails', 'items' => ['PHP' => '8.4']]]);

    expect(aboutHeadings())->toBe(['Technicals']);
});

it('hides a section when its gate ability denies', function () {
    Gate::define('viewTechnicalDetails', fn () => false);
    aboutSections(['technicals' => ['enabled' => 'viewTechnicalDetails', 'items' => ['PHP' => '8.4']]]);

    expect(aboutHeadings())->toBe([]);
});

it('hides a section whose gate ability is not defined', function () {
    aboutSections(['technicals' => ['enabled' => 'undefinedAbility', 'items' => ['PHP' => '8.4']]]);

    expect(aboutHeadings())->toBe([]);
});

it('requires every ability in a list', function () {
    Gate::define('first', fn () => true);
    Gate::define('second', fn () => false);
    aboutSections([
        'both' => ['enabled' => ['first', 'second'], 'items' => ['A' => 'one']],
        'one' => ['enabled' => ['first'], 'items' => ['B' => 'two']],
    ]);

    expect(aboutHeadings())->toBe(['One']);
});

it('passes the signed in user to the gate', function () {
    Gate::define('isTestAdmin', fn ($user) => $user->email === 'admin@test.com');
    aboutSections(['admin_only' => ['enabled' => 'isTestAdmin', 'items' => ['A' => 'one']]]);

    expect(aboutHeadings())->toBe(['Admin Only']);
});

it('treats an invalid enabled value as disabled', function (mixed $flag) {
    aboutSections(['odd' => ['enabled' => $flag, 'items' => ['A' => 'one']]]);

    expect(aboutHeadings())->toBe([]);
})->with([
    'empty string' => [''],
    'empty list' => [[]],
    'list with a non-string' => [['first', 5]],
    'number' => [1],
]);

it('hides a disabled item but keeps the rest of the section', function () {
    Gate::define('viewServer', fn () => false);
    aboutSections(['technicals' => ['items' => [
        'PHP' => ['value' => '8.4'],
        'Server' => ['value' => 'web-01', 'enabled' => 'viewServer'],
        'Region' => ['value' => 'ca-central-1', 'enabled' => false],
    ]]]);

    mountAbout()
        ->assertMountedActionModalSee('8.4')
        ->assertMountedActionModalDontSee('web-01')
        ->assertMountedActionModalDontSee('ca-central-1');
});

it('omits a section whose items are all disabled', function () {
    aboutSections(['technicals' => ['items' => ['PHP' => ['value' => '8.4', 'enabled' => false]]]]);

    expect(aboutHeadings())->toBe([]);
});

it('hides the menu item when the panel gate ability denies', function () {
    Gate::define('viewAbout', fn () => false);
    config()->set('overkill-about.admin.enabled', 'viewAbout');

    expect(aboutAction()->isVisible())->toBeFalse();
});

it('hides the item when disabled for a panel', function () {
    config()->set('overkill-about.admin.enabled', false);

    expect(aboutAction('admin')->isVisible())->toBeFalse()
        ->and(aboutAction('app')->isVisible())->toBeTrue();
});

it('shows a different screen per panel', function () {
    aboutSections(['support' => ['items' => ['Knowledge Base' => 'kb.example.com']]], 'app');

    mountAbout()->assertMountedActionModalDontSee('Knowledge Base');

    Filament::setCurrentPanel('app');

    mountAbout()->assertMountedActionModalSee('Knowledge Base');
});

it('uses the panel config for the menu item', function () {
    config()->set('overkill-about.admin.menu', ['label' => 'Help & Support', 'icon' => 'heroicon-o-lifebuoy', 'sort' => 5]);

    $action = aboutAction();

    expect($action->getLabel())->toBe('Help & Support')
        ->and($action->getIcon())->toBe('heroicon-o-lifebuoy')
        ->and($action->getSort())->toBe(5);
});

it('uses the panel config for the modal', function () {
    config()->set('overkill-about.admin.modal', [
        'heading' => 'Custom heading',
        'description' => 'Custom description',
        'width' => '2xl',
        'slide_over' => true,
    ]);

    $action = aboutAction();

    expect($action->getModalHeading())->toBe('Custom heading')
        ->and($action->getModalDescription())->toBe('Custom description')
        ->and($action->getModalWidth())->toBe('2xl')
        ->and($action->isModalSlideOver())->toBeTrue();

    mountAbout()->assertMountedActionModalSee('Custom heading');
});
