<?php

namespace App\Console\Commands;

use App\Services\Content\CourseImporter;
use App\Services\Content\ImportException;
use Illuminate\Console\Command;

class ImportCourse extends Command
{
    protected $signature = 'content:import {slug : Folder name under content/} {--prune : Delete lessons/practices no longer in the files}';

    protected $description = 'Import or update a course from content/<slug>/ into the catalog';

    public function handle(CourseImporter $importer): int
    {
        try {
            $result = $importer->import($this->argument('slug'), (bool) $this->option('prune'));
        } catch (ImportException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Imported "%s": %d lessons, %d practices%s.',
            $result['course']->title, $result['lessons'], $result['practices'],
            $result['pruned'] ? ", pruned {$result['pruned']} lessons" : ''));

        return self::SUCCESS;
    }
}
