<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;
 
class TimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\TimeType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('time');
    }

    public function testName()
    {
        $this->assertEquals('time', $this->_type->getName());
    }

    public function testTimeConvertsToDatabaseValue()
    {
        $this->assertInternalType('string', $this->_type->convertToXmlValue(new \DateTime()));
    }

    public function testTimeConvertsToPHPValue()
    {
        $this->assertInstanceOf('\DateTime', $this->_type->convertToPHPValue('5:30:55'));
    }

    public function testInvalidTimeFormatConversion()
    {
        $this->setExpectedException('Doctrine\OXM\Types\ConversionException');
        $this->_type->convertToPHPValue('abcdefg');
    }

    public function testNullConversion()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }
}