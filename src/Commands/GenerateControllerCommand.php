<?php

namespace Fznoviar\SuitGenerator\Commands;

use DB;
use Illuminate\Console\Command;
use Schema;

class GenerateControllerCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suit:controller 
                            {--table= : a single table or a list of tables separated by a comma (,)}
                            {--all : run for all tables}
                            {--connection= : database connection to use, default use from .env connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate models for the given tables based on their columns';

    protected $fieldsRules;

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

        $tables = $this->getTables();
        $controllerStub = file_get_contents($this->getStub());

        foreach ($tables as $table) {
            $stub = $controllerStub;
            $fullPath = $this->generateControllerFullPath($table);

            $model = [
                'table' => $table,
                'rules' => $this->getSchema($table),
            ];

            $this->setColumns($table);

            $this->resetFields();

            $stub = $this->replaceModelName($stub, $table);

            $stub = $this->replaceClassName($stub, $table);

            $stub = $this->replaceModuleInformation($stub, $model);

            $this->info('Writing model: ' . $fullPath, true);
            file_put_contents($fullPath, $stub);
        }

        $this->info('Complete Controller Generate Command');
    }

    protected function generateControllerFullPath($table)
    {
        $filename = implode('', [$this->getFilename($table), 'Controller']);
        $fullPath = "app/Http/Controllers/Admin/$filename.php";
        $this->info("Generating file: $filename.php");
        return $fullPath;
    }

    protected function getStub()
    {
        $this->info('loading controller stub');

        return __DIR__ . '/../stubs/controller.stub';
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
        return str_replace('{{class}}', implode('', [$this->getFilename($tableName), 'Controller']), $stub);
    }

    /**
     * replaces the model name in the stub.
     *
     * @param string $stub      stub content
     * @param string $tableName the name of the table to make as the model
     *
     * @return string stub content
     */
    public function replaceModelName($stub, $tableName)
    {
        return str_replace('{{model}}', $this->getFilename($tableName), $stub);
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
        // replace fillable
        $this->fieldsRules = '';
        foreach ($modelInformation['rules'] as $field) {
            // fillable and hidden
            if ($field != 'id') {
                $this->fieldsRules .= (strlen($this->fieldsRules) > 0 ? $separator : '') . "'$field' => 'required'";
            }
        }
        // replace in stub
        $stub = str_replace('{{rules}}', $this->fieldsRules, $stub);
        return $stub;
    }
}
