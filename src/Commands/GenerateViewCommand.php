<?php

namespace Fznoviar\SuitGenerator\Commands;

use DB;
use Form;
use Illuminate\Console\Command;
use Schema;

class GenerateViewCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suit:view 
                            {--table= : a single table or a list of tables separated by a comma (,)}
                            {--all : run for all tables}
                            {--connection= : database connection to use, default use from .env connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate view for the given tables based on their columns';

    protected $views;

    protected $fieldsForms;

    protected $fieldsFormTranslates;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->views = [
            '_translate-header',
            'index',
            'index-ajax',
            'create',
            'edit',
            'form',

        ];
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting View Generate Command');

        $this->getOptions();

        if (!$this->isExecutable()) {
            return;
        }

        $tables = $this->getTables();
        foreach ($this->views as $view) {
            ${$view . 'Stub'} = file_get_contents($this->getStub($view));
        }

        foreach ($tables as $table) {
            foreach ($this->views as $view) {
                $stub = ${$view . 'Stub'};
                $fullPath = $this->generateFullPath($table, $view);
                
                if ($view == 'form') {
                    $this->setColumns($table);
                    $this->resetFields();

                    $this->replaceFolderName($stub, $table);
                    
                    $this->replaceForms($stub, $table);
                }
                $this->info('Writing model: ' . $fullPath, true);
                file_put_contents($fullPath, $stub);
            }
        }

        $this->info('Complete View Generate Command');
    }

    protected function getStub($file)
    {
        $this->info('loading views stub');

        return __DIR__ . '/../stubs/views/' . $file . '.stub';
    }

    protected function generateFullPath($table, $filename)
    {
        $path = sprintf('resources/views/admins/%s/', $table);
        if (!file_exists($path)) {
            mkdir($path);
        }
        $fullPath = sprintf('%s/%s', $path, $filename);
        $this->info("Generating file: $filename.blade.php");
        return $fullPath;
    }

    protected function replaceFolderName($stub, $tableName)
    {
        return str_replace('{{folder}}', str_replace('_', '-', $tableName), $stub);
    }

    protected function replaceForms($stub, $tableName)
    {
        $separator = "\n        ";
        // replace fillable
        $this->fieldsForms = '';
        $this->fieldsFormTranslates = '';
        foreach ($this->columns->where('translate', false) as $field) {
            if ($field != 'id') {
                $this->fieldsForms .= (strlen($this->fieldsForms) > 0 ? $separator : '') . $this->getFormField($field);
            }
        }
        foreach ($this->columns->where('translate', true) as $field) {
            if ($field != 'id') {
                $this->fieldsFormTranslates .= (strlen($this->fieldsFormTranslates) > 0 ? $separator : '') . $this->getFormField($field);
            }
        }
        $stub = str_replace('{{forms}}', $this->fieldsForms, $stub);
        $stub = str_replace('{{form_translate}}', $this->fieldsFormTranslates, $stub);
    }

    protected function getFormField($field)
    {
        if (str_contains($field['type'], 'varchar')) {
            return '{{ Form::suitText(' . $field['field'] . ', ' . $this->getFieldVerbose($field['field']) . ') }}';
        } elseif (str_contains($field['type'], 'int')) {
            return '{{ Form::suitNumber(' . $field['field'] . ', ' . $this->getFieldVerbose($field['field']) . ') }}';
        } elseif (str_contains($field['type'], 'datetime')) {
            return '{{ Form::suitDateTime(' . $field['field'] . ', ' . $this->getFieldVerbose($field['field']) . ') }}';
        }
        return '';
    }

    protected function getFieldVerbose($field)
    {
        return ucwords(str_replace('_', ' ', str_singular($field)));
    }

    /**
     * reset all variables to be filled again when using multiple
     */
    protected function resetFields()
    {
        $this->fieldsForms = '';
        $this->fieldsFormTranslates = '';
    }
}
