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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\OXM\Marshaller;

use Doctrine\Tests\OxmTestCase,
    Doctrine\Tests\OXM\Entities\Collections\CollectionClass,
    Doctrine\Tests\OXM\Entities\Collections\CollectionAttributeClass,
    Doctrine\Tests\OXM\Entities\Collections\WrapperForElement,
    Doctrine\Tests\OXM\Entities\Collections\Element,
    Doctrine\Tests\OXM\Entities\Collections\WrapperForSuperclass,
    Doctrine\Tests\OXM\Entities\Collections\ChildA,
    Doctrine\Tests\OXM\Entities\Collections\ChildB,
    Doctrine\Tests\OXM\Entities\Collections\Wrapper;

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
    public function itShouldHandleTextCdataCollectionProperly()
    {
        $request = new CollectionClass();
        $request->list = array('<one>', '<two>', '<three>');

        $xml = $this->marshaller->marshalToString($request);

        $this->assertXmlStringEqualsXmlString('<collection-class>
            <list><![CDATA[<one>]]></list>
            <list><![CDATA[<two>]]></list>
            <list><![CDATA[<three>]]></list>
        </collection-class>', $xml);

        $otherRequest = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(3, count($otherRequest->list));
        $this->assertContains('<one>', $otherRequest->list);
        $this->assertContains('<two>', $otherRequest->list);
        $this->assertContains('<three>', $otherRequest->list);
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

    /**
     * @test
     */
    public function collectionWrapsXmlText()
    {
        $wrapper = new Wrapper();
        $wrapper->list = array('red', 'green', 'blue');
        $wrapper->enum = array('one', 'two', 'three', 'four');

        $xml = $this->marshaller->marshalToString($wrapper);

        $this->assertXmlStringEqualsXmlString('<wrapper xmlns:prfx="http://www.foo.bar.baz.com/schema">
            <foo>
                <list>red</list>
                <list>green</list>
                <list>blue</list>
            </foo>
            <prfx:bar>
                <prfx:enum>one</prfx:enum>
                <prfx:enum>two</prfx:enum>
                <prfx:enum>three</prfx:enum>
                <prfx:enum>four</prfx:enum>
            </prfx:bar>
        </wrapper>', $xml);

        $otherWrapper = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(3, count($otherWrapper->list));
        $this->assertContains('red', $otherWrapper->list);
        $this->assertContains('green', $otherWrapper->list);
        $this->assertContains('blue', $otherWrapper->list);

        $this->assertEquals(4, count($otherWrapper->enum));
        $this->assertContains('one', $otherWrapper->enum);
        $this->assertContains('two', $otherWrapper->enum);
        $this->assertContains('three', $otherWrapper->enum);
        $this->assertContains('four', $otherWrapper->enum);
    }

    /**
     * @test
     */
    public function collectionWrapsXmlElement()
    {
        $wrapperForElement = new WrapperForElement();
        $element = new Element();
        $element->attribute = 'blue';

        $element2 = new Element();
        $element2->attribute = 'red';

        $wrapperForElement->attributes = array($element, $element2);

        $xml = $this->marshaller->marshalToString($wrapperForElement);

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
        <wrapper-for-element>
            <foo>
                <element>
                    <attribute>blue</attribute>
                </element>
                <element>
                    <attribute>red</attribute>
                </element>
            </foo>
        </wrapper-for-element>', $xml);

        $otherWrapperForElement = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(2, count($otherWrapperForElement->attributes));
        $this->assertContains('blue', $otherWrapperForElement->attributes[0]->attribute);
        $this->assertContains('red', $otherWrapperForElement->attributes[1]->attribute);
    }

    /**
     * @test
     */
    public function collectionWrapsXmlMappedSuperclass()
    {
        $wrapperForSuperclass = new WrapperForSuperclass();
        $childA = new ChildA();
        $childA->aField = 'blue';

        $childB = new ChildB();
        $childB->bField = 'red';

        $wrapperForSuperclass->children = array($childA, $childB);

        $xml = $this->marshaller->marshalToString($wrapperForSuperclass);

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
        <wrapper-for-superclass>
            <foo>
                <child-a>
                    <a-field>blue</a-field>
                </child-a>
                <child-b>
                    <b-field>red</b-field>
                </child-b>
            </foo>
        </wrapper-for-superclass>', $xml);

        $otherWrapperForSuperclass = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(2, count($otherWrapperForSuperclass->children));
        $this->assertContains('blue', $otherWrapperForSuperclass->children[0]->aField);
        $this->assertContains('red', $otherWrapperForSuperclass->children[1]->bField);
    }
}
