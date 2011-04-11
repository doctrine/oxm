<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;

 
class BooleanTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\BooleanType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('boolean');
    }

    public function testName()
    {
        $this->assertEquals('boolean', $this->_type->getName());
    }

    public function testBooleanConvertsToXmlValue()
    {
        $this->assertInternalType('string', $this->_type->convertToXmlValue(1));
        $this->assertInternalType('string', $this->_type->convertToXmlValue("true"));
        $this->assertInternalType('string', $this->_type->convertToXmlValue("false"));
    }

    public function testBooleanConvertsToPHPValue()
    {
        $this->assertInternalType('bool', $this->_type->convertToPHPValue(0));
        $this->assertInternalType('bool', $this->_type->convertToPHPValue(1));
        $this->assertInternalType('bool', $this->_type->convertToPHPValue(true));
        $this->assertInternalType('bool', $this->_type->convertToPHPValue(false));
        $this->assertInternalType('bool', $this->_type->convertToPHPValue("true"));
        $this->assertInternalType('bool', $this->_type->convertToPHPValue("false"));

        $this->assertTrue($this->_type->convertToPHPValue("true"));
        $this->assertTrue($this->_type->convertToPHPValue("1"));

        $this->assertFalse($this->_type->convertToPHPValue("false"));
    }

    public function testBooleanNullConvertsToPHPValue()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }
}