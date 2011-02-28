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

namespace Doctrine\OXM;

use \Doctrine\OXM\Marshaller\Marshaller,
    \Doctrine\Common\EventManager;

/**
 *
 */
class XmlEntityManager
{
    /**
     * The used Configuration.
     *
     * @var Configuration
     */
    private $config;

    /**
     * @var Mapping\MappingFactory
     */
    private $mappingFactory;

    /**
     * @var Marshaller\Marshaller
     */
    private $marshaller;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Creates a new EntityManager that uses the given Configuration and EventManager implementations.
     *
     * @param Configuration $config
     * @param \Doctrine\Common\EventManager $eventManager
     */
    public function __construct(Marshaller $marshaller, Configuration $config, EventManager $eventManager = null)
    {
        $this->marshaller = $marshaller;
        $this->config = $config;

        if (null === $eventManager) {
            $eventManager = new EventManager;
        }
        $this->eventManager = $eventManager;

        $metadataFactoryClassName = $config->getMappingFactoryName();
        $this->mappingFactory = new $metadataFactoryClassName;
        $this->mappingFactory->setXmlEntityManager($this);
        $this->mappingFactory->setCacheDriver($this->config->getMappingCacheImpl());
    }


    /**
     * Marshals a mapped object into XML
     *
     * @param object $object
     * @return string
     */
    public function marshal($object)
    {
        return $this->marshaller->marshal($this->mappingFactory, $object);
    }

    /**
     * Unmarshals XML into mapped objects
     *
     * @param string $xml
     * @return object
     */
    public function unmarshal($xml)
    {
        return $this->marshaller->unmarshal($this->mappingFactory, $xml);
    }

    /**
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return Mapping\MappingFactory
     */
    public function getMappingFactory()
    {
        return $this->mappingFactory;
    }

    /**
     * @return Mapping\Driver\Driver
     */
    public function getMappingDriverImpl()
    {
        return $this->config->getMappingDriverImpl();
    }

    /**
     * @return Marshaller\Marshaller
     */
    public function getMarshaller()
    {
        return $this->marshaller;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Returns the OXM mapping descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)) or an aliased class name.
     *
     * Examples:
     * MyProject\Domain\User
     * sales:PriceRequest
     *
     * @return Mapping\Mapping
     * @internal Performance-sensitive method.
     */
    public function getMapping($className)
    {
        return $this->mappingFactory->getMappingForClass($className);
    }
}
