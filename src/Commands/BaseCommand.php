<?php

namespace Fznoviar\SuitGenerator\Commands;

use DB;
use Illuminate\Console\Command;
use Schema;

class BaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

    protected $columns;
    protected $options;
    protected $excludes;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->options = [
            'connection' => '',
            'table' => '',
            'all' => true
        ];

        $this->excludes = [
            'html_templates',
            'menus',
            'menus_translate',
            'migrations',
            'pages',
            'pages_translate',
            'page_attachments',
            'password_reminders',
            'sessions',
            'settings',
            'taggables',
            'tags',
            'users',
        ];
    }

    protected function getFilename($table)
    {
        $name = explode(' ', ucwords(str_replace('_', ' ', str_singular($table))));
        return implode('', $name);
    }

    protected function getTables()
    {
        if ($this->options['all']) {
            return $this->getAllTables();
        }
        return explode(',', $this->options['table']);
    }

    protected function getAllTables()
    {
        $tables = [];
        if (strlen($this->options['connection']) <= 0) {
            $tables = collect(DB::select(DB::raw('show tables')))->flatten();
        } else {
            $tables = collect(DB::connection($this->options['connection'])->select(DB::raw('show tables')))->flatten();
        }

        $tables = $tables->map(function ($value, $key) {
            return collect($value)->flatten()[0];
        })->reject(function ($value, $key) {
            return $value == 'migrations';
        });
        return $tables;
    }

    protected function isExecutable()
    {
        if (strlen($this->options['table']) <= 0 && $this->options['all'] == false) {
            $this->error('No --table specified or --all');

            return false;
        }
        $tables = explode(',', $this->options['table']);
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->error($table . ' table not exists');
                return false;
            }
        }
        return true;
    }

    protected function getOptions()
    {
        $this->options['table'] = $this->option('table') ? $this->option('table') : '';

        $this->options['all'] = $this->option('all') ? true : false;

        $this->options['connection'] = ($this->option('connection')) ? $this->option('connection') : '';
    }

    protected function getSchema($tableName)
    {
        $this->info('Retrieving table definition for: ' . $tableName);
        
        if (strlen($this->options['connection']) <= 0) {
            return Schema::getColumnListing($tableName);
        } else {
            return Schema::connection($this->options['connection'])->getColumnListing($tableName);
        }
    }

    protected function describeTable($tableName)
    {
        $this->info('Retrieving column information for : ' . $tableName);
        if (strlen($this->options['connection']) <= 0) {
            return DB::select(DB::raw('describe ' . $tableName));
        } else {
            return DB::connection($this->options['connection'])->select(DB::raw('describe ' . $tableName));
        }
    }

    protected function setColumns($table)
    {
        $columns = $this->describeTable($table);

        $this->columns = collect();
        foreach ($columns as $col) {
            $this->columns->push([
                'field' => $col->Field,
                'type' => $col->Type,
            ]);
        }
    }

    /**
     * reset all variables to be filled again when using multiple
     */
    protected function resetFields()
    {
        $this->fieldsFillable = '';
        $this->fieldsHidden = '';
        $this->fieldsCast = '';
        $this->fieldsDate = '';
    }
}
