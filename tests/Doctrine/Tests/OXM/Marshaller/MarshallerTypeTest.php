<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/1/11
 * Time: 7:43 PM
 * To change this template use File | Settings | File Templates.
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
        $config->setMetadataDriverImpl(AnnotationDriver::create("tests/Doctrine/Tests/OXM/Marshaller"));
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
        $xml = $this->marshaller->marshal($object);

        $this->assertTrue(strlen($xml) > 0);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>' . $resultString, $xml);

        $otherObject = $this->marshaller->unmarshal($xml);
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