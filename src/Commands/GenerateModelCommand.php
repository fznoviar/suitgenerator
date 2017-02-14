<?php

namespace Fznoviar\SuitGenerator\Commands;

use DB;
use Illuminate\Console\Command;
use Schema;

class GenerateModelCommand extends BaseCommand
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
                'fillable' => $this->getFullSchema($table),
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
        $filename = $this->getFilename($table);
        $fullPath = "app/Model/$filename.php";
        $this->info("Generating file: $filename.php");
        return $fullPath;
    }

    protected function getStub()
    {
        $this->info('loading model stub');

        return __DIR__ . '/../stubs/model.stub';
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
        return str_replace('{{class}}', $this->getFilename($tableName), $stub);
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
