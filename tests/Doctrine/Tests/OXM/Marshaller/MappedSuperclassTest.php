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

use \Doctrine\Tests\OxmTestCase,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\Request,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO1,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO2,
    \Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO3;

class MappedSuperclassTest extends OxmTestCase
{
    /** @var \Doctrine\OXM\Marshaller\XmlMarshaller */
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
        $bo->overridden = 'yes';
        $request->bo = $bo;

        $xml = $this->marshaller->marshalToString($request);

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <request id="1">
                <concrete-bo1 inherit="1">
                    <overridden>yes</overridden>
                </concrete-bo1>
            </request>', $xml);

        $otherRequest = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(1, $otherRequest->id);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO1', $otherRequest->bo);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\AbstractBusinessObject', $otherRequest->bo);
        $this->assertEquals(1, $otherRequest->bo->inherit);
    }

    /**
     * @test
     */
    public function itShouldDoDeepInheritedFieldsCorrectly()
    {
        $request = new Request();
        $request->id = 1;
        $bo = new ConcreteBO3();
        $bo->inherit = 1;
        $bo->overridden = 'yes';
        $bo->description = 'Scooby Doo';
        $request->bo = $bo;

        $xml = $this->marshaller->marshalToString($request);

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
            <request id="1">
                <concrete-bo3 inherit="1" description="Scooby Doo">
                    <overridden>yes</overridden>
                </concrete-bo3>
            </request>', $xml);

        $otherRequest = $this->marshaller->unmarshalFromString($xml);

        $this->assertEquals(1, $otherRequest->id);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\ConcreteBO3', $otherRequest->bo);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\BusinessObject', $otherRequest->bo);
        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\MappedSuperclass\AbstractBusinessObject', $otherRequest->bo);
        $this->assertEquals(1, $otherRequest->bo->inherit);
        $this->assertEquals('yes', $otherRequest->bo->overridden);
        $this->assertEquals('Scooby Doo', $otherRequest->bo->description);
    }
}
