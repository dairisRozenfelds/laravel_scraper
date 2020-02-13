<?php

namespace App\Scrapers;

use App\ScraperModels\Pigiame as PigiameModel;
use App\Scrapers\Formatters\Pigiame as FormatterPigiame;
use Clue\React\Buzz\Browser;
use Clue\React\Mq\Queue;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use Symfony\Component\DomCrawler\Crawler;
use function json_encode;

class Pigiame implements ScraperInterface
{
    /**
     * Website URL that will be scraped
     */
    const WEBSITE_URL = 'https://www.pigiame.co.ke/cars';

    /**
     * Mapping for fetched property names from the website to the model's column names
     */
    const VEHICLE_MODEL_COLUMN_MAPPING = [
        'ad id' => 'ad_id',
        'location' => 'location',
        'region' => 'region',
        'currency' => 'currency',
        'price' => 'price',
        'ad date inserted' => 'ad_date_inserted',
        'condition' => 'condition',
        'make' => 'make',
        'model' => 'model',
        'transmission' => 'transmission',
        'drive type' => 'drive_type',
        'mileage' => 'mileage',
        'mileage unit' => 'mileage_unit',
        'build year' => 'build_year',
        'car features' => 'car_features'
    ];

    /**
     * Request base delay in seconds
     * @var float
     */
    protected $requestBaseDelay = 0.5;

    /**
     * Sleep interval from (use INT type numbers)
     * @var int
     */
    protected $randomSleepIntervalFrom = 50;

    /**
     * Sleep interval to (use INT type numbers)
     * @var int
     */
    protected $randomSleepIntervalTo = 150;

    /**
     * Concurrent request count
     * @var int
     */
    protected $concurrentRequests = 2;

    /**
     * Request timeout in seconds when the request will be ignored if no response is received
     * @var int
     */
    protected $requestTimeout = 5;

    /**
     * Limit for how much entries will be saved
     * @var int
     */
    protected $entryLimit = 20;

    /**
     * Model insert batch limit
     * @var int
     */
    protected $entryInsertBatchLimit = 5;

    /**
     * @var Browser|null
     */
    protected $browserClient;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * Vehicle detail card URLs that will be used to fetch vehicle model's data
     * @var array
     */
    protected $listingCardUrls = [];

    /**
     * Frontpage page counter
     * @var int
     */
    protected $frontpageNumber = 1;

    /**
     * Parsed vehicle data
     * @var array
     */
    protected $vehicleData = [];

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function beginScrape()
    {
        if ($this->entryInsertBatchLimit > $this->entryLimit) {
            throw new Exception('Batch insert limit set larger than entry limit');
        }

        $this->loop = LoopFactory::create();
        $this->browserClient = new Browser($this->loop);

        // Scrape the frontpages synchronously
        $this->scrapeFrontPage();
        $this->loop->run();

        $asyncQueue = new Queue($this->concurrentRequests, null, function ($url) {
            return $this->browserClient->get($url);
        });

        foreach ($this->listingCardUrls as $listingCardUrl) {
            $asyncQueue($listingCardUrl)->then([$this, 'detailPageResponseSuccess']);
        }

        // Execute the vehicle data fetching
        $this->loop->run();

        $batchCount = 0;
        $vehicleBatchData = [];

        // Save vehicle data
        foreach ($this->vehicleData as $key => $vehicleData) {
            $vehicleBatchData[] = $vehicleData;
            $batchCount++;

            if (($batchCount % $this->entryInsertBatchLimit) === 0) {
                $this->saveVehicleData($vehicleBatchData);

                $vehicleBatchData = [];
                $batchCount = 0;
            }
        }

        if (count($vehicleBatchData) > 0) {
            $this->saveVehicleData($this->vehicleData);
        }
    }

    /**
     * Inserts the vehicle data in DB
     * @param array $data
     * @throws Exception
     */
    protected function saveVehicleData(array $data)
    {
        PigiameModel::insert($this->getFormattedModelInsertArray($data));
    }

    /**
     * Returns formatted array with using the mapping constant
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function getFormattedModelInsertArray(array $data)
    {
        $formattedData = [];

        foreach ($data as $vehicleData) {
            $formattedVehicle = [];

            foreach (self::VEHICLE_MODEL_COLUMN_MAPPING as $property => $column) {
                $formattedVehicle[$column] = $vehicleData[$property] ?? null;
            }

            $formattedVehicle['created_at'] = new DateTime();

            $formattedData[] = $formattedVehicle;
        }

        return $formattedData;
    }

    /**
     * Front page request successful response handler
     *
     * @param ResponseInterface $response
     */
    public function frontPageResponseSuccess(ResponseInterface $response)
    {
        $crawler = new Crawler((string)$response->getBody());
        $listingCards = $crawler->filter('.listings-cards a.listing-card__inner');

        if ($listingCards->count() > 0) {
            foreach ($listingCards as $listingCard) {
                if ($this->entryLimit && count($this->listingCardUrls) === $this->entryLimit) {
                    $this->loop->stop();
                    return;
                }

                $this->listingCardUrls[] = $listingCard->getAttribute('href');
            }

            Log::info('Frontpage parsed', ['page_number' => $this->frontpageNumber]);
            $this->frontpageNumber++;
            $this->randomSleep();

            // If the page has result, queue to the next one
            $this->loop->futureTick([$this, 'scrapeFrontPage']);
        }
    }

    /**
     * Create a request to the front page of the website
     */
    public function scrapeFrontPage()
    {
        $url = self::WEBSITE_URL;

        if ($this->frontpageNumber > 1) {
            $args = parse_url($url, PHP_URL_QUERY);
            $url .= $args ? '&p=' : '?p=';
            $url .= $this->frontpageNumber;
        }

        $promise = $this->browserClient->get($url)->then([$this, 'frontPageResponseSuccess']);

        $this->loop->addTimer($this->requestTimeout, function () use ($promise) {
            $promise->cancel();
        });
    }

    /**
     * Detail page successful response handler
     *
     * @param ResponseInterface $response
     * @throws Exception
     */
    public function detailPageResponseSuccess(ResponseInterface $response)
    {
        $crawler = new Crawler((string)$response->getBody());
        $formatter = new FormatterPigiame();
        $vehicle = [];

        $vehicle['ad id'] = $formatter->getFormattedId(
            $crawler->filter('.listing-item__details .listing-item__details__ad-id')->text()
        );
        $vehicle['location'] = $formatter->getFormattedString(
            $crawler->filter('.listing-item__address .listing-item__address-location')->text()
        );
        $vehicle['region'] = $formatter->getFormattedString(
            $crawler->filter('.listing-item__address .listing-item__address-region')->text()
        );

        $priceArray = $formatter->getFormattedPriceArray(
            $crawler->filter('.listing-item__price .listing-card__price__value')->text()
        );

        $vehicle['price'] = $priceArray['price'];
        $vehicle['currency'] = $priceArray['currency'];

        $vehicle['ad date inserted'] = $formatter->getFormattedDate(
            $crawler->filter('.listing-item__details .listing-item__details__date')->text()
        );

        $labels = $crawler->filter('dl.listing-item__properties dt.listing-item__properties__title');

        $labels->each(function ($label) use (&$vehicle, $formatter) {
            $value = $label->filter('dt.listing-item__properties__title + dd.listing-item__properties__description');

            if ($propertyLabel = $formatter->getFormattedString($label->text())) {
                switch ($propertyLabel) {
                    // Car features are saved as a list, therefore parse them as an array
                    case 'car features':
                        $labelList = [];

                        // Populate label list value array
                        $value->filter('li')->each(function ($listItem) use (&$labelList, $formatter) {
                            if ($labelListValue = $formatter->getFormattedString($listItem->text())) {
                                $labelList[] = $labelListValue;
                            }
                        });

                        $vehicle[$propertyLabel] = json_encode($labelList);
                        break;
                    // Break mileage into numeric mileage and the units that are used
                    case 'mileage':
                        $mileageArray = $formatter->getFormattedMileage($value->text());

                        $vehicle['mileage'] = $mileageArray['mileage'];
                        $vehicle['mileage unit'] = $mileageArray['unit'];
                        break;
                    default:
                        $vehicle[$propertyLabel] = $formatter->getFormattedString($value->text());
                }
            }
        });

        $this->vehicleData[] = $vehicle;
        Log::info('Detailpage parsed', ['vehicle' => $vehicle]);
    }

    /**
     * Sleeps for a random time from the defined parameters
     */
    protected function randomSleep()
    {
        $sleepTime = rand($this->randomSleepIntervalFrom, $this->randomSleepIntervalTo);

        usleep($sleepTime + ($this->requestBaseDelay * 1000000));
    }
}
