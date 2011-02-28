<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/22/11
 * Time: 3:53 PM
 * To change this template use File | Settings | File Templates.
 */

use \Doctrine\OXM\Mapping\Mapping,
    \Doctrine\Common\Util\Debug,
    \Doctrine\OXM\XmlEntityManager,
    \Doctrine\OXM\Mapping\Driver\AnnotationDriver,
    \Doctrine\OXM\Marshaller\SimpleXmlMarshaller,
    \Doctrine\OXM\Configuration,
    \Doctrine\Common\EventManager,
    \Doctrine\Tests\OXM\Entities\User,
    \Doctrine\Tests\OXM\Entities\CustomerContact,
    \Doctrine\Tests\OXM\Entities\Address,
    \Doctrine\Tests\OXM\Entities\Autocomplete\Order;

class XmlEntityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\XmlEntityManager
     */
    private $xm;

    /**
     * @var \Doctrine\OXM\Configuration
     */
    private $config;

    public function setup()
    {
        $this->config = new Configuration();
        $this->config->setMappingDriverImpl(AnnotationDriver::create("tests/Doctrine/Tests/OXM/Entities"));
        $this->config->setMappingCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

        $this->xm = new XmlEntityManager(new SimpleXmlMarshaller(), $this->config, new EventManager());
    }

    public function testMarshaller()
    {
        $user = new User();
        $user->setFirstNameNickname('Malcolm');
        $user->setLastName('Reynolds');
        $user->setAddress(new Address('123 Waverly Way', 'New Haven', 'Insanity'));
        $user->addContact(new CustomerContact('no@way.com'));
        $user->addContact(new CustomerContact('other@way.com'));
        
        $xml = $this->xm->marshal($user);

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
//        print_r($dom->saveXML());

        $this->assertTrue(strlen($xml) > 0);

        $otherUser = $this->xm->unmarshal($xml);

        $this->assertInstanceOf('Doctrine\Tests\OXM\Entities\User', $otherUser);

        $this->assertEquals('Malcolm', $otherUser->getFirstNameNickname());
        $this->assertEquals('Reynolds', $otherUser->getLastName());

        $this->assertEquals('123 Waverly Way', $otherUser->getAddress()->getStreet());
        $this->assertEquals('New Haven', $otherUser->getAddress()->getCity());
        $this->assertEquals('Insanity', $otherUser->getAddress()->getState());

        $this->assertEquals(2, count($otherUser->getContacts()));

//        print_r($otherUser);

//        print_r(1);
//        print_r($this->config->getMappingCacheImpl());
//        print_r(1);
    }

    public function testItShouldAutocompleteFields()
    {
        $order = new Order(1, 'business cards', new DateTime());

        $xml = $this->xm->marshal($order);

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
//        print_r($dom->saveXML());

        $this->assertTrue(strlen($xml) > 0);


        $otherOrder = $this->xm->unmarshal($xml);
//        print_r($otherOrder);

        $this->assertEquals(1, $otherOrder->getId());
        $this->assertEquals('business cards', $otherOrder->getProductType());

//        print_r(1);
//        print_r($this->config->getMappingCacheImpl());
//        print_r(1);
    }
}