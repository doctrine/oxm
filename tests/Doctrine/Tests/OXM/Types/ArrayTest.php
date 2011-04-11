<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;

class ArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\ArrayType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('array');
    }

    public function tearDown()
    {
        error_reporting(-1); // reactive all error levels
    }

    public function testName()
    {
        $this->assertEquals('array', $this->_type->getName());
    }

    public function testArrayConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToXmlValue(array()))
        );
    }

    public function testArrayConvertsToPHPValue()
    {
        $this->assertTrue(
            is_array($this->_type->convertToPHPValue(serialize(array())))
        );
    }

    public function testConversionFailure()
    {
        error_reporting( (E_ALL | E_STRICT) - \E_NOTICE );
        $this->setExpectedException('Doctrine\OXM\Types\ConversionException');
        $this->_type->convertToPHPValue('abcdefg');
    }

    public function testNullConversion()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }

    /**
     * @group DBAL-73
     */
    public function testFalseConversion()
    {
        $this->assertFalse($this->_type->convertToPHPValue(serialize(false)));
    }
}