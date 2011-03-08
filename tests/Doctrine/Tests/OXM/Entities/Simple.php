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

/** @XmlMappedSuperclass */
abstract class SimpleAbstractParent { /** @XmlText(type="string", direct=true) */public $other = "yes"; }

/** @XmlMappedSuperclass */
abstract class SimpleAbstractParentWithField { /** @XmlAttribute(type="integer", direct=true) */ private $id = 2; }

/** @XmlEntity */
class SimpleChild extends SimpleAbstractParent { }
    
/** @XmlEntity */
class SimpleChildExtendsWithParentField extends SimpleAbstractParentWithField { }

/** @XmlEntity */
class SimpleChildExtendsWithChildField extends SimpleAbstractParent { /** @XmlAttribute(type="integer", direct=true) */ public $id; }

