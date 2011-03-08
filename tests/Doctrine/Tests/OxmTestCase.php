<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/1/11
 * Time: 10:02 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests;

class OxmTestCase extends \PHPUnit_Framework_TestCase
{
    /** The metadata cache that is shared between all ORM tests (except functional tests). */
    private static $_metadataCacheImpl = null;

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @return \Doctrine\OXM\XmlEntityManager
     */
    protected function _getTestXmlEntityManager($eventManager = null, $withSharedMetadata = true)
    {
        $config = new \Doctrine\OXM\Configuration();
        if ($withSharedMetadata) {
            $config->setMetadataCacheImpl(self::getSharedMetadataCacheImpl());
        } else {
            $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        }

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__ .'/OXM/Entities'));

        $storage = new \Doctrine\OXM\Storage\FileSystemStorage(__DIR__ .'/Workspace');
        
//        $config->setProxyDir(__DIR__ . '/Proxies');
//        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new \Doctrine\Common\EventManager();

        return \Doctrine\Tests\Mocks\XmlEntityManagerMock::create($storage, $config, $eventManager);
    }

    private static function getSharedMetadataCacheImpl()
    {
        if (self::$_metadataCacheImpl === null) {
            self::$_metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }
        return self::$_metadataCacheImpl;
    }

    /**
     * @param string $entityName
     * @return \Doctrine\OXM\Mapping\ClassMetadataInfo
     */
    protected function _getClassMetadataMock($entityName)
    {
        return new \Doctrine\Tests\Mocks\ClassMetadataInfoMock($entityName);
    }
}