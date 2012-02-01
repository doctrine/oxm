<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\OXM\Marshaller;

use \Doctrine\Tests\OxmTestCase,
    \Doctrine\Tests\OXM\Entities\NamespaceEntities\Foo,
    \Doctrine\Tests\OXM\Entities\NamespaceEntities\Qux,
    \Doctrine\Tests\OXM\Entities\NamespaceEntities\Baz;

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

    /**
     * @test
     */
    public function itShouldMarshalNamespacedNode()
    {
        $qux = new Qux();
        $qux->baz = new Baz();
        $qux->baz->title = "Baz";
        $qux->bas = "Bas";

        $xml = $this->marshaller->marshalToString($qux);

        $expectedXml = '<?xml version="1.0" encoding="UTF-8"?>
            <qux xmlns="http://www.foobar.com/schema" xmlns:bat="http://www.foobaz.com/schema">
                <bas>Bas</bas>
                <bat:baz>Baz</bat:baz>
            </qux>';

        $this->assertXmlStringEqualsXmlString($expectedXml, $xml);
    }

    /**
     * @test
     */
    public function itShouldUnmarshalNonNamespacedNodePresentAfterNamespacedNode()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <qux xmlns="http://www.foobar.com/schema" xmlns:bat="http://www.foobaz.com/schema">
                <bat:baz>Baz</bat:baz>
                <bas>Bas</bas>
            </qux>';

        $qux = $this->marshaller->unmarshalFromString($xml);

        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\NamespaceEntities\Baz', $qux->baz);
        $this->assertEquals('Baz', $qux->baz->title);
        $this->assertEquals('Bas', $qux->bas);
    }
}
