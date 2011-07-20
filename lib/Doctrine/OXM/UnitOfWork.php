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

use Exception, InvalidArgumentException, UnexpectedValueException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\OXM\Mapping\ClassMetadata;
use Doctrine\OXM\Event;
use Doctrine\OXM\Proxy\Proxy;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the filesystem
 *
 * This UnitOfWork is only capable of persisting xml class which are mapped via
 * the @XmlRootEntity element.  This ensures that @XmlId fields exist, and the persister
 * knows how to save the xml correctly.
 *
 * @since       2.0
 * @author      Richard Fullmer <richard.fullmer@opensoftdev.com>
 * @internal    This class contains highly performance-sensitive code.
 */
class UnitOfWork implements PropertyChangedListener
{
    /**
     * An entity is in MANAGED state when its persistence is managed by an XmlEntityManager.
     */
    const STATE_MANAGED = 1;

    /**
     * An entity is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by an XmlEntityManager.
     */
    const STATE_NEW = 2;

    /**
     * A detached entity is an instance with persistent state and identity that is not
     * (or no longer) associated with an XmlEntityManager (and a UnitOfWork).
     */
    const STATE_DETACHED = 3;

    /**
     * A removed entity instance is an instance with a persistent identity,
     * associated with an XmlEntityManager, whose persistent state will be deleted
     * on commit.
     */
    const STATE_REMOVED = 4;

    /**
     * The identity map that holds references to all managed entities that have
     * an identity. The entities are grouped by their class name.
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @var array
     */
    private $identityMap = array();

    /**
     * Map of all identifiers of managed entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $entityIdentifiers = array();


    /**
     * Map of the original entity data of managed entities.
     * Keys are object ids (spl_object_hash). This is used for calculating changesets
     * at commit time.
     *
     * @var array
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *           A value will only really be copied if the value in the entity is modified
     *           by the user.
     */
    private $originalEntityData = array();

    /**
     * Map of entity changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $entityChangeSets = array();

    /**
     * The (cached) states of any known entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $entityStates = array();

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used for entities with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     * @todo rename: scheduledForSynchronization
     */
    private $scheduledForDirtyCheck = array();

    /**
     * A list of all pending entity insertions.
     *
     * @var array
     */
    private $entityInsertions = array();

    /**
     * A list of all pending entity updates.
     *
     * @var array
     */
    private $entityUpdates = array();

    /**
     * A list of all pending entity deletions.
     *
     * @var array
     */
    private $entityDeletions = array();
    
    /**
     * The XmlEntityManager that "owns" this UnitOfWork instance.
     *
     * @var Doctrine\OXM\XmlEntityManager
     */
    private $xem;

    /**
     * The EventManager used for dispatching events.
     *
     * @var EventManager
     */
    private $evm;

    /**
     * The entity persister instances used to persist entity instances.
     *
     * @var array
     */
    private $persisters = array();



    /**
     * Initializes a new UnitOfWork instance, bound to the given EntityManager.
     *
     * @param Doctrine\OXM\XmlEntityManager $em
     */
    public function __construct(XmlEntityManager $xem)
    {
        $this->xem = $xem;
        $this->evm = $xem->getEventManager();
    }
    
    /**
     * Gets the EntityPersister for an Entity.
     *
     * @param string $entityName  The name of the Entity.
     * @return \Doctrine\OXM\Persisters\RootXmlEntityPersister
     */
    public function getXmlEntityPersister($entityName)
    {
        if ( ! isset($this->persisters[$entityName])) {
            $class = $this->xem->getClassMetadata($entityName);
            $this->persisters[$entityName] = new Persisters\RootXmlEntityPersister($this->xem, $class);
        }
        return $this->persisters[$entityName];
    }

   /**
     * Clears the UnitOfWork.
     */
    public function clear()
    {
        $this->identityMap =
        $this->entityIdentifiers =
        $this->entityChangeSets =
        $this->entityStates =
        $this->scheduledForDirtyCheck =
        $this->entityInsertions =
        $this->entityUpdates =
        $this->entityDeletions = array();
    }

    /**
     * @param array $options
     * @return
     */
    public function commit(array $options = array())
    {
        if ( ! ($this->entityInsertions ||
                $this->entityDeletions ||
                $this->entityUpdates)) {
            return; // Nothing to do.
        }

        // Raise onFlush
        if ($this->evm->hasListeners(Events::onFlush)) {
            $this->evm->dispatchEvent(Events::onFlush, new Event\OnFlushEventArgs($this->xem));
        }

        // Execute insertions, updates, and deletes
        if ($this->entityInsertions) {
            foreach ($this->entityInsertions as $class) {
                $classMetadata = $this->xem->getClassMetadata(get_class($class));
                $this->executeInserts($classMetadata, $options);
            }
        }

        if ($this->entityUpdates) {
            foreach ($this->entityUpdates as $class) {
                $classMetadata = $this->xem->getClassMetadata(get_class($class));
                $this->executeUpdates($classMetadata, $options);
            }
        }

        if ($this->entityDeletions) {
            foreach ($this->entityDeletions as $class) {
                $classMetadata = $this->xem->getClassMetadata(get_class($class));
                $this->executeDeletions($classMetadata, $options);
            }
        }

        // Clear up
        $this->entityInsertions =
        $this->entityUpdates =
        $this->entityDeletions =
        $this->entityChangeSets =
        $this->scheduledForDirtyCheck = array();
    }

    /**
     * Executes all document insertions for documents of the specified type.
     *
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with batchInsert()
     */
    private function executeInserts($class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getXmlEntityPersister($className);

        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postPersist]);
        $hasListeners = $this->evm->hasListeners(Events::postPersist);
        if ($hasLifecycleCallbacks || $hasListeners) {
            $documents = array();
        }

        $inserts = array();
        foreach ($this->entityInsertions as $oid => $xmlEntity) {
            if (get_class($xmlEntity) === $className) {
                $persister->insert($xmlEntity);
                unset($this->entityInsertions[$oid]);
            }
        }
    }

    /**
     * Executes all document deletions for documents of the specified type.
     *
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with remove()
     */
    private function executeDeletions(ClassMetadata $class, array $options = array())
    {
        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postRemove]);
        $hasListeners = $this->evm->hasListeners(Events::postRemove);

        $className = $class->name;
        $persister = $this->getXmlEntityPersister($className);


        foreach ($this->entityDeletions as $oid => $xmlEntity) {
            if (get_class($xmlEntity) == $className || $xmlEntity instanceof Proxy && $xmlEntity instanceof $className) {
//                if ( ! $class->isEmbeddedDocument) {
                $persister->delete($xmlEntity, $options);
//                }
                unset(
                    $this->entityDeletions[$oid],
                    $this->entityIdentifiers[$oid],
                    $this->originalEntityData[$oid]
                );

//                // Clear snapshot information for any referenced PersistentCollection
//                // http://www.doctrine-project.org/jira/browse/MODM-95
//                foreach ($class->fieldMappings as $fieldMapping) {
//                    if (isset($fieldMapping['type']) && $fieldMapping['type'] === 'many') {
//                        $value = $class->reflFields[$fieldMapping['fieldName']]->getValue($document);
//                        if ($value instanceof PersistentCollection) {
//                            $value->clearSnapshot();
//                        }
//                    }
//                }

                // Xml Entity with this $oid after deletion treated as NEW, even if the $oid
                // is obtained by a new document because the old one went out of scope.
                $this->entityStates[$oid] = self::STATE_NEW;

                if ($hasLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postRemove, $xmlEntity);
                }
                if ($hasListeners) {
                    $this->evm->dispatchEvent(Events::postRemove, new Event\LifecycleEventArgs($xmlEntity, $this->xem));
                }
//                $this->cascadePostRemove($class, $xmlEntity);
            }
        }
    }

    /**
     * Executes all xml entity updates for documents of the specified type.
     *
     * @param Doctrine\OXM\Mapping\ClassMetadata $class
     * @param array $options Array of options to be used with update()
     */
    private function executeUpdates(ClassMetadata $class, array $options = array())
    {
        $className = $class->name;
        $persister = $this->getXmlEntityPersister($className);

        $hasPreUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::preUpdate]);
        $hasPreUpdateListeners = $this->evm->hasListeners(Events::preUpdate);
        $hasPostUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postUpdate]);
        $hasPostUpdateListeners = $this->evm->hasListeners(Events::postUpdate);

        foreach ($this->entityUpdates as $oid => $xmlEntity) {
            if (get_class($xmlEntity) == $className || $xmlEntity  instanceof Proxy && $xmlEntity instanceof $className) {
//                if ( ! $class->isEmbeddedDocument) {
                if ($hasPreUpdateLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::preUpdate, $xmlEntity);
    //                        $this->recomputeSingleDocumentChangeSet($class, $xmlEntity);
                }

                if ($hasPreUpdateListeners) {
                    $this->evm->dispatchEvent(Events::preUpdate, new Event\PreUpdateEventArgs(
                        $xmlEntity, $this->xem, $this->entityChangeSets[$oid])
                    );
                }

                $persister->update($xmlEntity);
                unset($this->entityUpdates[$oid]);

                if ($hasPostUpdateLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postUpdate, $xmlEntity);
                }
                if ($hasPostUpdateListeners) {
                    $this->evm->dispatchEvent(Events::postUpdate, new Event\LifecycleEventArgs($xmlEntity, $this->xem));
                }
//                    $this->cascadePostUpdateAndPostPersist($class, $xmlEntity);
            }
        }
    }

    public function refresh($xmlEntity)
    {
        $visited = array();
        $this->doRefresh($xmlEntity, $visited);
    }
    
    /**
     * Executes a refresh operation on an xml-entity.
     *
     * @param object $xmlEntity The xml-entity to refresh.
     * @param array $visited The already visited xml-entities during cascades.
     * @throws InvalidArgumentException If the xml-entity is not MANAGED.
     */
    private function doRefresh($xmlEntity, array &$visited)
    {
        $oid = spl_object_hash($xmlEntity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $xmlEntity; // mark visited

        $class = $this->xem->getClassMetadata(get_class($xmlEntity));
        if ($this->getXmlEntityState($xmlEntity) == self::STATE_MANAGED) {
            // @todo refresh xml-entity
        } else {
            throw new \InvalidArgumentException("XmlEntity is not MANAGED.");
        }
    }

    /**
     * Detaches an xml-entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param object $xmlEntity The xml-entity to detach.
     */
    public function detach($xmlEntity)
    {
        $visited = array();
        $this->doDetach($xmlEntity, $visited);
    }
    
    /**
     * Executes a detach operation on the given xml-entity.
     *
     * @param object $xmlEntity
     * @param array $visited
     * @internal This method always considers xml-entities with an assigned identifier as DETACHED.
     */
    private function doDetach($xmlEntity, array &$visited)
    {
        $oid = spl_object_hash($xmlEntity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $xmlEntity; // mark visited

        switch ($this->getXmlEntityState($xmlEntity, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                $this->removeFromIdentityMap($xmlEntity);
                unset($this->entityIdentifiers[$oid], $this->entityUpdates[$oid],
                        $this->entityDeletions[$oid], $this->entityIdentifiers[$oid],
                        $this->entityStates[$oid], $this->originalEntityData[$oid]);
                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }

        //$this->cascadeDetach($document, $visited);
    }

    /**
     * Merges the state of the given detached xml-entity into this UnitOfWork.
     *
     * @param object $xmlEntity
     * @return object The managed copy of the xml-entity.
     */
    public function merge($xmlEntity)
    {
        
    }
    


    public function remove($xmlEntity)
    {
        $visited = array();
        $this->doRemove($xmlEntity, $visited);
    }

    private function doRemove($xmlEntity, array &$visited)
    {
        $oid = spl_object_hash($xmlEntity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $xmlEntity; // mark visited

        $class = $this->xem->getClassMetadata(get_class($xmlEntity));
        $entityState = $this->getXmlEntityState($xmlEntity);
        switch ($entityState) {
            case self::STATE_NEW:
            case self::STATE_REMOVED:
                // nothing to do
                break;
            case self::STATE_MANAGED:
                if (isset($class->lifecycleCallbacks[Events::preRemove])) {
                    $class->invokeLifecycleCallbacks(Events::preRemove, $xmlEntity);
                }
                if ($this->evm->hasListeners(Events::preRemove)) {
                    $this->evm->dispatchEvent(Events::preRemove, new Event\LifecycleEventArgs($xmlEntity, $this->xem));
                }
                $this->scheduleForDelete($xmlEntity);
                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException("A detached xml entity can not be removed.");
            default:
                throw new UnexpectedValueException("Unexpected xml entity state: $entityState.");
        }
    }

    /**
     * INTERNAL:
     * Schedules an entity for deletion.
     *
     * @param object $xmlEntity
     */
    public function scheduleForDelete($xmlEntity)
    {
        $oid = spl_object_hash($xmlEntity);

        if (isset($this->entityInsertions[$oid])) {
            if ($this->isInIdentityMap($xmlEntity)) {
                $this->removeFromIdentityMap($xmlEntity);
            }
            unset($this->entityInsertions[$oid]);
            return; // entity has not been persisted yet, so nothing more to do.
        }

        if ( ! $this->isInIdentityMap($xmlEntity)) {
            return; // ignore
        }

        $this->removeFromIdentityMap($xmlEntity);

        if (isset($this->entityUpdates[$oid])) {
            unset($this->entityUpdates[$oid]);
        }
        if ( ! isset($this->entityDeletions[$oid])) {
            $this->entityDeletions[$oid] = $xmlEntity;
        }
    }

    /**
     * Checks whether an entity is registered in the identity map of this UnitOfWork.
     *
     * @param object $entity
     * @return boolean
     */
    public function isInIdentityMap($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->entityIdentifiers[$oid])) {
            return false;
        }
        $classMetadata = $this->xem->getClassMetadata(get_class($entity));
        $id = $this->entityIdentifiers[$oid];
        if ($id === '') {
            return false;
        }

        return isset($this->identityMap[$classMetadata->rootXmlEntityName][$id]);
    }

    /**
     * INTERNAL:
     * Removes an entity from the identity map. This effectively detaches the
     * entity from the persistence management of Doctrine.
     *
     * @ignore
     * @param object $entity
     * @return boolean
     */
    public function removeFromIdentityMap($entity)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->xem->getClassMetadata(get_class($entity));
        $id = $this->entityIdentifiers[$oid];
        if ($id === '') {
            throw new InvalidArgumentException("The given entity has no identity.");
        }
        $className = $classMetadata->rootXmlEntityName;
        if (isset($this->identityMap[$className][$id])) {
            unset($this->identityMap[$className][$id]);
            //$this->entityStates[$oid] = self::STATE_DETACHED;
            return true;
        }

        return false;
    }
    
    /**
     * Persists an xml entity as part of the current unit of work.
     *
     * @param object $xmlEntity The xml entity to persist.
     */
    public function persist($xmlEntity)
    {
        $class = $this->xem->getClassMetadata(get_class($xmlEntity));

        if ($class->isMappedSuperclass) {
            throw OXMException::cannotPersistMappedSuperclass($class->name);
        }
        if (!$class->isRoot) {
            throw OXMException::canOnlyPersistRootClasses($class->name);
        }
        $visited = array();
        $this->doPersist($xmlEntity, $visited);
    }


    /**
     * Saves an document as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited documents to prevent infinite recursions.
     *
     * NOTE: This method always considers documents that are not yet known to
     * this UnitOfWork as NEW.
     *
     * @param object $xmlEntity The document to persist.
     * @param array $visited The already visited documents.
     */
    private function doPersist($xmlEntity, array &$visited)
    {
        $oid = spl_object_hash($xmlEntity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $xmlEntity; // Mark visited

        $class = $this->xem->getClassMetadata(get_class($xmlEntity));

        // We assume NEW, so DETACHED entities result in an exception on flush (constraint violation).
        // If we would detect DETACHED here we would throw an exception anyway with the same
        // consequences (not recoverable/programming error), so just assuming NEW here
        // lets us avoid some database lookups for entities with natural identifiers.
        $xmlEntityState = $this->getXmlEntityState($xmlEntity, self::STATE_NEW);

        switch ($xmlEntityState) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($xmlEntity);
                }
                break;
            case self::STATE_NEW:
                $this->persistNew($class, $xmlEntity);
                break;
            case self::STATE_REMOVED:
                // Entity becomes managed again
                unset($this->entityDeletions[$oid]);
                $this->entityStates[$oid] = self::STATE_MANAGED;
                break;
            case self::STATE_DETACHED:
                // Can actually not happen right now since we assume STATE_NEW.
                throw new InvalidArgumentException("Detached entity passed to persist().");
            default:
                throw new UnexpectedValueException("Unexpected entity state: $xmlEntityState.");
        }

//        $this->cascadePersist($xmlEntity, $visited);
    }

    /**
     * Schedules a xml entity for dirty-checking at commit-time.
     *
     * @param object $xmlEntity The xml entity to schedule for dirty-checking.
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck($xmlEntity)
    {
        $rootClassName = $this->xem->getClassMetadata(get_class($xmlEntity))->rootXmlEntityName;
        $this->scheduledForDirtyCheck[$rootClassName][spl_object_hash($xmlEntity)] = $xmlEntity;
    }

    /**
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     * @param  $xmlEntity
     * @return void
     */
    private function persistNew($class, $xmlEntity)
    {
        $oid = spl_object_hash($xmlEntity);
        if (isset($class->lifecycleCallbacks[Events::prePersist])) {
            $class->invokeLifecycleCallbacks(Events::prePersist, $xmlEntity);
        }
        if ($this->evm->hasListeners(Events::prePersist)) {
            $this->evm->dispatchEvent(Events::prePersist, new Event\LifecycleEventArgs($xmlEntity, $this->xem));
        }

        $idGen = $class->idGenerator;
        if ( ! $idGen->isPostInsertGenerator()) {
            $idValue = $idGen->generate($this->xem, $xmlEntity);
            if ( ! $idGen instanceof \Doctrine\OXM\Id\AssignedGenerator) {
                $this->entityIdentifiers[$oid] = array($class->identifier => $idValue);
                $class->setIdentifierValue($xmlEntity, $this->entityIdentifiers[$oid]);
            } else {
                $this->entityIdentifiers[$oid] = $idValue;
            }
        }

        $this->entityStates[$oid] = self::STATE_MANAGED;

        $this->scheduleForInsert($xmlEntity);
    }

    /**
     * Schedules an document for insertion into the database.
     * If the document already has an identifier, it will be added to the identity map.
     *
     * @param object $xmlEntity The document to schedule for insertion.
     */
    public function scheduleForInsert($xmlEntity)
    {
        $oid = spl_object_hash($xmlEntity);

        if (isset($this->entityUpdates[$oid])) {
            throw new \InvalidArgumentException("Dirty xml entity can not be scheduled for insertion.");
        }
        if (isset($this->entityDeletions[$oid])) {
            throw new \InvalidArgumentException("Removed xml entity can not be scheduled for insertion.");
        }
        if (isset($this->entityInsertions[$oid])) {
            throw new \InvalidArgumentException("Xml entity can not be scheduled for insertion twice.");
        }

        $this->entityInsertions[$oid] = $xmlEntity;

        if (isset($this->entityIdentifiers[$oid])) {
            $this->addToIdentityMap($xmlEntity);
        }
    }
    
    /**
     * Checks whether an entity is scheduled for insertion.
     *
     * @param object $entity
     * @return boolean
     */
    public function isScheduledForInsert($entity)
    {
        return isset($this->entityInsertions[spl_object_hash($entity)]);
    }

    /**
     * INTERNAL:
     * Registers an document in the identity map.
     * Note that documents in a hierarchy are registered with the class name of
     * the root document.
     *
     * @ignore
     * @param object $xmlEntity  The document to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the document in question is already managed.
     */
    public function addToIdentityMap($xmlEntity)
    {
        $classMetadata = $this->xem->getClassMetadata(get_class($xmlEntity));
        $id = $this->entityIdentifiers[spl_object_hash($xmlEntity)];

        if ($id === '') {
            throw new \InvalidArgumentException("The given xml entity has no identity.");
        }
        $className = $classMetadata->rootXmlEntityName;
        if (isset($this->identityMap[$className][$id])) {
            return false;
        }
        
        $this->identityMap[$className][$id] = $xmlEntity;
        if ($xmlEntity instanceof NotifyPropertyChanged) {
            $xmlEntity->addPropertyChangedListener($this);
        }
        return true;
    }

    /**
     * Gets the state of an xml entity within the current unit of work.
     *
     * NOTE: This method sees xml entities that are not MANAGED or REMOVED and have a
     *       populated identifier, whether it is generated or manually assigned, as
     *       DETACHED. This can be incorrect for manually assigned identifiers.
     *
     * @param object $xmlEntity
     * @param integer $assume The state to assume if the state is not yet known. This is usually
     *                        used to avoid costly state lookups, in the worst case with a filesystem
     *                        lookup.
     * @return int The document state.
     */
    public function getXmlEntityState($xmlEntity, $assume = null)
    {
        $oid = spl_object_hash($xmlEntity);
        if ( ! isset($this->entityStates[$oid])) {
            $class = $this->xem->getClassMetadata(get_class($xmlEntity));

            // State can only be NEW or DETACHED, because MANAGED/REMOVED states are known.
            // Note that you can not remember the NEW or DETACHED state in _documentStates since
            // the UoW does not hold references to such objects and the object hash can be reused.
            // More generally because the state may "change" between NEW/DETACHED without the UoW being aware of it.
            if ($assume === null) {
                $id = $class->getIdentifierValue($xmlEntity);
                if ( ! $id) {
                    return self::STATE_NEW;
                } else {
                    // Last try before db lookup: check the identity map.
                    if ($this->tryGetById($id, $class->rootXmlEntityName)) {
                        return self::STATE_DETACHED;
                    } else {
                        // db lookup
                        if ($this->getXmlEntityPersister(get_class($xmlEntity))->exists($xmlEntity)) {
                            return self::STATE_DETACHED;
                        } else {
                            return self::STATE_NEW;
                        }
                    }

                }
            } else {
                return $assume;
            }
        }
        return $this->entityStates[$oid];
    }

    /**
     * INTERNAL:
     * Tries to get an document by its identifier hash. If no document is found for
     * the given hash, FALSE is returned.
     *
     * @ignore
     * @param string $id
     * @param string $rootClassName
     * @return mixed The found document or FALSE.
     */
    public function tryGetById($id, $rootClassName)
    {
        return isset($this->identityMap[$rootClassName][$id]) ?
                $this->identityMap[$rootClassName][$id] : false;
    }

    /**
     * Notifies the listener of a property change.
     *
     * @param object $sender The object on which the property changed.
     * @param string $propertyName The name of the property that changed.
     * @param mixed $oldValue The old value of the property that changed.
     * @param mixed $newValue The new value of the property that changed.
     */
    function propertyChanged($entity, $propertyName, $oldValue, $newValue)
    {
        $oid = spl_object_hash($entity);
        $class = $this->xem->getClassMetadata(get_class($entity));

        if ( ! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark entity for synchronization
        $this->entityChangeSets[$oid][$propertyName] = array($oldValue, $newValue);
        if ( ! isset($this->scheduledForDirtyCheck[$class->rootXmlEntityName][$oid])) {
            $this->scheduleForDirtyCheck($entity);
        }
    }
}
