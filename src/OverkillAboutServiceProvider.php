<?php

namespace Kuzonet\OverkillAbout;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OverkillAboutServiceProvider extends PackageServiceProvider
{
    public static string $name = 'overkill-about';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile();
                // ->askToStarRepoOnGitHub('kuzonet/overkill-about');
            });
    }
}
