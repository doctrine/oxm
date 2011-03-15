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
    \Doctrine\Tests\OXM\Entities\NamespaceEntities\Foo;

class NamespacesTest extends OxmTestCase
{
    private $marshaller;

    public function setUp()
    {
        $this->marshaller = $this->_getMarshaller("tests/Doctrine/Tests/OXM/Entities/NamespaceEntities");
    }

    /**
     * @test
     */
    public function itShouldWriteNamespacesCorrectly()
    {
        $request = new Foo();
        $request->id = 1;
        $request->bo = "bar";

        $xml = $this->marshaller->marshalToString($request);

        $this->assertXmlStringEqualsXmlString('<foo xmlns="http://www.foobar.com/schema" xmlns:baz="http://www.foobaz.com/schema">
            <id>1</id>
                <baz:bo>bar</baz:bo>
            </foo>', $xml);

        $otherRequest = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(1, $otherRequest->id);
        $this->assertEquals("bar", $otherRequest->bo);
    }
}
