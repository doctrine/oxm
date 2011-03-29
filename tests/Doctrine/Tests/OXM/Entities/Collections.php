<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/4/11
 * Time: 10:18 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities;

/** @XmlEntity */
class CollectionClass
{
    /** @var array @XmlText(type="string", collection=true) */
    public $list;
}
