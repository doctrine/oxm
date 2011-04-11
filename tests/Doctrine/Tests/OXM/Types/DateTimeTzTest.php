<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type,
    Doctrine\OXM\Types\DateTimeTzType;

class DateTimeTzTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\DateTimeTzType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('datetimetz');
    }

    public function testName()
    {
        $this->assertEquals('datetimetz', $this->_type->getName());
    }

    public function testDateTimeConvertsToXmlValue()
    {
        $date = new \DateTime('1985-09-01 10:10:10+0200');

        $expected = '1985-09-01 10:10:10+0200';
        $actual = $this->_type->convertToXmlValue($date);

        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeConvertsToPHPValue()
    {
        // Birthday of jwage and also birthday of Doctrine. Send him a present ;)
        $date = $this->_type->convertToPHPValue('1985-09-01 00:00:00+0200');
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertEquals('1985-09-01 00:00:00+0200', $date->format('Y-m-d H:i:sO'));
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