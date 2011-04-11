<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;

 
class DateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\DateType
     */
    protected $_type;
    protected $_tz;

    protected function setUp()
    {
        $this->_type = Type::getType('date');
        $this->_tz = date_default_timezone_get();
    }

    public function tearDown()
    {
        date_default_timezone_set($this->_tz);
    }

    public function testName()
    {
        $this->assertEquals('date', $this->_type->getName());
    }

    public function testDateConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToXmlValue(new \DateTime()))
        );
    }

    public function testDateConvertsToPHPValue()
    {
        // Birthday of jwage and also birthday of Doctrine. Send him a present ;)
        $this->assertInstanceOf('\DateTime', $this->_type->convertToPHPValue('1985-09-01'));
    }

    public function testDateResetsNonDatePartsToZeroUnixTimeValues()
    {
        $date = $this->_type->convertToPHPValue('1985-09-01');

        $this->assertEquals('00:00:00', $date->format('H:i:s'));
    }

    public function testDateRests_SummerTimeAffection()
    {
        date_default_timezone_set('Europe/Berlin');

        $date = $this->_type->convertToPHPValue('2009-08-01');
        $this->assertEquals('00:00:00', $date->format('H:i:s'));
        $this->assertEquals('2009-08-01', $date->format('Y-m-d'));

        $date = $this->_type->convertToPHPValue('2009-11-01');
        $this->assertEquals('00:00:00', $date->format('H:i:s'));
        $this->assertEquals('2009-11-01', $date->format('Y-m-d'));
    }
    
    public function testInvalidDateFormatConversion()
    {
        $this->setExpectedException('Doctrine\OXM\Types\ConversionException');
        $this->_type->convertToPHPValue('abcdefg');
    }

    public function testNullConversion()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }
}