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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\OXM\Tests\OXM\Storage;

use \Doctrine\OXM\Storage\FileSystemStorage,
    \Doctrine\Tests\OxmTestCase;


class FileSystemStorageTest extends OxmTestCase
{
    /**
     * @var \Doctrine\OXM\Storage\FileSystemStorage
     */
    protected $fileSystem;

    public function setUp()
    {
        $this->fileSystem = new \Doctrine\OXM\Storage\FileSystemStorage(__DIR__ . '/../Workspace');
    }

    /**
     * @test
     */
    public function itShouldSaveToSpecifiedDirectory()
    {
        $calvin = $this->_getClassMetadataMock('Calvin');
        $this->fileSystem->insert($calvin, 1, 'Spaceman Spiff');

        $this->assertTrue(is_file(__DIR__ . '/../Workspace/Calvin/1.xml'));
        $this->assertEquals('Spaceman Spiff', file_get_contents(__DIR__ . '/../Workspace/Calvin/1.xml'));
        @unlink(__DIR__ . '/../Workspace/Calvin/1.xml');
        @rmdir(__DIR__ . '/../Workspace/Calvin');
    }


    /**
     * @test
     */
    public function itShouldSaveWithCustomExtension()
    {
        $this->fileSystem->setFileExtension('hobbes');
        $calvin = $this->_getClassMetadataMock('Calvin');
        $this->fileSystem->insert($calvin, 1, 'Spaceman Spiff');

        $this->assertTrue(is_file(__DIR__ . '/../Workspace/Calvin/1.hobbes'));
        $this->assertEquals('Spaceman Spiff', file_get_contents(__DIR__ . '/../Workspace/Calvin/1.hobbes'));
        @unlink(__DIR__ . '/../Workspace/Calvin/1.hobbes');
        @rmdir(__DIR__ . '/../Workspace/Calvin');
    }

    /**
     * @test
     */
    public function itShouldSaveWithFullNamespaceSupport()
    {
        $calvin = $this->_getClassMetadataMock('Calvin\\Hobbes');
        $this->fileSystem->insert($calvin, 1, 'Spaceman Spiff');

        $this->assertTrue(is_file(__DIR__ . '/../Workspace/Calvin/Hobbes/1.xml'));
        $this->assertEquals('Spaceman Spiff', file_get_contents(__DIR__ . '/../Workspace/Calvin/Hobbes/1.xml'));
        @unlink(__DIR__ . '/../Workspace/Calvin/Hobbes/1.xml');
        @rmdir(__DIR__ . '/../Workspace/Calvin/Hobbes');
        @rmdir(__DIR__ . '/../Workspace/Calvin');
    }
}

