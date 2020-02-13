<?php

namespace Tests\Unit;

use App\Scrapers\Formatters\Pigiame as FormatterPigiame;
use DateTime;
use PHPUnit\Framework\TestCase;

class PigiameTest extends TestCase
{
    public function testFormattedString()
    {
        $formatter = new FormatterPigiame();
        $result = 'model';

        $this->assertEquals($result, $formatter->getFormattedString('Model'));
        $this->assertEquals($result, $formatter->getFormattedString(' Model'));
        $this->assertEquals($result, $formatter->getFormattedString('Model '));
        $this->assertEquals($result, $formatter->getFormattedString(' Model '));
    }

    public function testFormattedId()
    {
        $formatter = new FormatterPigiame();
        $result = 1703174;

        $this->assertEquals($result, $formatter->getFormattedId('Ad ID: 1703174'));
        $this->assertEquals($result, $formatter->getFormattedId(' Ad ID: 1703174'));
        $this->assertEquals($result, $formatter->getFormattedId('Ad ID: 1703174 '));
        $this->assertEquals($result, $formatter->getFormattedId(' Ad ID: 1703174 '));
    }

    public function testFormattedPriceArray()
    {
        $formatter = new FormatterPigiame();
        $result1 = [
            'currency' => 'ksh',
            'price' => 1350000.0
        ];
        $result2 = [
            'currency' => 'ksh',
            'price' => 500.0
        ];

        $this->assertEquals($result1, $formatter->getFormattedPriceArray('KSh 1,350,000 '));
        $this->assertEquals($result1, $formatter->getFormattedPriceArray(' KSh 1,350,000'));
        $this->assertEquals($result1, $formatter->getFormattedPriceArray('KSh 1,350,000 '));
        $this->assertEquals($result1, $formatter->getFormattedPriceArray(' KSh 1,350,000 '));

        $this->assertEquals($result2, $formatter->getFormattedPriceArray('KSh 500 '));
        $this->assertEquals($result2, $formatter->getFormattedPriceArray(' KSh 500'));
        $this->assertEquals($result2, $formatter->getFormattedPriceArray('KSh 500 '));
        $this->assertEquals($result2, $formatter->getFormattedPriceArray(' KSh 500 '));
    }

    public function testFormattedDate()
    {
        $formatter = new FormatterPigiame();

        $yesterdaysDate = new DateTime();
        $yesterdaysDate->modify('-1 days');
        $result1 = DateTime::createFromFormat('l, H:i', 'Monday, 09:02');

        if ($yesterdaysDate <= $result1) {
            $result1->modify('-1 weeks');
        }

        $result2 = new DateTime();
        $result2->modify('-1 days');
        $result2->setTime(13, 24);

        $result3 = DateTime::createFromFormat('Y-m-d H:i', '2018-03-05 10:41');
        $result4 = DateTime::createFromFormat('m-d H:i', '01-28 20:25');

        $this->assertEquals($result1, $formatter->getFormattedDate('Monday, 09:02'));
        $this->assertEquals($result2, $formatter->getFormattedDate('Yesterday, 13:24'));
        $this->assertEquals($result3, $formatter->getFormattedDate('5. Mar \'18, 10:41'));
        $this->assertEquals($result4, $formatter->getFormattedDate('28. Jan, 20:25'));
    }

    public function testFormattedMileage()
    {
        $formatter = new FormatterPigiame();
        $result1 = [
            'mileage' => 147000,
            'unit' => 'km'
        ];
        $result2 = [
            'mileage' => 500,
            'unit' => 'mi'
        ];

        $this->assertEquals($result1, $formatter->getFormattedMileage(' 147,000 km'));
        $this->assertEquals($result1, $formatter->getFormattedMileage('147,000 km '));
        $this->assertEquals($result1, $formatter->getFormattedMileage(' 147,000 km '));

        $this->assertEquals($result2, $formatter->getFormattedMileage(' 500 mi'));
        $this->assertEquals($result2, $formatter->getFormattedMileage('500 mi '));
        $this->assertEquals($result2, $formatter->getFormattedMileage(' 500 mi '));
    }
}
