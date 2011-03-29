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
    Doctrine\Tests\OXM\Entities\CollectionClass;

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
    public function itShouldHandleCollectionsProperly()
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
}
