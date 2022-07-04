<?php

namespace Karpack\Statusable\Commands;

use Illuminate\Console\Command;
use Karpack\Contracts\Statuses\StatusesManager;

class RegisterStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statuses:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates all the missing statuses of registered models';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(StatusesManager $statusesService)
    {
        $this->info('Creating statuses');

        $statusesService->createRegisteredModelStatuses();

        $this->info('Created all statuses of models registered in the StatusServiceProvider');
    }
}