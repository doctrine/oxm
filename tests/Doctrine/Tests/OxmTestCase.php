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

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(__DIR__ .'/OXM/Entities')));

        $storage = new \Doctrine\OXM\Storage\FileSystemStorage(__DIR__ .'/Workspace');
        
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
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

    /**
     * @param  $paths
     * @return \Doctrine\OXM\Marshaller\Marshaller
     */
    protected function _getMarshaller($paths)
    {
        $config = new \Doctrine\OXM\Configuration();

        if (empty($paths)) {
            $paths = "tests/Doctrine/Tests/OXM/Entities";
        }

        if (!is_array($paths)) {
            $paths = array($paths);
        }

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths));
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

        $metadataFactory = new \Doctrine\OXM\Mapping\ClassMetadataFactory($config);

        return new \Doctrine\OXM\Marshaller\XmlMarshaller($metadataFactory);
    }
}
