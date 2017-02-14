<?php

namespace Fznoviar\SuitGenerator;

use Illuminate\Support\ServiceProvider;

class SuitGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }
    public function register()
    {
        $this->registerModelGenerator();
    }
    private function registerModelGenerator()
    {
        $this->app->singleton('command.fznoviar.suitmodel', function ($app) {
            return $app['Fznoviar\SuitGenerator\Commands\GenerateModelCommand'];
        });
        $this->commands('command.fznoviar.suitmodel');
    }
}
