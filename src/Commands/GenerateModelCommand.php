<?php

namespace Fznoviar\SuitGenerator\Commands;

use DB;
use Illuminate\Console\Command;
use Schema;

class GenerateModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suit:model 
                            {--table= : a single table or a list of tables separated by a comma (,)}
                            {--all : run for all tables}
                            {--connection= : database connection to use, default use from .env connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate models for the given tables based on their columns';

    protected $fieldsFillable;
    protected $fieldsHidden;
    protected $fieldsCast;
    protected $fieldsDate;
    protected $databaseConnection;

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

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting Model Generate Command');

        $this->getOptions();

        if (!$this->isExecutable()) {
            return;
        }

        $tables = $this->getTables();
        $modelStub = file_get_contents($this->getStub());

        foreach ($tables as $table) {
            $stub = $modelStub;
            $fullPath = $this->generateModelFullPath($table);

            $model = [
                'table' => $table,
                'fillable' => $this->getSchema($table),
                'guardable' => [],
                'hidden' => [],
                'casts' => [],
            ];

            $this->setColumns($table);

            $this->resetFields();

            $stub = $this->replaceClassName($stub, $table);

            $stub = $this->replaceModuleInformation($stub, $model);

            $this->info('Writing model: ' . $fullPath, true);
            file_put_contents($fullPath, $stub);
        }

        $this->info('Complete Model Generate Command');
    }

    protected function generateModelFullPath($table)
    {
        $filename = str_singular(ucfirst($table));
        $fullPath = "app/Model/$filename.php";
        $this->info("Generating file: $filename.php");
        return $fullPath;
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
        return true;
    }

    protected function getStub()
    {
        $this->info('loading model stub');

        return __DIR__ . '/../stubs/model.stub';
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
     * replaces the class name in the stub.
     *
     * @param string $stub      stub content
     * @param string $tableName the name of the table to make as the class
     *
     * @return string stub content
     */
    public function replaceClassName($stub, $tableName)
    {
        return str_replace('{{class}}', str_singular(ucfirst($tableName)), $stub);
    }

    /**
     * replaces the module information.
     *
     * @param string $stub             stub content
     * @param array  $modelInformation array (key => value)
     *
     * @return string stub content
     */
    public function replaceModuleInformation($stub, $modelInformation)
    {
        $separator = ",\n        ";
        // replace table
        $stub = str_replace('{{table}}', $modelInformation['table'], $stub);
        // replace fillable
        $this->fieldsHidden = '';
        $this->fieldsFillable = '';
        $this->fieldsCast = '';
        foreach ($modelInformation['fillable'] as $field) {
            // fillable and hidden
            if ($field != 'id') {
                $this->fieldsFillable .= (strlen($this->fieldsFillable) > 0 ? $separator : '') . "'$field'";
                $fieldsFiltered = $this->columns->where('field', $field);
                if ($fieldsFiltered) {
                    // check type
                    switch (strtolower($fieldsFiltered->first()['type'])) {
                        case 'timestamp':
                            $this->fieldsDate .= (strlen($this->fieldsDate) > 0 ? $separator : '') . "'$field'";
                            break;
                        case 'datetime':
                            $this->fieldsDate .= (strlen($this->fieldsDate) > 0 ? $separator : '') . "'$field'";
                            break;
                        case 'date':
                            $this->fieldsDate .= (strlen($this->fieldsDate) > 0 ? $separator : '') . "'$field'";
                            break;
                        case 'tinyint(1)':
                            $this->fieldsCast .= (strlen($this->fieldsCast) > 0 ? $separator : '') . "'$field' => 'boolean'";
                            break;
                    }
                }
            } else {
                if ($field != 'id' && $field != 'created_at' && $field != 'updated_at') {
                    $this->fieldsHidden .= (strlen($this->fieldsHidden) > 0 ? $separator : '') . "'$field'";
                }
            }
        }
        // replace in stub
        $stub = str_replace('{{fillable}}', $this->fieldsFillable, $stub);
        $stub = str_replace('{{hidden}}', $this->fieldsHidden, $stub);
        $stub = str_replace('{{casts}}', $this->fieldsCast, $stub);
        $stub = str_replace('{{dates}}', $this->fieldsDate, $stub);
        return $stub;
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
