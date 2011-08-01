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


namespace Doctrine\OXM\Mapping\Driver;

use SimpleXMLElement;
use Doctrine\OXM\Mapping\MappingException;
use Doctrine\OXM\Mapping\ClassMetadataInfo;
use Doctrine\OXM\Util\Inflector;

/**
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class XmlDriver extends AbstractFileDriver
{
    /**
     * {@inheritdoc}
     */
    protected $fileExtension = '.dcm.xml';

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $xmlRoot = $this->getElement($className);
        
        if ($xmlRoot->getName() == 'entity') {
            if (isset($xmlRoot['root']) && $xmlRoot['root'] == "true") {
                $metadata->isRoot = true;

                $metadata->setCustomRepositoryClass(
                    isset($xmlRoot['repository-class']) ? (string) $xmlRoot['repository-class'] : null
                );
            }
        } else if ($xmlRoot->getName() == 'mapped-superclass') {
            $metadata->isMappedSuperclass = true;
        } else {
            throw MappingException::classIsNotAValidXmlEntityOrXmlMappedSuperClass($className);
        }

        $metadata->setName($className);

        // Evalute xml-name attribute
        if (isset($xmlRoot['xml-name'])) {
            $metadata->setXmlName((string) $xmlRoot['xml-name']);
        } else {
            $metadata->setXmlName(Inflector::xmlize($className));
        }


        // Evaluate change-tracking-policy attribute
        if (isset($xmlRoot['change-tracking-policy'])) {
            $metadata->setChangeTrackingPolicy(constant('Doctrine\OXM\Mapping\ClassMetadata::CHANGETRACKING_'
                    . strtoupper((string)$xmlRoot['change-tracking-policy'])));
        }

        // Evaluate <namespaces...>
        if (isset($xmlRoot->namespaces)) {
            $namespaces = array();
            foreach ($xmlRoot->namespaces->namespace as $namespace) {
                $namespaces[] = array(
                    'url' => (string) $namespace['url'],
                    'prefix' => (string) $namespace['prefix']
                );
            }
            $metadata->setXmlNamespaces($namespaces);
        }

        // Evaluate <field ...> mappings
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $fieldMapping) {
                $mapping = array(
                    'fieldName' => (string)$fieldMapping['name'],
                    'type' => (string)$fieldMapping['type'],
                    'node' => constant('Doctrine\OXM\Mapping\ClassMetadata::XML_' . strtoupper((string)$fieldMapping['node'])),
                );

                if (isset($fieldMapping['xml-name'])) {
                    $mapping['name'] = (string)$fieldMapping['xml-name'];
                }

                if (isset($fieldMapping['identifier'])) {
                    $mapping['id'] = (boolean)$fieldMapping['identifier'];
                }

                if (isset($fieldMapping['direct'])) {
                    $mapping['direct'] = (boolean)$fieldMapping['direct'];
                }

                if (isset($fieldMapping['nulable'])) {
                    $mapping['nullable'] = (boolean)$fieldMapping['nullable'];
                }

                if (isset($fieldMapping['required'])) {
                    $mapping['required'] = (boolean)$fieldMapping['required'];
                }

                if (isset($fieldMapping['collection'])) {
                    $mapping['collection'] = (boolean)$fieldMapping['collection'];
                }

                if (isset($fieldMapping['get-method'])) {
                    $mapping['getMethod'] = (string) $fieldMapping['get-method'];
                }

                if (isset($fieldMapping['set-method'])) {
                    $mapping['setMethod'] = (string) $fieldMapping['set-method'];
                }

                if (isset($fieldMapping['prefix'])) {
                    $mapping['prefix'] = (string) $fieldMapping['prefix'];
                }

                if (isset($fieldMapping['wrapper'])) {
                    $mapping['wrapper'] = (string) $fieldMapping['wrapper'];
                }

                $metadata->mapField($mapping);
            }
        }


        // Evaluate <lifecycle-callbacks...>
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $metadata->addLifecycleCallback((string)$lifecycleCallback['method'], constant('Doctrine\OXM\Events::' . (string)$lifecycleCallback['type']));
            }
        }
    }


    protected function loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $xmlEntityElement) {
                $className = (string) $xmlEntityElement['class'];
                $result[$className] = $xmlEntityElement;
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $xmlMappedSuperClass) {
                $className = (string) $xmlMappedSuperClass['class'];
                $result[$className] = $xmlMappedSuperClass;
            }
        }

        return $result;
    }
}
