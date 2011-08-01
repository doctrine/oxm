<?php

namespace Doctrine\OXM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\OXM\XmlEntityManager;

/**
 * Class that holds event arguments for a preInsert/preUpdate event.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Richard Fullmer <richardfullmer@gmail.com>
 * @since 2.0
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $entityChangeSet;

    /**
     *
     * @param object $entity
     * @param XmlEntityManager $xem
     * @param array $changeSet
     */
    public function __construct($entity, $xem, array &$changeSet)
    {
        parent::__construct($entity, $xem);
        $this->entityChangeSet = &$changeSet;
    }

    public function getEntityChangeSet()
    {
        return $this->entityChangeSet;
    }

    /**
     * Field has a changeset?
     *
     * @return bool
     */
    public function hasChangedField($field)
    {
        return isset($this->entityChangeSet[$field]);
    }

    /**
     * Get the old value of the changeset of the changed field.
     * 
     * @param  string $field
     * @return mixed
     */
    public function getOldValue($field)
    {
    	$this->assertValidField($field);

        return $this->entityChangeSet[$field][0];
    }

    /**
     * Get the new value of the changeset of the changed field.
     *
     * @param  string $field
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][1];
    }

    /**
     * Set the new value of this field.
     * 
     * @param string $field
     * @param mixed $value
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->entityChangeSet[$field][1] = $value;
    }

    private function assertValidField($field)
    {
    	if (!isset($this->entityChangeSet[$field])) {
            throw new \InvalidArgumentException(
                "Field '".$field."' is not a valid field of the entity ".
                "'".get_class($this->getXmlEntity())."' in PreInsertUpdateEventArgs."
            );
        }
    }
}

