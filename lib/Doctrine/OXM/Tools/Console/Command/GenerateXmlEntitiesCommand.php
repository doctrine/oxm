<?php
/*
 *  $Id$
 *
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

namespace Doctrine\OXM\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
    Doctrine\OXM\Tools\Console\MetadataFilter,
    Doctrine\OXM\Tools\DocumentGenerator,
    Doctrine\OXM\Tools\DisconnectedClassMetadataFactory;

/**
 * Command to generate xml-entities classes and method stubs from your mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   1.0
 * @version $Revision$
 * @author  
 */
class GenerateXmlEntitiesCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('oxm:generate:xml-entities')
        ->setDescription('Generate xml-entity classes and method stubs from your mapping information.')
        ->setDefinition(array(
            new InputOption(
                'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match xml-entities that should be processed.'
            ),
            new InputArgument(
                'dest-path', InputArgument::REQUIRED, 'The path to generate your xml-entity classes.'
            ),
            new InputOption(
                'generate-annotations', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should generate annotation metadata on xml-entities.', false
            ),
            new InputOption(
                'generate-methods', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should generate stub methods on xml-entities.', true
            ),
            new InputOption(
                'regenerate-xml-entities', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should regenerate xml-entity if it exists.', false
            ),
            new InputOption(
                'update-xml-entities', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should only update xml-entity if it exists.', true
            ),
            new InputOption(
                'extend', null, InputOption::VALUE_OPTIONAL,
                'Defines a base class to be extended by generated xml-entity classes.'
            ),
            new InputOption(
                'num-spaces', null, InputOption::VALUE_OPTIONAL,
                'Defines the number of indentation spaces', 4
            )
        ))
        ->setHelp(<<<EOT
Generate xml-entity classes and method stubs from your mapping information.

If you use the <comment>--update-xml-entities</comment> or <comment>--regenerate-xml-entities</comment> flags your exisiting
code gets overwritten. The XmlEntityGenerator will only append new code to your
file and will not delete the old code. However this approach may still be prone
to error and we suggest you use code repositories such as GIT or SVN to make
backups of your code.

It makes sense to generate the xml-entity code if you are using xml-entities as Data
Access Objects only and dont put much additional logic on them. If you are
however putting much more logic on the xml-entities you should refrain from using
the xml-entity-generator and code your xml-entities manually.

<error>Important:</error> Even if you specified Inheritance options in your
XML or YAML Mapping files the generator cannot generate the base and
child classes for you correctly, because it doesn't know which
class is supposed to extend which. You have to adjust the xml-entity
code manually for inheritance to work!
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $xem = $this->getHelper('xem')->getXmlEntityManager();
        
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setXmlEntityManager($xem);
        $cmf->setConfiguration($xem->getConfiguration());
        $metadatas = $cmf->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));
        
        // Process destination directory
        $destPath = realpath($input->getArgument('dest-path'));

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("XmlEntities destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } else if ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("XmlEntities destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            // Create XmlEntityGenerator
            $xmlEntityGenerator = new XmlEntityGenerator();

            $xmlEntityGenerator->setGenerateAnnotations($input->getOption('generate-annotations'));
            $xmlEntityGenerator->setGenerateStubMethods($input->getOption('generate-methods'));
            $xmlEntityGenerator->setRegenerateXmlEntityIfExists($input->getOption('regenerate-xml-entities'));
            $xmlEntityGenerator->setUpdateXmlEntityIfExists($input->getOption('update-xml-entities'));
            $xmlEntityGenerator->setNumSpaces($input->getOption('num-spaces'));

            if (($extend = $input->getOption('extend')) !== null) {
                $xmlEntityGenerator->setClassToExtend($extend);
            }

            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing xml-entity "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
            }

            // Generating XmlEntities
            $xmlEntityGenerator->generate($metadatas, $destPath);

            // Outputting information message
            $output->write(PHP_EOL . sprintf('XmlEntity classes generated to "<info>%s</INFO>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }
    }
}