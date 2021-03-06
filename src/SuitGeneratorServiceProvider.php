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
        $this->registerControllerGenerator();
        $this->registerViewGenerator();
        $this->registerModuleGenerator();
    }
    
    private function registerModelGenerator()
    {
        $this->app->singleton('command.fznoviar.suitmodel', function ($app) {
            return $app['Fznoviar\SuitGenerator\Commands\GenerateModelCommand'];
        });
        $this->commands('command.fznoviar.suitmodel');
    }

    private function registerControllerGenerator()
    {
        $this->app->singleton('command.fznoviar.suitcontroller', function ($app) {
            return $app['Fznoviar\SuitGenerator\Commands\GenerateControllerCommand'];
        });
        $this->commands('command.fznoviar.suitcontroller');
    }

    private function registerViewGenerator()
    {
        $this->app->singleton('command.fznoviar.suitview', function ($app) {
            return $app['Fznoviar\SuitGenerator\Commands\GenerateViewCommand'];
        });
        $this->commands('command.fznoviar.suitview');
    }

    private function registerModuleGenerator()
    {
        $this->app->singleton('command.fznoviar.suitmodule', function ($app) {
            return $app['Fznoviar\SuitGenerator\Commands\GenerateModuleCommand'];
        });
        $this->commands('command.fznoviar.suitmodule');
    }
}
