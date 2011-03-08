<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/28/11
 * Time: 9:35 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\OXM;

use \Doctrine\Common\Persistence\ObjectRepository;

class XmlEntityRepository implements ObjectRepository
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var XmlEntityManager
     */
    protected $xem;

    /**
     * @var \Doctrine\OXM\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * Initializes a new <tt>XmlEntityRepository</tt>.
     *
     * @param XmlEntityManager $em The EntityManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct(XmlEntityManager $xem, Mapping\ClassMetadata $class)
    {
        $this->entityName = $class->name;
        $this->xem = $xem;
        $this->class = $class;
    }


    /**
     * Clears the repository, causing all managed entities to become detached.
     */
    public function clear()
    {
        $this->xem->clear($this->class->rootXmlEntityName);
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria
     * @return object The object.
     */
    public function findOneBy(array $criteria)
    {
        throw new \Exception("XmlEntityRepository::findBy() is not yet implemented");
    }

    /**
     * Finds objects by a set of criteria.
     *
     * @param array $criteria
     * @return mixed The objects.
     */
    public function findBy(array $criteria)
    {
        throw new \Exception("XmlEntityRepository::findBy() is not yet implemented");
    }

    /**
     * Finds all objects in the repository.
     *
     * @return mixed The objects.
     */
    public function findAll()
    {
        // TODO: Implement findAll() method.
    }

    /**
     * Finds an entity by its identifier.
     *
     * @param $id The identifier.
     * @param int $lockMode
     * @param int $lockVersion
     * @return object The entity.
     */
    public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        // Check identity map first
        if ($entity = $this->xem->getUnitOfWork()->tryGetById($id, $this->class->rootXmlEntityName)) {
            if ($lockMode != LockMode::NONE) {
                $this->xem->lock($entity, $lockMode, $lockVersion);
            }

            return $entity; // Hit!
        }

        if ($lockMode == LockMode::NONE) {
            return $this->xem->getUnitOfWork()->getXmlEntityPersister($this->entityName)->load($id);
        } else if ($lockMode == LockMode::OPTIMISTIC) {
            if (!$this->class->isVersioned) {
                throw OptimisticLockException::notVersioned($this->entityName);
            }
            $entity = $this->xem->getUnitOfWork()->getEntityPersister($this->entityName)->load($id);

            $this->xem->getUnitOfWork()->lock($entity, $lockMode, $lockVersion);

            return $entity;
        } else {
            if (!$this->xem->getConnection()->isTransactionActive()) {
                throw TransactionRequiredException::transactionRequired();
            }

            return $this->xem->getUnitOfWork()->getXmlEntityPersister($this->entityName)->load($id, null, null, array(), $lockMode);
        }
    }

}
