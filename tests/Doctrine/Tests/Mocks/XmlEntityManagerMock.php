<?php
/*
 *  $Id$
 *
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

namespace Doctrine\Tests\Mocks;

/**
 * Special XmlEntityManager mock used for testing purposes.
 */
class XmlEntityManagerMock extends \Doctrine\OXM\XmlEntityManager
{
    private $_uowMock;
    private $_proxyFactoryMock;
    private $_idGenerators = array();

    /**
     * @override
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }
    
    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param <type> $uow
     */
    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }

    /**
     * @static
     * @param \Doctrine\OXM\Storage\Storage|null $storage
     * @param \Doctrine\OXM\Configuration|null $config
     * @param \Doctrine\Common\EventManager|null $eventManager
     * @return XmlEntityManagerMock
     */
    public static function create(\Doctrine\OXM\Storage\Storage $storage = null,
            \Doctrine\OXM\Configuration $config = null,
            \Doctrine\Common\EventManager $eventManager = null)
    {
        if (is_null($storage)) {
            $storage = new \Doctrine\OXM\Storage\FileSystemStorage(__DIR__ . '/../Workspace');
        }
        if (is_null($config)) {
            $config = new \Doctrine\OXM\Configuration();
            $config->setMetadataDriverImpl(\Doctrine\OXM\Mapping\Driver\AnnotationDriver::create(__DIR__ . '/../OXM/Entities'));
        }
        if (is_null($eventManager)) {
            $eventManager = new \Doctrine\Common\EventManager();
        }
        
        return new XmlEntityManagerMock($storage, $config, $eventManager);
    }
/*
    public function setIdGenerator($className, $generator)
    {
        $this->_idGenerators[$className] = $generator;
    }
*/
    /** @override */
/*    public function getIdGenerator($className)
    {

        if (isset($this->_idGenerators[$className])) {
            return $this->_idGenerators[$className];
        }
                
        return parent::getIdGenerator($className);
    }
 */
}
