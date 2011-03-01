<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\OXM\Id;

use Doctrine\OXM\XmlEntityManager;
use Doctrine\OXM\Mapping\ClassMetadata;

/**
 * IncrementGenerator is responsible for generating auto increment identifiers. It uses
 * a collection named "doctrine_increment_ids" which stores a document for each document
 * type and generates the next id by using $inc on a field named "current_id".
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class IncrementGenerator extends AbstractIdGenerator
{

    /** @inheritDoc */
    public function generate(XmlEntityManager $xem, $xmlEntity)
    {
        $className = get_class($xmlEntity);
        // keep a file marker on the filesystem with the "next" number in it
//        $db = $xem->getDocumentDatabase($className);
//        $coll = $dm->getDocumentCollection($className);
//
//        $query = array('_id' => $coll->getName());
//        $newObj = array('$inc' => array('current_id' => 1));
//
//        $command = array();
//        $command['findandmodify'] = 'doctrine_increment_ids';
//        $command['query'] = $query;
//        $command['update'] = $newObj;
//        $command['upsert'] = true;
//        $command['new'] = true;
//        $result = $db->command($command);
//        return $result['value']['current_id'];
    }
}