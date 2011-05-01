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

namespace Doctrine\OXM\Persisters;

use Doctrine\OXM\XmlEntityManager;
use Doctrine\OXM\Mapping\ClassMetadata;

class RootXmlEntityPersister
{
    /**
     * @var \Doctrine\OXM\Marshaller\Marshaller
     */
    private $marshaller;

    /**
     * @var \Doctrine\OXM\XmlEntityManager
     */
    private $xem;

    /**
     * @var \Doctrine\OXM\Storage\Storage
     */
    private $storage;

    /**
     * @var \Doctrine\OXM\Mapping\ClassMetadata
     */
    private $metadata;

    /**
     * @param \Doctrine\OXM\XmlEntityManager $xem
     * @param \Doctrine\OXM\Mapping\ClassMetadataInfo
     */
    public function __construct(XmlEntityManager $xem, ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
        $this->xem = $xem;
        $this->marshaller = $xem->getMarshaller();
        $this->storage = $xem->getStorage();
    }

    /**
     * Inserts this xml entity into the storage system
     *
     * @param  $xmlEntity
     * @return bool|int
     */
    public function insert($xmlEntity)
    {
        $identifier = $this->metadata->getIdentifierValue($xmlEntity);

        $xml = $this->marshaller->marshalToString($xmlEntity);
        
        return $this->storage->insert($this->metadata, $identifier, $xml);
    }


    /**
     * Updates this xml entity in the storage system
     *
     * @param  $xmlEntity
     * @return bool|int
     */
    public function update($xmlEntity)
    {
        $identifier = $this->metadata->getIdentifierValue($xmlEntity);

        $xml = $this->marshaller->marshalToString($xmlEntity);

        return $this->storage->update($this->metadata, $identifier, $xml);
    }

    /**
     * @param object $xmlEntity
     * @return boolean
     */
    public function exists($xmlEntity)
    {
        return $this->storage->exists($this->metadata, $this->metadata->getIdentifierValue($xmlEntity));
    }

    public function delete($xmlEntity, array $options = array())
    {
        return $this->storage->delete($this->metadata, $this->metadata->getIdentifierValue($xmlEntity));
    }


    public function load($id)
    {
        $xml = $this->storage->load($this->metadata, $id);

        return $this->marshaller->unmarshalFromString($xml);
    }
}
