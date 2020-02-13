<?php

namespace App\Scrapers\Formatters;

use App\Scrapers\Pigiame as ScraperPigiame;
use DateTime;
use Exception;

class Pigiame
{
    /**
     * Regular expressions and date format mappings
     */
    const DATE_TYPE_MAPPING_TYPE_1 = [
        'regex' => '/^(Today|Yesterday), (\d{2}):(\d{2})$/'
    ];

    const DATE_TYPE_MAPPING_TYPE_2 = [
        'regex' => '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), \d{2}:\d{2}$/',
        'date_format' => 'l, H:i'
    ];

    const DATE_TYPE_MAPPING_TYPE_3 = [
        'regex' => '/^\d{1,2}. [a-zA-Z]{3}, \d{2}:\d{2}$/',
        'date_format' => 'j. M, H:i'
    ];

    const DATE_TYPE_MAPPING_TYPE_4 = [
        'regex' => '/^\d{1,2}. [a-zA-Z]{3} \'\d{2}, \d{2}:\d{2}$/',
        'date_format' => 'j. M \'y, H:i'
    ];

    const DATE_TYPE_MAPPING_TYPE_5 = [
        'regex' => '/^(Today|Yesterday), \d{2}:\d{2}$/',
        'date_format' => 'l, H:i'
    ];

    /**
     * @param $string
     * @return int
     */
    public function getFormattedId($string)
    {
        sscanf($this->getFormattedString($string), 'ad id: %d', $id);

        return $id;
    }

    /**
     * Returns the passed string in lowercase and trimmed from both ends
     *
     * @param $string
     * @return string
     */
    public function getFormattedString($string)
    {
        return trim(strtolower($string));
    }

    /**
     * Returns formatted price array in the format
     * [
     *   'currency' => 'KSh',
     *   'price' => 50000
     * ]
     *
     * @param $string
     * @return array
     */
    public function getFormattedPriceArray($string)
    {
        $string = trim($string);

        $priceArray = explode(' ', $string);
        $currency = $priceArray[0] ?? null;
        $price = $priceArray[1] ?? null;
        $price = str_replace(',', '', $price);

        return [
            'currency' => strtolower($currency),
            'price' => (float)$price
        ];
    }

    /**
     * Returns DateTime object if the passed string matches the defined formats
     *
     * @param $string
     * @return DateTime|null
     * @throws Exception
     */
    public function getFormattedDate($string)
    {
        $string = trim($string);

        if (preg_match(self::DATE_TYPE_MAPPING_TYPE_1['regex'], $string, $matched)) {
            $date = new DateTime();

            if ($matched[1] === 'Yesterday') {
                $date->modify('-1 days');
            }

            $date->setTime($matched[2], $matched[3]);
        } elseif (preg_match(self::DATE_TYPE_MAPPING_TYPE_2['regex'], $string, $matched)) {
            $yesterdaysDate = new DateTime();
            $yesterdaysDate->modify('-1 days');
            $date = DateTime::createFromFormat(self::DATE_TYPE_MAPPING_TYPE_2['date_format'], $string);

            /**
             * If the date is larger than yesterday's date, then set it to previous week
             * because the scraped data shows weekday names only after the yesterday's date
             */
            if ($yesterdaysDate <= $date) {
                $date->modify('-1 weeks');
            }
        } elseif (preg_match(self::DATE_TYPE_MAPPING_TYPE_3['regex'], $string, $matched)) {
            $date = DateTime::createFromFormat(self::DATE_TYPE_MAPPING_TYPE_3['date_format'], $string);
        } elseif (preg_match(self::DATE_TYPE_MAPPING_TYPE_4['regex'], $string, $matched)) {
            $date = DateTime::createFromFormat(self::DATE_TYPE_MAPPING_TYPE_4['date_format'], $string);
        } elseif (preg_match(self::DATE_TYPE_MAPPING_TYPE_5['regex'], $string, $matched)) {
            $date = DateTime::createFromFormat(self::DATE_TYPE_MAPPING_TYPE_5['date_format'], $string);
        } else {
            $date = null;
        }

        return $date;
    }

    /**
     * Returns formatted price array in the format
     * [
     *   'mileage' => 50000,
     *   'unit' => km
     * ]
     *
     * @param $string
     * @return array
     */
    public function getFormattedMileage($string)
    {
        $string = trim($string);

        $mileageArray = explode(' ', $string);
        $mileage = $mileageArray[0] ?? null;
        $mileage = str_replace(',', '', $mileage);
        $unit = $mileageArray[1] ?? null;

        return [
            'mileage' => $mileage,
            'unit' => $unit
        ];
    }
}
