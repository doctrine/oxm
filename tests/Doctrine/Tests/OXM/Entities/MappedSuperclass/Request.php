<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/10/11
 * Time: 8:50 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities\MappedSuperclass;

/**
 * @XmlRootEntity
 */
class Request
{
    /** @XmlAttribute(type="string") */
    public $id;

    /**
     * @XmlElement(type="Doctrine\Tests\OXM\Entities\MappedSuperclass\AbstractBusinessObject")
     */
    public $bo;
}
