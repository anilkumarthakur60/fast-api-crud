<?php

namespace Anil\FastApiCrud\Commands;

class MigrateFreshSeed extends \Illuminate\Database\Console\Migrations\FreshCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:fresh --seed';
}
