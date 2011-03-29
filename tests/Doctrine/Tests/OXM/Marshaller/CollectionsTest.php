<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/10/11
 * Time: 8:56 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Marshaller;

use Doctrine\Tests\OxmTestCase,
    Doctrine\Tests\OXM\Entities\CollectionClass,
    Doctrine\Tests\OXM\Entities\CollectionAttributeClass;

class CollectionsTest extends OxmTestCase
{
    /** @var \Doctrine\OXM\Marshaller\XmlMarshaller */
    private $marshaller;

    public function setUp()
    {
        $this->marshaller = $this->_getMarshaller("tests/Doctrine/Tests/OXM/Entities");
    }

    /**
     * @test
     */
    public function itShouldHandleXmlTextCollectionsProperly()
    {
        $request = new CollectionClass();
        $request->list = array('one', 'two', 'three');

        $xml = $this->marshaller->marshalToString($request);

        $this->assertXmlStringEqualsXmlString('<collection-class>
            <list>one</list>
            <list>two</list>
            <list>three</list>
        </collection-class>', $xml);

        $otherRequest = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(3, count($otherRequest->list));
        $this->assertContains('one', $otherRequest->list);
        $this->assertContains('two', $otherRequest->list);
        $this->assertContains('three', $otherRequest->list);
    }

    /**
     * @test
     */
    public function itShouldHandleXmlAttributeCollectionsProperly()
    {
        $colorContainer = new CollectionAttributeClass();
        $colorContainer->colors = array('red', 'green', 'blue');

        $xml = $this->marshaller->marshalToString($colorContainer);

        $this->assertXmlStringEqualsXmlString('<collection-attribute-class colors="red green blue" />', $xml);

        $otherContainer = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(3, count($otherContainer->colors));
        $this->assertContains('red', $otherContainer->colors);
        $this->assertContains('green', $otherContainer->colors);
        $this->assertContains('blue', $otherContainer->colors);
    }
}
