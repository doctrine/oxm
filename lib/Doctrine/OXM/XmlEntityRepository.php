<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/28/11
 * Time: 9:35 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\OXM;

use \Doctrine\Common\Persistence\ObjectRepository;

class XmlEntityRepository implements ObjectRepository
{
    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria
     * @return object The object.
     */
    public function findOneBy(array $criteria)
    {
        // TODO: Implement findOneBy() method.
    }

    /**
     * Finds objects by a set of criteria.
     *
     * @param array $criteria
     * @return mixed The objects.
     */
    public function findBy(array $criteria)
    {
        // TODO: Implement findBy() method.
    }

    /**
     * Finds all objects in the repository.
     *
     * @return mixed The objects.
     */
    public function findAll()
    {
        // TODO: Implement findAll() method.
    }

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param $id The identifier.
     * @return object The object.
     */
    public function find($id)
    {
        // TODO: Implement find() method.
    }

}
