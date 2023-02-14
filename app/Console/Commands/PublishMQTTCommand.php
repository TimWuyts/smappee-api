<?php

namespace App\Console\Commands;

use App\Http\Controllers\SmappeeController;
use Illuminate\Console\Command;

class PublishMQTTCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:publish {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish service location information to MQTT broker';

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
     *
     * @return int
     */
    public function handle()
    {
        $serviceLocationId = $this->argument('id');

        $smappee = new SmappeeController();
        $smappee->publish($serviceLocationId);

        return 0;
    }
}
