<?php

namespace Doctrine\Tests\OXM\Types;

use \Doctrine\OXM\Types\Type;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Doctrine\OXM\OXMException
     */
    public function testGetUnknownType()
    {
       Type::getType('foo');
    }

    /**
     * @expectedException Doctrine\OXM\OXMException
     * @backupStaticAttributes enabled
     */
    public function testAddTypeExists()
    {
        Type::addType('string', 'Doctrine\\Tests\\Mocks\\TypeMock');
    }

    /**
     * @backupStaticAttributes enabled
     */
    public function testAddType()
    {
        Type::addType('mock', 'Doctrine\\Tests\\Mocks\\TypeMock');
        $this->assertTrue(Type::hasType('mock'));
        $this->assertEquals('mock', Type::getType('mock')->getName());
    }

    /**
     * @expectedException Doctrine\OXM\OXMException
     */
    public function testOverrideTypeNotFound()
    {
        Type::overrideType('foo', 'Doctrine\\Tests\\Mocks\\TypeMock');
    }

    /**
     * @backupStaticAttributes enabled
     */
    public function testOverrideType()
    {
        Type::overrideType('string', 'Doctrine\\Tests\\Mocks\\TypeMock');
        $typesMap = Type::getTypesMap();
        $this->assertEquals('Doctrine\\Tests\\Mocks\\TypeMock', $typesMap['string']);
        $this->assertInstanceOf('Doctrine\\Tests\\Mocks\\TypeMock', Type::getType('string'));
    }

    /**
     * @backupStaticAttributes enabled
     */
    public function testOverrideTypeAlreadyInstantiated()
    {
        Type::getType('string');
        Type::overrideType('string', 'Doctrine\\Tests\\Mocks\\TypeMock');
        $this->assertInstanceOf('Doctrine\\Tests\\Mocks\\TypeMock', Type::getType('string'));
    }
}
