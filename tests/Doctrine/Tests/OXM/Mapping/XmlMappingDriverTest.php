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

namespace Doctrine\Tests\OXM\Mapping;

use Doctrine\OXM\Mapping\ClassMetadata;
use Doctrine\OXM\Mapping\ClassMetadataInfo;
use Doctrine\OXM\Mapping\Driver\XmlDriver;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    /**
     * @return \Doctrine\OXM\Mapping\Driver\Driver
     */
    protected function _loadDriver()
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    /**
     * @param string $xmlMappingFile
     * @dataProvider dataValidSchema
     */
    public function testValidateXmlSchema($xmlMappingFile)
    {
        $xsdSchemaFile = __DIR__ . "/../../../../../doctrine-mapping.xsd";

        $dom = new \DOMDocument('UTF-8');
        $dom->load($xmlMappingFile);
        $this->assertTrue($dom->schemaValidate($xsdSchemaFile));
    }

    static public function dataValidSchema()
    {
        return array(
//            array(__DIR__ . "/xml/Doctrine.Tests.OXM.Mapping.CTI.dcm.xml"),
            array(__DIR__ . "/xml/Doctrine.Tests.OXM.Mapping.User.dcm.xml"),
            array(__DIR__ . "/xml/Doctrine.Tests.OXM.Mapping.Role.dcm.xml"),
//            array(__DIR__ . "/xml/CatNoId.dcm.xml"),
        );
    }
}
