<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type,
    Doctrine\OXM\Types\DateTimeType;

class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\DateTimeType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('datetime');
    }

    public function testName()
    {
        $this->assertEquals('datetime', $this->_type->getName());
    }

    public function testDateTimeConvertsToXmlValue()
    {
        $date = new \DateTime('1985-09-01 10:10:10');

        $actual = $this->_type->convertToXmlValue($date);

        $this->assertEquals('1985-09-01 10:10:10', $actual);
    }

    public function testDateTimeConvertsToPHPValue()
    {
        // Birthday of jwage and also birthday of Doctrine. Send him a present ;)
        $date = $this->_type->convertToPHPValue('1985-09-01 00:00:00');
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertEquals('1985-09-01 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    /**
     * @expectedException \Doctrine\OXM\Types\ConversionException
     */
    public function testInvalidDateTimeFormatConversion()
    {
        $this->_type->convertToPHPValue('abcdefg');
    }

    public function testNullConversion()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }

    public function testThatXmlValueFormatCanBeCustomised()
    {
        $date = new \DateTime('1985-09-01T10:10:10', new \DateTimeZone('America/New_York'));

        $iso8601 = $this->_type->convertToXmlValue($date, array('format' => 'c'));
        $custom = $this->_type->convertToXmlValue($date, array('format' => 'D, jS M Y'));

        $this->assertEquals('1985-09-01T10:10:10-04:00', $iso8601);
        $this->assertEquals('Sun, 1st Sep 1985', $custom);
    }

    public function testThatPhpValueFormatCanBeCustomised()
    {
        $iso8601 = $this->_type->convertToPhpValue('1985-09-01T10:10:10-04:00', array('format' => 'c'));
        $custom = $this->_type->convertToPhpValue('Sun, 1st Sep 1985', array('format' => 'D, jS M Y'));

        $this->assertInstanceOf('\DateTime', $iso8601);
        $this->assertInstanceOf('\DateTime', $custom);
        $this->assertEquals('1985-09-01T10:10:10-04:00', $iso8601->format('c'));
        $this->assertEquals('Sun, 1st Sep 1985', $custom->format('D, jS M Y'));
    }

    /**
     * @expectedException \Doctrine\OXM\Types\ConversionException
     */
    public function testUnsupportedFormat()
    {
        $this->_type->convertToPhpValue('1985-1981-01T10:10:10', array('format' => 'c'));
    }
}
