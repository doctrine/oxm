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

use \Doctrine\OXM\Mapping\ClassMetadataFactory,
    \Doctrine\OXM\Configuration,
    \Doctrine\OXM\Marshaller\Marshaller,
    \Doctrine\OXM\Marshaller\XmlMarshaller,
    \Doctrine\OXM\Mapping\Driver\AnnotationDriver;

class MarshallerTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Marshaller\Marshaller
     */
    private $marshaller;

    public function setUp()
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver("tests/Doctrine/Tests/OXM/Marshaller"));
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

        $metadataFactory = new ClassMetadataFactory($config);

        $this->marshaller = new XmlMarshaller($metadataFactory);
    }

    /**
     * @test
     * @dataProvider typeMappingProvider
     */
    public function itShouldProduceExactXmlForAttributeOfType($object, $resultString)
    {
        $xml = $this->marshaller->marshalToString($object);

        $this->assertTrue(strlen($xml) > 0);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>' . $resultString, $xml);

        $otherObject = $this->marshaller->unmarshalFromString($xml);
        $this->assertEquals($object->i, $otherObject->i);
        $this->assertEquals($object, $otherObject);
    }

    public function typeMappingProvider()
    {
        return array(
            array(new ArrayType(array(1=>1)), '<array-type i="a:1:{i:1;i:1;}"/>'),
            array(new BooleanType(true), '<boolean-type i="true"/>'),
            array(new BooleanType(false), '<boolean-type i="false"/>'),
            array(new DateTimeType(new \DateTime('1911-02-06 12:00:00')), '<date-time-type i="1911-02-06 12:00:00"/>'),
            array(new DateTimeTzType(new \DateTime('1911-02-06 12:00:00+0200')), '<date-time-tz-type i="1911-02-06 12:00:00+0200"/>'),
            array(new DateType(new \DateTime('1911-02-06')), '<date-type i="1911-02-06"/>'),
            array(new TimeType(new \DateTime('12:34:56')), '<time-type i="12:34:56"/>'),
            array(new FloatType(3.1415), '<float-type i="3.1415"/>'),
            array(new IntegerType(1), '<integer-type i="1"/>'),
            array(new StringType('Demon\'s Barber'), '<string-type i="Demon\'s Barber"/>'),
//            array(new ObjectType($obj), '<object-type i="Demon Barber"/>'),
        );
    }
}
/** @XmlEntity */
class ArrayType {
    /** @XmlAttribute(type="array", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}

/** @XmlEntity */
class BooleanType {
    /** @XmlAttribute(type="boolean", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class DateTimeType {
    /** @XmlAttribute(type="datetime", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class DateTimeTzType {
    /** @XmlAttribute(type="datetimetz", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class DateType {
    /** @XmlAttribute(type="date", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class TimeType {
    /** @XmlAttribute(type="time", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class FloatType {
    /** @XmlAttribute(type="float", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class IntegerType {
    /** @XmlAttribute(type="integer", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class StringType {
    /** @XmlAttribute(type="string", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
/** @XmlEntity */
class ObjectType {
    /** @XmlAttribute(type="object", direct=true) */
    public $i;
    public function __construct($i) { $this->i = $i; }
}
