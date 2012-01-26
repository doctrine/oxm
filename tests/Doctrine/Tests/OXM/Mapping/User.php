<?php
/*
 * This file is part of ProFIT
 *
 * Copyright (c) 2011 Farheap Solutions (http://www.farheap.com)
 *
 * The unauthorized use of this code outside the boundaries of
 * Farheap Solutions Inc. is prohibited.
 */

namespace Doctrine\Tests\OXM\Mapping;

use Doctrine\OXM\Mapping\ClassMetadataInfo;

/**
 * @XmlRootEntity(xml="cms-user")
 * @HasLifecycleCallbacks
 * @XmlNamespaces({
 *   @XmlNamespace(url="http://www.schema.com/foo", prefix="foo"),
 *   @XmlNamespace(url="http://www.schema.com/bar", prefix="bar")
 * })
 */
class User
{
    /**
     * @XmlId
     * @XmlField(type="string", node="attribute")
     */
    public $id;

    /**
     * @XmlText(type="string", required=true, getMethod="getUsername", setMethod="setUsername")
     */
    public $name;

    /**
     * @XmlText(type="string", collection=true, wrapper="comments", name="comment")
     */
    public $comments;

    /**
     * @XmlElement(type="Doctrine\Tests\OXM\Mapping\Role", collection=true, name="role")
     */
    public $roles;

    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist()
    {

    }
    /**
     * @PrePersist
     */
    public function doOtherStuffOnPrePersistToo()
    {

    }

    /**
     * @PostPersist
     */
    public function doStuffOnPostPersist()
    {

    }

    /**
     * @PreMarshal
     */
    public function doStuffOnPreMarshal()
    {

    }

    public function getUsername()
    {
        return $this->name;
    }

    public function setUsername($name)
    {
        $this->name = $name;
    }

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->setXmlName('cms-user');
        $metadata->isRoot = true;

        $metadata->setXmlNamespaces(array(
            array('url' => 'http://www.schema.com/foo', 'prefix' => 'foo'),
            array('url' => 'http://www.schema.com/bar', 'prefix' => 'bar')
        ));

        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
        $metadata->addLifecycleCallback('doStuffOnPreMarshal', 'preMarshal');

        $metadata->mapField(array(
            'fieldName' => 'id',
            'id' => true,
            'type' => 'string',
            'node' => 'attribute'
        ));
        $metadata->mapField(array(
            'fieldName' => 'name',
            'type' => 'string',
            'node' => 'text',
            'required' => true,
            'setMethod' => 'setUsername',
            'getMethod' => 'getUsername'
        ));
        $metadata->mapField(array(
            'fieldName' => 'comments',
            'type' => 'string',
            'node' => 'text',
            'collection' => true,
            'wrapper' => 'comments',
            'name' => 'comment',
        ));
        $metadata->mapField(array(
            'fieldName' => 'roles',
            'type' => 'Role',
            'node' => 'value',
            'collection' => true,
            'name' => 'role',
        ));
    }
}
