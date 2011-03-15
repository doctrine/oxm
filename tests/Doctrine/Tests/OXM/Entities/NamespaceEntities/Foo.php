<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/10/11
 * Time: 8:50 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities\NamespaceEntities;

/**
 * @XmlRootEntity
 * @XmlNamespaces({
 *   @XmlNamespace(url="http://www.foobar.com/schema"),
 *   @XmlNamespace(url="http://www.foobaz.com/schema", prefix="baz")
 * })
 */
class Foo
{
    /** @XmlText(type="string") */
    public $id;

    /**
     * @XmlText(type="string", prefix="baz")
     */
    public $bo;
}
