<?php

namespace Fznoviar\SuitGenerator\Commands;

use Artisan;
use DB;
use Illuminate\Console\Command;
use Schema;

class GenerateModuleCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suit:module 
                            {--table= : a single table or a list of tables separated by a comma (,)}
                            {--all : run for all tables}
                            {--connection= : database connection to use, default use from .env connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate module for the given tables based on their columns';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting Controller Generate Command');

        $this->getOptions();

        if (!$this->isExecutable()) {
            return;
        }

        $this->info('Generate Model');
        Artisan::call('suit:model', $this->getParams());
        $this->info('Generate Controller');
        Artisan::call('suit:controller', $this->getParams());
        $this->info('Generate View');
        Artisan::call('suit:view', $this->getParams());

        $this->info('Complete Controller Generate Command');
    }

    protected function getParams()
    {
        $params = [];
        if ($this->options['all']) {
            $params['--all'] = '--all';
        }
        if ($this->options['table']) {
            $params['--table'] = $this->options['table'];
        }
        if ($this->options['connection']) {
            $params['--connection'] = '--connection';
        }
        return $params;
    }
}
