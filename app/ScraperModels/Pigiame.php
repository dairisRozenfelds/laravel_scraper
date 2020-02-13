<?php

namespace App\ScraperModels;

use DateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pigiame
 * @package App\ScraperModels
 * @property int $id
 * @property string $location
 * @property string $region
 * @property string $currency
 * @property float $price
 * @property string $condition
 * @property string $make
 * @property string $model
 * @property string $transmission
 * @property string $drive_type
 * @property int $mileage
 * @property string $mileage_unit
 * @property int $build_year
 * @property array $car_features
 * @property DateTime $ad_date_inserted
 * @property DateTime $created_at
 * @property DateTime $updated_at
 */
class Pigiame extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'scraper_pigiame';
}
