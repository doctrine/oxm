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

namespace Doctrine\OXM\Tools;

use Doctrine\OXM\Mapping\ClassMetadataInfo,
    Doctrine\Common\Util\Inflector;

/**
 * Generic class used to generate PHP5 xml-entity classes from ClassMetadataInfo instances
 *
 *     [php]
 *     $classes = $xem->getClassMetadataInfoFactory()->getAllMetadata();
 *
 *     $generator = new \Doctrine\OXM\MongoDB\Tools\XmlEntityGenerator();
 *     $generator->setGenerateAnnotations(true);
 *     $generator->setGenerateStubMethods(true);
 *     $generator->setRegenerateXmlEntityIfExists(false);
 *     $generator->setUpdateXmlEntityIfExists(true);
 *     $generator->generate($classes, '/path/to/generate/xml-entitys');
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   1.0
 * @version $Revision$
 * @author  Igor Golovanov <igor.golovanov@gmail.com>
 */
class XmlEntityGenerator
{
    /**
     * @var bool
     */
    private $backupExisting = true;

    /** The extension to use for written php files */
    private $extension = '.php';

    /** Whether or not the current ClassMetadataInfo instance is new or old */
    private $isNew = true;

    private $staticReflection = array();

    /** Number of spaces to use for indention in generated code */
    private $numSpaces = 4;

    /** The actual spaces to use for indention */
    private $spaces = '    ';

    /** The class all generated xml-entities should extend */
    private $classToExtend;

    /** Whether or not to generation annotations */
    private $generateAnnotations = false;

    /** Whether or not to generated sub methods */
    private $generateXmlEntityStubMethods = false;

    /** Whether or not to update the xml-entity class if it exists already */
    private $updateXmlEntityIfExists = false;

    /** Whether or not to re-generate xml-entity class if it exists already */
    private $regenerateXmlEntityIfExists = false;

    private static $classTemplate =
'<?php

<namespace>

<imports>

<xmlEntityAnnotation>
<xmlEntityClassName>
{
<xmlEntityBody>
}';

    private static $getMethodTemplate =
'/**
 * <description>
 *
 * @return <variableType>$<variableName>
 */
public function <methodName>()
{
<spaces>return $this-><fieldName>;
}';

    private static $setMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName> = $<variableName>;
}';

    private static $addMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName>[] = $<variableName>;
}';

    private static $lifecycleCallbackMethodTemplate =
'<comment>
public function <methodName>()
{
<spaces>// Add your code here
}';

    private static $constructorMethodTemplate =
'public function __construct()
{
<collections>
}
';

    /**
     * Generate and write xml-entity classes for the given array of ClassMetadataInfo instances
     *
     * @param array $metadatas
     * @param string $outputDirectory 
     * @return void
     */
    public function generate(array $metadatas, $outputDirectory)
    {
        foreach ($metadatas as $metadata) {
            $this->writeXmlEntityClass($metadata, $outputDirectory);
        }
    }

    /**
     * Generated and write xml-entity class to disk for the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata
     * @param string $outputDirectory 
     * @return void
     */
    public function writeXmlEntityClass(ClassMetadataInfo $metadata, $outputDirectory)
    {
        $path = $outputDirectory . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->extension;
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->isNew = !file_exists($path) || (file_exists($path) && $this->regenerateXmlEntityIfExists);

        if ( ! $this->isNew) {
            $this->parseTokensInXmlEntityFile($path);
        }

        if ($this->backupExisting && file_exists($path)) {
            $backupPath = dirname($path) . DIRECTORY_SEPARATOR .  "~" . basename($path);
            if (!copy($path, $backupPath)) {
                throw new \RuntimeException("Attempt to backup overwritten xml-entity file but copy operation failed.");
            }
        }
        // If xml-entity doesn't exist or we're re-generating the xml-entities entirely
        if ($this->isNew) {
            file_put_contents($path, $this->generateXmlEntityClass($metadata));
        // If xml-entity exists and we're allowed to update the xml-entity class
        } else if ( ! $this->isNew && $this->updateXmlEntityIfExists) {
            file_put_contents($path, $this->generateUpdatedXmlEntityClass($metadata, $path));
        }
    }

    /**
     * Generate a PHP5 Doctrine 2 xml-entity class from the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata 
     * @return string $code
     */
    public function generateXmlEntityClass(ClassMetadataInfo $metadata)
    {
        $placeHolders = array(
            '<namespace>',
            '<imports>',
            '<xmlEntityAnnotation>',
            '<xmlEntityClassName>',
            '<xmlEntityBody>'
        );

        $replacements = array(
            $this->generateXmlEntityNamespace($metadata),
            $this->generateXmlEntityImports($metadata),
            $this->generateXmlEntityDocBlock($metadata),
            $this->generateXmlEntityClassName($metadata),
            $this->generateXmlEntityBody($metadata)
        );

        $code = str_replace($placeHolders, $replacements, self::$classTemplate);
        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generate the updated code for the given ClassMetadataInfo and xml-entity at path
     *
     * @param ClassMetadataInfo $metadata 
     * @param string $path 
     * @return string $code;
     */
    public function generateUpdatedXmlEntityClass(ClassMetadataInfo $metadata, $path)
    {
        $currentCode = file_get_contents($path);

        $body = $this->generateXmlEntityBody($metadata);
        $body = str_replace('<spaces>', $this->spaces, $body);
        $last = strrpos($currentCode, '}');

        return substr($currentCode, 0, $last) . $body . (strlen($body) > 0 ? "\n" : ''). "}";
    }

    /**
     * Set the number of spaces the exported class should have
     *
     * @param integer $numSpaces 
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces = str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;
    }

    /**
     * Set the extension to use when writing php files to disk
     *
     * @param string $extension 
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Set the name of the class the generated classes should extend from
     *
     * @return void
     */
    public function setClassToExtend($classToExtend)
    {
        $this->classToExtend = $classToExtend;
    }

    /**
     * Set whether or not to generate annotations for the xml-entity
     *
     * @param bool $bool 
     * @return void
     */
    public function setGenerateAnnotations($bool)
    {
        $this->generateAnnotations = $bool;
    }

    /**
     * Set whether or not to try and update the xml-entity if it already exists
     *
     * @param bool $bool 
     * @return void
     */
    public function setUpdateXmlEntityIfExists($bool)
    {
        $this->updateXmlEntityIfExists = $bool;
    }

    /**
     * Set whether or not to regenerate the xml-entity if it exists
     *
     * @param bool $bool
     * @return void
     */
    public function setRegenerateXmlEntityIfExists($bool)
    {
        $this->regenerateXmlEntityIfExists = $bool;
    }

    /**
     * Set whether or not to generate stub methods for the xml-entity
     *
     * @param bool $bool
     * @return void
     */
    public function setGenerateStubMethods($bool)
    {
        $this->generateXmlEntityStubMethods = $bool;
    }

    /**
     * Should an existing xml-entity be backed up if it already exists?
     */
    public function setBackupExisting($bool)
    {
        $this->backupExisting = $bool;
    }

    private function generateXmlEntityNamespace(ClassMetadataInfo $metadata)
    {
        if ($this->hasNamespace($metadata)) {
            return 'namespace ' . $this->getNamespace($metadata) .';';
        }
    }

    private function generateXmlEntityClassName(ClassMetadataInfo $metadata)
    {
        return 'class ' . $this->getClassName($metadata) .
            ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    private function generateXmlEntityBody(ClassMetadataInfo $metadata)
    {
        $fieldMappingProperties = $this->generateXmlEntityFieldMappingProperties($metadata);
        
        $stubMethods = $this->generateXmlEntityStubMethods ? $this->generateXmlEntityStubMethods($metadata) : null;
        $lifecycleCallbackMethods = $this->generateXmlEntityLifecycleCallbackMethods($metadata);

        $code = array();

        if ($fieldMappingProperties) {
            $code[] = $fieldMappingProperties;
        }

        $code[] = $this->generateXmlEntityConstructor($metadata);

        if ($stubMethods) {
            $code[] = $stubMethods;
        }

        if ($lifecycleCallbackMethods) {
            $code[] = "\n".$lifecycleCallbackMethods;
        }

        return implode("\n", $code);
    }

    private function generateXmlEntityConstructor(ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod('__construct', $metadata)) {
            return '';
        }

        return '';
    }

    /**
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     * @param string $path
     */
    private function parseTokensInXmlEntityFile($path)
    {
        $tokens = token_get_all(file_get_contents($path));
        $lastSeenNamespace = '';
        $lastSeenClass = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if ($token[0] == T_NAMESPACE) {
                $peek = $i;
                $lastSeenNamespace = '';
                while (isset($tokens[++$peek])) {
                    if (';' == $tokens[$peek]) {
                        break;
                    } elseif (is_array($tokens[$peek]) && in_array($tokens[$peek][0], array(T_STRING, T_NS_SEPARATOR))) {
                        $lastSeenNamespace .= $tokens[$peek][1];
                    }
                }
            } else if ($token[0] == T_CLASS) {
                $lastSeenClass = $lastSeenNamespace . '\\' . $tokens[$i+2][1];
                $this->staticReflection[$lastSeenClass]['properties'] = array();
                $this->staticReflection[$lastSeenClass]['methods'] = array();
            } else if ($token[0] == T_FUNCTION) {
                if ($tokens[$i+2][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = $tokens[$i+2][1];
                } else if ($tokens[$i+2][0] == T_AMPERSAND && $tokens[$i+3][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = $tokens[$i+3][1];
                }
            } else if (in_array($token[0], array(T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED)) && $tokens[$i+2][0] != T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }

    private function hasProperty($property, ClassMetadataInfo $metadata)
    {
        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($property, $this->staticReflection[$metadata->name]['properties'])
        );
    }

    private function hasMethod($method, ClassMetadataInfo $metadata)
    {
        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($method, $this->staticReflection[$metadata->name]['methods'])
        );
    }

    private function hasNamespace(ClassMetadataInfo $metadata)
    {
        return strpos($metadata->name, '\\') ? true : false;
    }

    private function extendsClass()
    {
        return $this->classToExtend ? true : false;
    }

    private function getClassToExtend()
    {
        return $this->classToExtend;
    }

    private function getClassToExtendName()
    {
        $refl = new \ReflectionClass($this->getClassToExtend());

        return '\\' . $refl->getName();
    }

    private function getClassName(ClassMetadataInfo $metadata)
    {
        return ($pos = strrpos($metadata->name, '\\'))
            ? substr($metadata->name, $pos + 1, strlen($metadata->name)) : $metadata->name;
    }

    private function getNamespace(ClassMetadataInfo $metadata)
    {
        return substr($metadata->name, 0, strrpos($metadata->name, '\\'));
    }

    private function generateXmlEntityImports(ClassMetadataInfo $metadata)
    {
        if ($this->generateAnnotations) {
            return 'use Doctrine\\OXM\\Mapping as OXM;';
        }
    }

    private function generateXmlEntityDocBlock(ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = '/**';
        $lines[] = ' * '.$metadata->name;

        if ($this->generateAnnotations) {
            $lines[] = ' *';

            if ($metadata->isMappedSuperclass) {
                $lines[] = ' * @OXM\\XmlMappedSupperClass';
            } else if ($metadata->isRoot) {
                $lines[] = ' * @OXM\\XmlRootEntity';
            } else {
                $lines[] = ' * @OXM\\XmlEntity';
            }

            $xmlEntity = array();
            if ($metadata->isRoot) {
                if ($metadata->customRepositoryClassName) {
                    $xmlEntity[] = ' *     repositoryClass="' . $metadata->customRepositoryClassName . '"';
                }
            }
            
            

            if ($xmlEntity) {
                $lines[count($lines) - 1] .= '(';
                $lines[] = implode(",\n", $xmlEntity);
                $lines[] = ' * )';
            }
            
            if (isset($metadata->lifecycleCallbacks) && $metadata->lifecycleCallbacks) {
                $lines[] = ' * @OXM\\HasLifecycleCallbacks';
            }

            $methods = array(
                'generateChangeTrackingPolicyAnnotation'
            );

            foreach ($methods as $method) {
                if ($code = $this->$method($metadata)) {
                    $lines[] = ' * ' . $code;
                }
            }
        }
        

        $lines[] = ' */';
        return implode("\n", $lines);
    }


    private function generateChangeTrackingPolicyAnnotation(ClassMetadataInfo $metadata)
    {
        return '@OXM\\XmlChangeTrackingPolicy("' . $this->getChangeTrackingPolicyString($metadata->changeTrackingPolicy) . '")';
    }

    private function generateXmlEntityStubMethods(ClassMetadataInfo $metadata)
    {
        $methods = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['id'])) {
                //if ($metadata->getgeneratorType == ClassMetadataInfo::GENERATOR_TYPE_NONE) {
                    if ($code = $this->generateXmlEntityStubMethod($metadata, 'set', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                        $methods[] = $code;
                    }
                //}
                if ($code = $code = $this->generateXmlEntityStubMethod($metadata, 'get', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
            } else {
                if ($code = $code = $this->generateXmlEntityStubMethod($metadata, 'set', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
                if ($code = $code = $this->generateXmlEntityStubMethod($metadata, 'get', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
            } 
        }

        return implode("\n\n", $methods);
    }

    private function generateXmlEntityLifecycleCallbackMethods(ClassMetadataInfo $metadata)
    {
        if (isset($metadata->lifecycleCallbacks) && $metadata->lifecycleCallbacks) {
            $methods = array();

            foreach ($metadata->lifecycleCallbacks as $name => $callbacks) {
                foreach ($callbacks as $callback) {
                    if ($code = $this->generateLifecycleCallbackMethod($name, $callback, $metadata)) {
                        $methods[] = $code;
                    }
                }
            }

            return implode("\n\n", $methods);
        }

        return "";
    }


    private function generateXmlEntityFieldMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if ($this->hasProperty($fieldMapping['fieldName'], $metadata) ||
                $metadata->isInheritedField($fieldMapping['fieldName'])) {
                continue;
            }
            if (isset($fieldMapping['association']) && $fieldMapping['association']) {
                continue;
            }

            $lines[] = $this->generateFieldMappingPropertyDocBlock($fieldMapping, $metadata);
            $lines[] = $this->spaces . 'private $' . $fieldMapping['fieldName']
                     . (isset($fieldMapping['default']) ? ' = ' . var_export($fieldMapping['default'], true) : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    private function generateXmlEntityStubMethod(ClassMetadataInfo $metadata, $type, $fieldName, $typeHint = null)
    {
        $methodName = $type . Inflector::classify($fieldName);
        
        if ($this->hasMethod($methodName, $metadata)) {
            return;
        }

        $var = sprintf('%sMethodTemplate', $type);
        $template = self::$$var;

        $variableType = $typeHint ? $typeHint . ' ' : null;

        $types = \Doctrine\OXM\Types\Type::getTypesMap();
        $methodTypeHint = $typeHint && ! isset($types[$typeHint]) ? '\\' . $typeHint . ' ' : null;

        $replacements = array(
          '<description>'       => ucfirst($type) . ' ' . $fieldName,
          '<methodTypeHint>'    => $methodTypeHint,
          '<variableType>'      => $variableType,
          '<variableName>'      => Inflector::camelize($fieldName),
          '<methodName>'        => $methodName,
          '<fieldName>'         => $fieldName
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        
        return $this->prefixCodeWithSpaces($method);
    }

    private function generateLifecycleCallbackMethod($name, $methodName, $metadata)
    {
        if ($this->hasMethod($methodName, $metadata)) {
            return;
        }

        $replacements = array(
            '<comment>'    => $this->generateAnnotations ? '/** @OXM\\'.ucfirst($name).' */' : '',
            '<methodName>' => $methodName,
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            self::$lifecycleCallbackMethodTemplate
        );
        

        return $this->prefixCodeWithSpaces($method);
    }

    private function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
            

        $lines[] = $this->spaces . ' * @var ' . $fieldMapping['type'] . ' $' . $fieldMapping['fieldName'];

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            if (isset($fieldMapping['id']) && $fieldMapping['id']) {                
                $lines[] = $this->spaces . ' * @OXM\\XmlId';
                
                if ($generatorType = $this->getIdGeneratorTypeString($metadata->generatorType)) {
                    $lines[] = $this->spaces.' * @OXM\\XmlGeneratedValue(strategy="' . $generatorType . '")';
                }
            }
            
            if(isset($fieldMapping['references'])) {
                $lines[] = $this->spaces.' * @OXM\\XmlReferences(entityName="' . $fieldMapping['references'] . '")';
            }
            
            $field = array();
            if (isset($fieldMapping['name'])) {
                $field[] = 'name="' . $fieldMapping['name'] . '"';
            }

            if (isset($fieldMapping['type'])) {
                $field[] = 'type="' . $fieldMapping['type'] . '"';
            }

            if (isset($fieldMapping['nullable']) && $fieldMapping['nullable'] === true) {
                $field[] = 'nullable=' .  var_export($fieldMapping['nullable'], true);
            }
            if (isset($fieldMapping['options'])) {
                $options = array();
                foreach ($fieldMapping['options'] as $key => $value) {
                    $options[] = '"' . $key . '" = "' . $value . '"';
                }
                $field[] = "options={".implode(', ', $options)."}";
            }
            $lines[] = $this->spaces . ' * @OXM\\XmlField(' . implode(', ', $field) . ')';
            

//            if (isset($fieldMapping['version']) && $fieldMapping['version']) {
//                $lines[] = $this->spaces . ' * @OXM\\Version';
//            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    private function prefixCodeWithSpaces($code, $num = 1)
    {
        $lines = explode("\n", $code);

        foreach ($lines as $key => $value) {
            $lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
        }

        return implode("\n", $lines);
    }


    private function getChangeTrackingPolicyString($policy)
    {
        switch ($policy) {
            case ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT:
                return 'DEFERRED_IMPLICIT';

            case ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT:
                return 'DEFERRED_EXPLICIT';

            case ClassMetadataInfo::CHANGETRACKING_NOTIFY:
                return 'NOTIFY';

            default:
                throw new \InvalidArgumentException('Invalid provided ChangeTrackingPolicy: ' . $policy);
        }
    }

    private function getIdGeneratorTypeString($type)
    {
        switch ($type) {
            case ClassMetadataInfo::GENERATOR_TYPE_AUTO:
                return 'AUTO';

            case ClassMetadataInfo::GENERATOR_TYPE_INCREMENT:
                return 'INCREMENT';

            case ClassMetadataInfo::GENERATOR_TYPE_UUID:
                return 'UUID';

            case ClassMetadataInfo::GENERATOR_TYPE_NONE:
                return 'NONE';

            default:
                throw new \InvalidArgumentException('Invalid provided IdGeneratorType: ' . $type);
        }
    }
}