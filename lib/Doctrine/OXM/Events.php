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

/**
 * Container for all OXM events.
 *
 * This class cannot be instantiated.
 */
final class Events
{
    private function __construct() {}
    /**
     * The preMarshal event occurs for a given xml entity before the respective
     * Marshaller marshal operation for that entity is executed.
     * 
     * This is an xml entity lifecycle event.
     * 
     * @var string
     */
    const preMarshal = 'preMarshal';
    
    /**
     * The postMarshal event occurs for an entity after the entity has
     * been marshalled. It will be invoked after the Marshaller has completed
     * marshalling.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const postMarshal = 'postMarshal';

    /**
     * The preUnmarshal event occurs for a given entity before the respective
     * Marshaller unmarshal operation for that entity is executed.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const preUnmarshal = 'preUnmarshal';

    /**
     * The postUnmarshal event occurs for an entity after the entity has
     * been unmarshalled. It will be invoked after the Marshaller unmarshals xml
     * to the entity.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const postUnmarshal = 'postUnmarshal';

    /**
     * The loadClassMetadata event occurs after the mapping metadata for a class
     * has been loaded from a mapping source (annotations/xml/yaml).
     * 
     * @var string
     */
    const loadClassMetadata = 'loadClassMetadata';
}