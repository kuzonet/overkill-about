<?php

use Kuzonet\OverkillAbout\Support\AboutConfig;

beforeEach(function () {
    config()->set('overkill-about', [
        'admin' => ['app' => ['name' => 'Kuzo Admin']],
        'app' => ['app' => ['name' => 'Kuzo']],
        'broken' => 'not an array',
    ]);
});

it('returns the entry for a panel', function () {
    expect(AboutConfig::forPanel('admin'))->toBe(['app' => ['name' => 'Kuzo Admin']])
        ->and(AboutConfig::forPanel('app'))->toBe(['app' => ['name' => 'Kuzo']]);
});

it('returns an empty screen for a panel that is not listed', function () {
    expect(AboutConfig::forPanel('other'))->toBe([]);
});

it('returns an empty screen when no key is given', function () {
    expect(AboutConfig::forPanel(null))->toBe([]);
});

it('ignores an entry that is not an array', function () {
    expect(AboutConfig::forPanel('broken'))->toBe([]);
});

it('tolerates a missing config file', function () {
    config()->set('overkill-about', null);

    expect(AboutConfig::forPanel('admin'))->toBe([]);
});
