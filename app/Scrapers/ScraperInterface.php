<?php

namespace App\Scrapers;

interface ScraperInterface
{
    /**
     * Executes the scraping algorithm
     *
     * @return int
     */
    public function beginScrape();
}
