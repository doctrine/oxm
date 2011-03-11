<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/10/11
 * Time: 8:56 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Marshaller;

use \Doctrine\Tests\OxmTestCase,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\Request,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO1,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO2;

class MappedSuperclassTest extends OxmTestCase
{
    private $marshaller;

    public function setUp()
    {
        $this->marshaller = $this->_getMarshaller("tests/Doctrine/Tests/OXM/Entities/MappedSuperclass");
    }

    /**
     * @test
     */
    public function itShouldDoInheritedFieldsCorrectly()
    {
        $request = new Request();
        $request->id = 1;
        $bo = new ConcreteBO1();
        $bo->inherit = 1;
        $request->bo = $bo;

        $xml = $this->marshaller->marshal($request);

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <request id="1">
                <concrete-bo1 inherit="1"/>
            </request>', $xml);

        $otherRequest = $this->marshaller->unmarshal($xml);

        $this->assertEquals(1, $otherRequest->id);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO1', $otherRequest->bo);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\AbstractBusinessObject', $otherRequest->bo);
        $this->assertEquals(1, $otherRequest->bo->inherit);
    }
}
