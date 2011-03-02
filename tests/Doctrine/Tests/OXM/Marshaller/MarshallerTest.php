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
    \Doctrine\Tests\OXM\Entities\Order,
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
        $config->setMetadataDriverImpl(AnnotationDriver::create("tests/Doctrine/Tests/OXM/Entities"));
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

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

        $otherUser = $this->marshaller->unmarshal($xml);


//        print_r($otherUser);

        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\User', $otherUser);

        $this->assertEquals('Malcolm', $otherUser->getFirstNameNickname());
        $this->assertEquals('Reynolds', $otherUser->getLastName());

        $this->assertEquals('123 Waverly Way', $otherUser->getAddress()->getStreet());
        $this->assertEquals('New Haven', $otherUser->getAddress()->getCity());
        $this->assertEquals('Insanity', $otherUser->getAddress()->getState());

        $this->assertEquals(2, count($otherUser->getContacts()));
    }

    public function testItShouldAutocompleteFields()
    {
        $order = new Order(1, 'business cards', new \DateTime());

        $xml = $this->marshaller->marshal($order);

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
//        print_r($dom->saveXML());

        $this->assertTrue(strlen($xml) > 0);

        $otherOrder = $this->marshaller->unmarshal($xml);
//        print_r($otherOrder);

        $this->assertEquals(1, $otherOrder->getId());
        $this->assertEquals('business cards', $otherOrder->getProductType());
    }
}
