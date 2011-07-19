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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\OXM;

use Doctrine\OXM\Marshaller\Marshaller;
use Doctrine\OXM\Storage\Storage;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\OXM\Proxy\ProxyFactory;

/**
 *
 */
class XmlEntityManager implements ObjectManager
{
    /**
     * The used Configuration.
     *
     * @var Configuration
     */
    private $config;

    /**
     * @var \Doctrine\OXM\Storage\Storage
     */
    private $storage;

    /**
     * @var Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var Marshaller\Marshaller
     */
    private $marshaller;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var Doctrine\OXM\UnitOfWork
     */
    private $unitOfWork;

    /**
     * Whether the XmlEntityManager is closed or not.
     */
    private $closed = false;


    /**
     * The XmlEntityRepository instances.
     *
     * @var array
     */
    private $repositories = array();
    
    /**
     * @var Proxy\ProxyFactory
     */
    private $proxyFactory;

    /**
     * Creates a new XmlEntityManager that uses the given Configuration and EventManager implementations.
     *
     * @param Storage $storage
     * @param Configuration $config
     * @param \Doctrine\Common\EventManager $eventManager
     */
    public function __construct(Storage $storage, Configuration $config, EventManager $eventManager = null)
    {
        $this->storage = $storage;
        $this->config = $config;

        if (null === $eventManager) {
            $eventManager = new EventManager;
        }
        $this->eventManager = $eventManager;

        $metadataFactoryClassName = $config->getClassMetadataFactoryName();
        $this->metadataFactory = new $metadataFactoryClassName($config, $this->eventManager);
        $this->metadataFactory->setCacheDriver($this->config->getMetadataCacheImpl());

        $marshallerClassName = $config->getMarshallerClassName();
        $this->marshaller = new $marshallerClassName($this->metadataFactory);

        $this->unitOfWork = new UnitOfWork($this);
        
        $this->proxyFactory = new ProxyFactory($this,
                $this->config->getProxyDir(),
                $this->config->getProxyNamespace(),
                $this->config->getAutoGenerateProxyClasses()
        );
    }
    
    
    /**
     * Gets the proxy factory used by the XmlEntityManager to create xml-entity proxies.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }


    /**
     * Marshals a mapped object into XML
     *
     * @param object $object
     * @return string
     */
    public function marshal($object)
    {
        return $this->marshaller->marshalToString($object);
    }

    /**
     * Unmarshals XML into mapped objects
     *
     * @param string $xml
     * @return object
     */
    public function unmarshal($xml)
    {
        return $this->marshaller->unmarshalFromString($xml);
    }

    /**
     * @return \Doctrine\OXM\Storage\XmlStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * @return Marshaller\Marshaller
     */
    public function getMarshaller()
    {
        return $this->marshaller;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return \Doctrine\OXM\Mapping\ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * Returns the OXM mapping descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)) or an aliased class name.
     *
     * Examples:
     * MyProject\Domain\User
     * sales:PriceRequest
     *
     * @return \Doctrine\OXM\Mapping\ClassMetadata
     * @internal Performance-sensitive method.
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }


    /**
     * Throws an exception if the EntityManager is closed or currently not active.
     *
     * @throws OXMException If the EntityManager is closed.
     */
    private function errorIfClosed()
    {
        if ($this->closed) {
            throw OXMException::entityManagerClosed();
        }
    }

    /**
     * Check if the Entity manager is open or closed.
     *
     * @return bool
     */
    public function isOpen()
    {
        return (!$this->closed);
    }

    /**
     * Gets the repository for a class.
     *
     * @param string $entityName
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($entityName)
    {
        $entityName = ltrim($entityName, '\\');
        if (isset($this->repositories[$entityName])) {
            return $this->repositories[$entityName];
        }

        $metadata = $this->getClassMetadata($entityName);
        $customRepositoryClassName = $metadata->customRepositoryClassName;

        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($this, $metadata);
        } else {
            $repository = new XmlEntityRepository($this, $metadata);
        }

        $this->repositories[$entityName] = $repository;

        return $repository;
    }


    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string $entityName
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->unitOfWork->clear();
        } else {
            //TODO
            throw new OXMException("EntityManager#clear(\$entityName) not yet implemented.");
        }
    }

    /**
     * Closes the EntityManager. All entities that are currently managed
     * by this EntityManager become detached. The EntityManager may no longer
     * be used after it is closed.
     */
    public function close()
    {
        $this->clear();
        $this->closed = true;
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the filesystem.
     * This effectively synchronizes the in-memory state of managed objects with the
     * filesystem.
     *
     * @throws Doctrine\OXM\OptimisticLockException If a version check on an entity that
     *         makes use of optimistic locking fails.
     */
    public function flush()
    {
        $this->errorIfClosed();
        $this->unitOfWork->commit();
    }

    /**
     * Refreshes the persistent state of an entity from the filesystem,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity The entity to refresh.
     */
    public function refresh($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->errorIfClosed();
        $this->unitOfWork->refresh($entity);
    }

    /**
     * Detaches an entity from the XmlEntityManager, causing a managed entity to
     * become detached.  Unflushed changes made to the entity if any
     * (including removal of the entity), will not be synchronized to the filesystem.
     * Entities which previously referenced the detached entity will continue to
     * reference it.
     *
     * @param object $entity The entity to detach.
     */
    public function detach($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->unitOfWork->detach($entity);
    }

    /**
     * Merges the state of a detached entity into the persistence context
     * of this XmlEntityManager and returns the managed copy of the entity.
     * The entity passed to merge will not become associated/managed with this XmlEntityManager.
     *
     * @param object $entity The detached entity to merge into the persistence context.
     * @return object The managed copy of the entity.
     */
    public function merge($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->errorIfClosed();
        return $this->unitOfWork->merge($entity);
    }

    /**
     * Removes an entity instance.
     *
     * A removed entity will be removed from the filesystem at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $entity The entity instance to remove.
     */
    public function remove($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->errorIfClosed();
        $this->unitOfWork->remove($entity);
    }

    /**
     * Tells the XmlEntityManager to make an instance managed and persistent.
     *
     * The entity will be entered into the filesystem at or before transaction
     * commit or as a result of the flush operation.
     *
     * NOTE: The persist operation always considers entities that are not yet known to
     * this XmlEntityManager as NEW. Do not pass detached entities to the persist operation.
     *
     * @param object $object The instance to make managed and persistent.
     */
    public function persist($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->errorIfClosed();
        $this->unitOfWork->persist($entity);
    }

    /**
     * Finds an Entity by its identifier.
     *
     * This is just a convenient shortcut for getRepository($entityName)->find($id).
     *
     * @param string $entityName
     * @param mixed $identifier
     * @param int $lockMode
     * @param int $lockVersion
     * @return object
     */
    public function find($entityName, $identifier, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->getRepository($entityName)->find($identifier, $lockMode, $lockVersion);
    }



    /**
     * Gets the UnitOfWork used by the EntityManager to coordinate operations.
     *
     * @return \Doctrine\OXM\UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }


}
