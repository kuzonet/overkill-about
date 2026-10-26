<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

it('merges the package config under overkill-about', function () {
    expect(config('overkill-about.admin'))->toBeArray()
        ->and(config('overkill-about.app'))->toBeArray();
});

it('loads translations', function () {
    expect(__('overkill-about::overkill-about.menu.label'))->toBe('About');
});

it('registers the about views under the overkill-about namespace', function () {
    expect(view()->exists('overkill-about::about'))->toBeTrue()
        ->and(view()->exists('overkill-about::about-component'))->toBeTrue();
});

it('publishes the config under the overkill-about-config tag', function () {
    $paths = ServiceProvider::pathsToPublish(null, 'overkill-about-config');

    expect(array_values($paths))->toContain(config_path('overkill-about.php'));
});

it('loads the real shipped config without throwing, even though it is not used during tests', function () {
    // The suite boots against workbench/config/overkill-about.php (see testbench.yaml), so this is
    // the one place the actual published demo in config/overkill-about.php gets exercised: it must
    // stay boot-safe (no DB/version lookup may throw) in every environment, not just this one.
    $config = require __DIR__ . '/../config/overkill-about.php';

    expect($config['admin'])->toBeArray()
        ->and($config['app'])->toBeArray();
});

arch('src has no eloquent models')
    ->expect('Kuzonet\OverkillAbout')
    ->not->toExtend(Model::class);
