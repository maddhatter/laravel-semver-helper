<?php namespace MaddHatter\SemverHelper;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use MaddHatter\SemverHelper\Console\ChangesListCommand;
use MaddHatter\SemverHelper\Console\ChangesMakeCommand;
use MaddHatter\SemverHelper\Console\TagVersionCommand;

class ServiceProvider extends BaseServiceProvider
{

    public function register()
    {
        $this->publishes([
            __DIR__ . '/../config/semver-helper.php' => config_path('semver-helper.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config/semver-helper.php', 'semver-helper');

        $this->commands([
            ChangesListCommand::class,
            ChangesMakeCommand::class,
            TagVersionCommand::class,
        ]);
    }

}