<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;
 
class ObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\ObjectType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('object');
    }

    public function testName()
    {
        $this->assertEquals('object', $this->_type->getName());
    }

    public function tearDown()
    {
        error_reporting(-1); // reactive all error levels
    }

    public function testObjectConvertsToDatabaseValue()
    {
        $this->assertInternalType('string', $this->_type->convertToXmlValue(new \stdClass()));
    }

    public function testObjectConvertsToPHPValue()
    {
        $this->assertInternalType('object', $this->_type->convertToPHPValue(serialize(new \stdClass)));
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