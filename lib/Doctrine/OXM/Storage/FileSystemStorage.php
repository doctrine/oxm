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

namespace Doctrine\OXM\Storage;

use Doctrine\OXM\Mapping\ClassMetadataInfo;

 /**
  * @author Richard Fullmer <richardfullmer@gmail.com>
  */
class FileSystemStorage implements Storage
{
    /**
     * @var string
     */
    private $storagePath;

    /**
     * @var string
     */
    private $fileExtension;

    /**
     * @var int
     */
    private $fileModeBits = 0755;

    /**
     * @var boolean
     */
    private $useNamespaceInPath = true;

    /**
     * @var array
     */
    private $fileLocks = array();


    /**
     * Construct a file system store with a specific base path
     */
    public function __construct($baseStoragePath, $defaultFileExtension = 'xml')
    {
        // todo - ensure storage path exists
        $this->storagePath = $baseStoragePath;
        $this->fileExtension = $defaultFileExtension;
    }

    /**
     * Release all known file locks when FileSystemStorage no longer in scope
     */
    public function __destruct()
    {
        foreach (array_keys($this->fileLocks) as $filename) {
            $this->unlock($filename);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(ClassMetadataInfo $classMetadata, $id)
    {
        return file_get_contents($this->getFilename($classMetadata, $id));
    }

    /**
     * {@inheritDoc}
     */
    public function exists(ClassMetadataInfo $classMetadata, $id)
    {
        return is_file($this->getFilename($classMetadata, $id));
    }

    /**
     * Insert the XML into the filesystem with a specific identifier
     *
     * @throws StorageException
     * @param \Doctrine\OXM\Mapping\ClassMetadataInfo $classMetadata
     * @param string $id
     * @param string $xmlContent
     * @return boolean
     */
    public function insert(ClassMetadataInfo $classMetadata, $id, $xmlContent)
    {
        $this->prepareStoragePathForClass($this->resolveClassName($classMetadata));

        $filePath = $this->getFilename($classMetadata, $id);
        $result = file_put_contents($filePath, $xmlContent);

        if (false === $result) {
            // @codeCoverageIgnoreStart
            throw new StorageException("Entity '$id' could not be saved to the filesystem at '$filePath'");
            // @codeCoverageIgnoreEnd
        }

        return $result > 0;
    }


    /**
     * Update the XML in the filesystem with a specific identifier
     *
     * @throws StorageException
     * @param \Doctrine\OXM\Mapping\ClassMetadataInfo $classMetadata
     * @param string $id
     * @param string $xmlContent
     * @return boolean
     */
    public function update(ClassMetadataInfo $classMetadata, $id, $xmlContent)
    {
        $this->prepareStoragePathForClass($this->resolveClassName($classMetadata));

        $filePath = $this->getFilename($classMetadata, $id);
        $result = file_put_contents($filePath, $xmlContent);

        if (false === $result) {
            // @codeCoverageIgnoreStart
            throw new StorageException("Entity '$id' could not be saved to the filesystem at '$filePath'");
            // @codeCoverageIgnoreEnd
        }

        return $result > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(ClassMetadataInfo $classMetadata, $id, array $options = array())
    {
        $filePath = $this->getFilename($classMetadata, $id);
        $result = unlink($filePath);
        if (false === $result) {
            // @codeCoverageIgnoreStart
            throw new StorageException("Entity '$id' could not be deleted from the filesystem at '$filePath'");
            // @codeCoverageIgnoreEnd
        }
        return $result;
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     * @return string
     */
    private function resolveClassName(ClassMetadataInfo $classMetadata)
    {
        if ($this->useNamespaceInPath) {
            return $classMetadata->rootXmlEntityName;
        } else {
            $classParts = explode("\\", $classMetadata->rootXmlEntityName);
            return array_pop($classParts);
        }
    }

    /**
     * Build the realpath to save the xml in a specific folder
     *
     * @param string $className
     * @return string
     */
    private function prepareStoragePathForClass($className)
    {
        $filePath = $this->buildStoragePath($className);
        if (!file_exists($filePath)) {
            mkdir($filePath, $this->fileModeBits, true);
        }
        return $filePath;
    }

    /**
     * @param string
     * @return string
     */
    private function buildStoragePath($className)
    {
        return $this->storagePath . '/' . implode('/', explode("\\", $className));
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     * @param mixed $id
     * @return string The filename for the given entity
     */
    protected function getFilename(ClassMetadataInfo $classMetadata, $id)
    {
        $baseFilePath = $this->buildStoragePath($this->resolveClassName($classMetadata));

        // todo - id should be sanitized for the filesystem

        return $baseFilePath . '/' . $id . '.' . $this->fileExtension;
    }

    /**
     * @param string $filename
     * @param Resource $handle
     * @return bool
     */
    private function lock($filename, $handle)
    {
        if ( ! isset($this->fileLocks[$filename])) {
            $success = flock($handle, LOCK_EX);
            if ($success) {
                $this->fileLocks[$filename] = $handle;
            }
            return $success;
        }
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function unlock($filename)
    {
        if (isset($this->fileLocks[$filename])) {
            $success = flock($this->fileLocks[$filename], LOCK_UN);
            if ($success) {
                unset($this->fileLocks[$filename]);
            }
            return $success;
        }
    }

    /**
     * @param string $baseStoragePath
     * @return void
     */
    public function setStoragePath($baseStoragePath)
    {
        $this->storagePath = $baseStoragePath;
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storagePath;
    }

    /**
     * @param string $fileExtension
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * @param int $fileModeBits
     * @return void
     */
    public function setFileModeBits($fileModeBits)
    {
        $this->fileModeBits = $fileModeBits;
    }

    /**
     * @return int
     */
    public function getFileModeBits()
    {
        return $this->fileModeBits;
    }

    /**
     * @param boolean $useNamespaceInPath
     * @return void
     */
    public function setUseNamespaceInPath($useNamespaceInPath)
    {
        $this->useNamespaceInPath = $useNamespaceInPath;
    }

    /**
     * @return boolean
     */
    public function getUseNamespaceInPath()
    {
        return $this->useNamespaceInPath;
    }

}
