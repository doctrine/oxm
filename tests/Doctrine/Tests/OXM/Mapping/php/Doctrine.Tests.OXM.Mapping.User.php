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

use Doctrine\OXM\Mapping\ClassMetadataInfo;

$metadata->setXmlName('cms-user');
$metadata->isRoot = true;

$metadata->setXmlNamespaces(array(
    array('url' => 'http://www.schema.com/foo', 'prefix' => 'foo'),
    array('url' => 'http://www.schema.com/bar', 'prefix' => 'bar')
));

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
$metadata->addLifecycleCallback('doStuffOnPreMarshal', 'preMarshal');

$metadata->mapField(array(
    'fieldName' => 'id',
    'id' => true,
    'type' => 'string',
    'node' => 'attribute'
));
$metadata->mapField(array(
    'fieldName' => 'name',
    'type' => 'string',
    'node' => 'text',
    'required' => true,
    'setMethod' => 'setUsername',
    'getMethod' => 'getUsername'
));

$metadata->mapField(array(
    'fieldName' => 'comments',
    'type' => 'string',
    'node' => 'text',
    'collection' => true,
    'wrapper' => 'comments',
    'name' => 'comment',
));

$metadata->mapField(array(
    'fieldName' => 'roles',
    'type' => 'Role',
    'node' => 'value',
    'collection' => true,
    'name' => 'role',
));
