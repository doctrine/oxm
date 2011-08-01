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

namespace Doctrine\OXM\Tools\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Used by CLI Tools to restrict entity-based commands to given patterns.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Igor Golovanov <igor.golovanov@gmail.com>
 */
class ConsoleRunner
{
    /**
     * Run console with the given helperset.
     * 
     * @param \Symfony\Component\Console\Helper\HelperSet $helperSet
     * @return void
     */
    static public function run(HelperSet $helperSet)
    {
        $cli = new Application('Doctrine OXM Command Line Interface', \Doctrine\OXM\Version::VERSION);
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        self::addCommands($cli);
        $cli->run();
    }

    /**
     * @param Application $cli
     */
    static public function addCommands(Application $cli)
    {
        $cli->addCommands(array(
            // OXM Commands
            new \Doctrine\OXM\Tools\Console\Command\ClearCache\MetadataCommand(),
            new \Doctrine\OXM\Tools\Console\Command\GenerateProxiesCommand(),
            new \Doctrine\OXM\Tools\Console\Command\GenerateRepositoriesCommand(),
            new \Doctrine\OXM\Tools\Console\Command\GenerateXmlEntitiesCommand(),
        ));
    }
}