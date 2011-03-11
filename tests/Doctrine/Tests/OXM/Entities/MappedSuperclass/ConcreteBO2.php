<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/10/11
 * Time: 8:52 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities\MappedSuperclass;

/**
 * @XmlEntity
 */
class ConcreteBO2 extends AbstractBusinessObject
{
    /**
     * @XmlAttribute(type="string")
     */
    public $description;
}
