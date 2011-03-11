<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/10/11
 * Time: 8:49 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities\MappedSuperclass;

/**
 * @XmlMappedSuperclass
 */
abstract class AbstractBusinessObject
{
    /** @XmlAttribute(type="string") */
    public $inherit;

}
