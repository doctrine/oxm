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
class Simple { }
    
/** @XmlEntity */
class SimpleCompound { }

/** @XmlEntity */
class SimpleWithField { /** @XmlAttribute(type="integer", direct=true) */ public $id; }

abstract class SimpleAbstractParent { }

