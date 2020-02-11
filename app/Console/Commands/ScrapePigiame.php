<?php

namespace App\Console\Commands;

use Clue\React\Buzz\Browser;
use Illuminate\Console\Command;
use React\EventLoop\Factory as LoopFactory;

class ScrapePigiame extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'scrape:pigiame';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Command description';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $loop = LoopFactory::create();
        $client = new Browser($loop);
        $response = $client->get('https://www.pigiame.co.ke/cars');
    }
}
