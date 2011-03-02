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
    \Doctrine\OXM\Marshaller\SimpleXmlMarshaller,
    \Doctrine\OXM\Mapping\Driver\AnnotationDriver,
    \Doctrine\Tests\OXM\Entities\User,
    \Doctrine\Tests\OXM\Entities\CustomerContact,
    \Doctrine\Tests\OXM\Entities\Address;

class MarshallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Marshaller\Marshaller
     */
    private $marshaller;

    public function setUp()
    {
        $config = new Configuration();
        $config->setClassMetadataDriverImpl(AnnotationDriver::create("tests/Doctrine/Tests/OXM/Entities"));
        $config->setClassMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

        $metadataFactory = new ClassMetadataFactory($config);

        $this->marshaller = new SimpleXmlMarshaller($metadataFactory);
    }


    public function testFirstClassMarshaller()
    {
        $user = new User();
        $user->setFirstNameNickname('Malcolm');
        $user->setLastName('Reynolds');
        $user->setAddress(new Address('123 Waverly Way', 'New Haven', 'Insanity'));
        $user->addContact(new CustomerContact('no@way.com'));
        $user->addContact(new CustomerContact('other@way.com'));

        $xml = $this->marshaller->marshal($user);

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
//        print_r($dom->saveXML());
    }
}
