<?php

namespace App\Console\Commands;

use App\Scrapers\Pigiame;
use Exception;
use Illuminate\Console\Command;

class ScrapePigiame extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'scrape:pigiame';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Scrape cars from the the website: ' . Pigiame::WEBSITE_URL;

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function handle()
    {
        $scraper = new Pigiame();
        $scraper->beginScrape();
    }
}
