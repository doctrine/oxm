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
use Doctrine\OXM\OXMException;

/**
 * Special generator for application-assigned identifiers (doesnt really generate anything).
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class AssignedGenerator extends AbstractIdGenerator
{
    /**
     * Returns the identifier assigned to the given entity.
     *
     * @param object $xmlEntity
     * @return mixed
     * @override
     */
    public function generate(XmlEntityManager $xem, $xmlEntity)
    {
        $class = $xem->getClassMetadata(get_class($xmlEntity));

        $idField = $class->identifier;
        $value = $class->reflFields[$idField]->getValue($xmlEntity);
        if (isset($value)) {
            if (is_object($value)) {
                // NOTE: Single Columns as associated identifiers only allowed - this constraint it is enforced.
                $identifier = current($xem->getUnitOfWork()->getEntityIdentifier($value));
            } else {
                $identifier = $value;
            }
        } else {
            throw OXMException::entityMissingAssignedId($xmlEntity);
        }
        
        return $identifier;
    }
}