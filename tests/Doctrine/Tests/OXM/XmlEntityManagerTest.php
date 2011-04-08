<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/22/11
 * Time: 3:53 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM;

use \Doctrine\OXM\Mapping\ClassMetadataInfo,
    \Doctrine\Tests\OxmTestCase,
    \Doctrine\Common\Util\Debug,
    \Doctrine\OXM\XmlEntityManager,
    \Doctrine\OXM\Mapping\Driver\AnnotationDriver,
    \Doctrine\OXM\Configuration,
    \Doctrine\Common\EventManager,
    \Doctrine\Tests\OXM\Entities\Order,
    \Doctrine\Tests\OXM\Entities\SimpleWithField;

class XmlEntityManagerTest extends OxmTestCase
{
    /**
     * @var \Doctrine\OXM\XmlEntityManager
     */
    private $xem;

    /**
     * @var \Doctrine\OXM\Configuration
     */
    private $config;

    public function setup()
    {
        $this->xem = $this->_getTestXmlEntityManager();
    }

    public function testPersisting()
    {
        $order = new Order(1, 'business cards', new \DateTime());

        $this->xem->persist($order);
        $this->xem->flush();

        $expectedFileName = __DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml';

        $this->assertTrue(is_file($expectedFileName));

        unlink($expectedFileName);
    }

    public function testNoFlushPersisting()
    {
        $order = new Order(1, 'business cards', new \DateTime());

        $this->xem->persist($order);

        $expectedFileName = __DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml';

        $this->assertTrue(!is_file($expectedFileName));
    }

    public function testPersistingAndDelete()
    {
        $order = new Order(1, 'business cards', new \DateTime());

        $this->xem->persist($order);
        $this->xem->flush();

        $expectedFileName = __DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml';

        $this->assertTrue(is_file($expectedFileName));

        $this->xem->remove($order);
        $this->xem->flush();
        
        $this->assertTrue(!is_file($expectedFileName));        
    }

    public function testPersistAndLoad()
    {
        $order = new Order(1, 'business cards', new \DateTime());

        $this->xem->persist($order);
        $this->xem->flush();

        $expectedFileName = __DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml';
        $this->assertTrue(is_file($expectedFileName));

        $otherOrder = $this->xem->getRepository('Doctrine\Tests\OXM\Entities\Order')->find(1);

        $this->assertEquals('business cards', $otherOrder->getProductType());

        unlink(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml');
    }

    public function testPersistUpdateAndLoad()
    {
        $order = new Order(1, 'business cards', new \DateTime());

        $this->xem->persist($order);

        $order->setProductType('postcards');
        $this->xem->persist($order);
        $this->xem->flush();

        $expectedFileName = __DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml';
        $this->assertTrue(is_file($expectedFileName));

        $otherOrder = $this->xem->getRepository('Doctrine\Tests\OXM\Entities\Order')->find(1);

        $this->assertEquals('postcards', $otherOrder->getProductType());

        unlink(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/1.xml');
    }

    public function testPersistMultipleObjects()
    {
        $order = new Order(3, 'business cards', new \DateTime());
        $order2 = new Order(4, 'post cards', new \DateTime());

        $this->xem->persist($order);
        $this->xem->persist($order2);
        $this->xem->flush();

        $this->assertTrue(is_file(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/3.xml'));
        $this->assertTrue(is_file(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/4.xml'));

        unlink(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/3.xml');
        unlink(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order/4.xml');
    }

    public function testObjectFlushWithMultiple()
    {
        for ($i = 1; $i <= 10; $i++) {
            $simple = new SimpleWithField();
            $simple->id = $i;
            
            $this->xem->persist($simple);

        }

        $this->xem->flush();

        for ($i = 1; $i <= 10; $i++) {
            $filepath = __DIR__ . "/../Workspace/Doctrine/Tests/OXM/Entities/SimpleWithField/$i.xml";
            $this->assertFileExists($filepath);
            $this->assertXmlStringEqualsXmlFile($filepath, '<?xml version="1.0" encoding="UTF-8"?><simple-with-field id="' . $i . '"/>');
            unlink($filepath);
        }
    }

    public function testObjectFlushPerPersist()
    {
        for ($i = 1; $i <= 10; $i++) {
            $simple = new SimpleWithField();
            $simple->id = $i;

            $this->xem->persist($simple);

            $this->xem->flush();

            $filepath = __DIR__ . "/../Workspace/Doctrine/Tests/OXM/Entities/SimpleWithField/$i.xml";
            $this->assertFileExists($filepath);
            $this->assertXmlStringEqualsXmlFile($filepath, '<?xml version="1.0" encoding="UTF-8"?><simple-with-field id="' . $i . '"/>');
            unlink($filepath);
        }
    }

    public function tearDown()
    {
        @rmdir(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities/Order');
        @rmdir(__DIR__ . '/../Workspace/Doctrine/Tests/OXM/Entities');
        @rmdir(__DIR__ . '/../Workspace/Doctrine/Tests/OXM');
        @rmdir(__DIR__ . '/../Workspace/Doctrine/Tests');
        @rmdir(__DIR__ . '/../Workspace/Doctrine');
    }
}