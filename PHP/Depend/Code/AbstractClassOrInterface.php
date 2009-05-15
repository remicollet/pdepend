<?php
/**
 * This file is part of PHP_Depend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2009, Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2009 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://pdepend.org/
 */

require_once 'PHP/Depend/Code/AbstractType.php';
require_once 'PHP/Depend/Util/UUID.php';

/**
 * Represents an interface or a class type.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage Code
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2008-2009 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://pdepend.org/
 */
abstract class PHP_Depend_Code_AbstractClassOrInterface
    extends PHP_Depend_Code_AbstractType
{
    /**
     * List of {@link PHP_Depend_Code_AbstractClassOrInterface} objects this
     * type depends on.
     *
     * @var array(PHP_Depend_Code_AbstractClassOrInterface) $_dependencies
     */
    private $_dependencies = array();

    /**
     * The parent for this class node.
     *
     * @var PHP_Depend_Code_ClassReference $_parentClassReference
     * @since 0.9.5
     */
    private $_parentClassReference = null;

    /**
     * List of all interfaces implemented/extended by the this type.
     *
     * @var array(PHP_Depend_Code_InterfaceReference) $_interfaceReferences
     */
    private $_interfaceReferences = array();

    /**
     * The parent package for this class.
     *
     * @var PHP_Depend_Code_Package $_package
     */
    private $_package = null;

    /**
     * List of {@link PHP_Depend_Code_Method} objects in this class.
     *
     * @var array(PHP_Depend_Code_Method) $_methods
     */
    private $_methods = array();

    /**
     * The tokens for this type.
     *
     * @var array(array) $_tokens
     */
    private $_tokens = array();

    /**
     * List of {@link PHP_Depend_Code_TypeConstant} objects that belong to this
     * type.
     *
     * @var array(PHP_Depend_Code_TypeConstant) $_constants
     */
    private $_constants = array();

    /**
     * This property will indicate that the class or interface is user defined.
     * The parser marks all classes and interfaces as user defined that have a
     * source file and were part of parsing process.
     *
     * @var boolean $_userDefined
     * @since 0.9.5
     */
    private $_userDefined = false;

    /**
     * This method will return <b>true</b> when this type has a declaration in
     * the analyzed source files.
     *
     * @return boolean
     * @since 0.9.5
     */
    public function isUserDefined()
    {
        return $this->_userDefined;
    }

    /**
     * This method can be used to mark a type as user defined. User defined
     * means that the type has a valid declaration in the analyzed source files.
     *
     * @return void
     * @since 0.9.5
     */
    public function setUserDefined()
    {
        $this->_userDefined = true;
    }

    /**
     * Returns the parent class or <b>null</b> if this class has no parent.
     *
     * @return PHP_Depend_Code_Class
     */
    public function getParentClass()
    {
        // No parent? Stop here!
        if ($this->_parentClassReference === null) {
            return null;
        }

        $parentClass = $this->_parentClassReference->getType();

        // Check parent against global filter
        $collection = PHP_Depend_Code_Filter_Collection::getInstance();
        if ($collection->accept($parentClass) === false) {
            return null;
        }

        // Parent is valid, so return
        return $parentClass;
    }

    /**
     * Sets a reference onto the parent class of this class node.
     *
     * @param PHP_Depend_Code_ClassReference $classReference Reference to the
     *        declared parent class.
     *
     * @return void
     * @since 0.9.5
     */
    public function setParentClassReference(
        PHP_Depend_Code_ClassReference $classReference
    ) {
        $this->_parentClassReference = $classReference;
    }

    /**
     * Returns a node iterator with all implemented interfaces.
     *
     * @return PHP_Depend_Code_NodeIterator
     * @since 0.9.5
     */
    public function getInterfaces()
    {
        $interfaces = array();
        foreach ($this->_interfaceReferences as $interfaceReference) {
            $interface = $interfaceReference->getType();
            if (in_array($interface, $interfaces, true) === true) {
                continue;
            }
            $interfaces[] = $interface;
            foreach ($interface->getInterfaces() as $parentInterface) {
                if (in_array($parentInterface, $interfaces, true) === false) {
                    $interfaces[] = $parentInterface;
                }
            }
        }

        if ($this->_parentClassReference === null) {
            return new PHP_Depend_Code_NodeIterator($interfaces);
        }

        $parentClass = $this->_parentClassReference->getType();
        foreach ($parentClass->getInterfaces() as $interface) {
            $interfaces[] = $interface;
        }
        return new PHP_Depend_Code_NodeIterator($interfaces);
    }

    /**
     * Adds a interface reference node.
     *
     * @param PHP_Depend_Code_InterfaceReference $interfaceReference The extended
     *        or implemented interface reference.
     *
     * @return void
     * @since 0.9.5
     */
    public function addInterfaceReference(
        PHP_Depend_Code_InterfaceReference $interfaceReference
    ) {
        $this->_interfaceReferences[] = $interfaceReference;
    }

    /**
     * Returns all {@link PHP_Depend_Code_TypeConstant} objects in this type.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getConstants()
    {
        return new PHP_Depend_Code_NodeIterator($this->_constants);
    }

    /**
     * Adds the given constant to this type.
     *
     * @param PHP_Depend_Code_TypeConstant $constant A new type constant.
     *
     * @return PHP_Depend_Code_TypeConstant
     */
    public function addConstant(PHP_Depend_Code_TypeConstant $constant)
    {
        if ($constant->getParent() !== null) {
            $constant->getParent()->removeConstant($constant);
        }
        // Set this as owner type
        $constant->setParent($this);
        // Store constant
        $this->_constants[] = $constant;

        return $constant;
    }

    /**
     * Removes the given constant from this type.
     *
     * @param PHP_Depend_Code_TypeConstant $constant The constant to remove.
     *
     * @return void
     */
    public function removeConstant(PHP_Depend_Code_TypeConstant $constant)
    {
        if (($i = array_search($constant, $this->_constants, true)) !== false) {
            // Remove this as owner
            $constant->setParent(null);
            // Remove from internal list
            unset($this->_constants[$i]);
        }
    }

    /**
     * Returns all {@link PHP_Depend_Code_Method} objects in this type.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getMethods()
    {
        return new PHP_Depend_Code_NodeIterator($this->_methods);
    }

    /**
     * Adds the given method to this type.
     *
     * @param PHP_Depend_Code_Method $method A new type method.
     *
     * @return PHP_Depend_Code_Method
     */
    public function addMethod(PHP_Depend_Code_Method $method)
    {
        if ($method->getParent() !== null) {
            $method->getParent()->removeMethod($method);
        }
        // Set this as owner type
        $method->setParent($this);
        // Store method
        $this->_methods[] = $method;

        return $method;
    }

    /**
     * Removes the given method from this class.
     *
     * @param PHP_Depend_Code_Method $method The method to remove.
     *
     * @return void
     */
    public function removeMethod(PHP_Depend_Code_Method $method)
    {
        if (($i = array_search($method, $this->_methods, true)) !== false) {
            // Remove this as owner
            $method->setParent(null);
            // Remove from internal list
            unset($this->_methods[$i]);
        }
    }

    /**
     * Returns all {@link PHP_Depend_Code_AbstractClassOrInterface} objects this
     * type depends on.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getDependencies()
    {
        $references = $this->_interfaceReferences;
        if ($this->_parentClassReference !== null) {
            $references[] = $this->_parentClassReference;
        }

        return new PHP_Depend_Code_ClassOrInterfaceReferenceIterator($references);
    }

    /**
     * Returns an <b>array</b> with all tokens within this type.
     *
     * @return array(PHP_Depend_Token)
     */
    public function getTokens()
    {
        $storage = PHP_Depend_StorageRegistry::get(PHP_Depend::TOKEN_STORAGE);
        return (array) $storage->restore($this->getUUID(), 'tokens-type');
    }

    /**
     * Sets the tokens for this type.
     *
     * @param array(PHP_Depend_Token) $tokens The generated tokens.
     *
     * @return void
     */
    public function setTokens(array $tokens)
    {
        $storage = PHP_Depend_StorageRegistry::get(PHP_Depend::TOKEN_STORAGE);
        $storage->store($tokens, $this->getUUID(), 'tokens-type');
    }

    /**
     * Returns the parent package for this class.
     *
     * @return PHP_Depend_Code_Package
     */
    public function getPackage()
    {
        return $this->_package;
    }

    /**
     * Sets the parent package for this class.
     *
     * @param PHP_Depend_Code_Package $package The parent package.
     *
     * @return void
     */
    public function setPackage(PHP_Depend_Code_Package $package = null)
    {
        $this->_package = $package;
    }

    /**
     * Returns <b>true</b> if this is an abstract class or an interface.
     *
     * @return boolean
     */
    public abstract function isAbstract();

    /**
     * Checks that this user type is a subtype of the given <b>$type</b>
     * instance.
     *
     * @param PHP_Depend_Code_AbstractClassOrInterface $type The possible parent
     *        type instance.
     *
     * @return boolean
     */
    public abstract function isSubtypeOf(
        PHP_Depend_Code_AbstractClassOrInterface $type
    );

    /**
     * Returns the declared modifiers for this type.
     *
     * @return integer
     */
    public abstract function getModifiers();

    // DEPRECATED METHODS AND PROPERTIES
    // @codeCoverageIgnoreStart

    /**
     * Adds the given {@link PHP_Depend_Code_AbstractClassOrInterface} object as
     * dependency.
     *
     * @param PHP_Depend_Code_AbstractClassOrInterface $type A type this
     *        function depends on.
     *
     * @return void
     * @deprecated Since version 0.9.5, use addParentClassReference() and
     *             addInterfaceReference() instead.
     */
    public function addDependency(PHP_Depend_Code_AbstractClassOrInterface $type)
    {
        fwrite(STDERR, 'Since 0.9.5 ' . __METHOD__ . '() is deprecated.' . PHP_EOL);
        if (array_search($type, $this->_dependencies, true) === false) {
            // Store type dependency
            $this->_dependencies[] = $type;
        }
    }

    /**
     * Removes the given {@link PHP_Depend_Code_AbstractClassOrInterface} object
     * from the dependency list.
     *
     * @param PHP_Depend_Code_AbstractClassOrInterface $type A type to remove.
     *
     * @return void
     * @deprecated Since version 0.9.5
     */
    public function removeDependency(PHP_Depend_Code_AbstractClassOrInterface $type)
    {
        fwrite(STDERR, 'Since 0.9.5 ' . __METHOD__ . '() is deprecated.' . PHP_EOL);
        if (($i = array_search($type, $this->_dependencies, true)) !== false) {
            // Remove from internal list
            unset($this->_dependencies[$i]);
        }
    }

    /**
     * Returns an unfiltered, raw array of
     * {@link PHP_Depend_Code_AbstractClassOrInterface} objects this type
     * depends on. This method is only for internal usage.
     *
     * @return array(PHP_Depend_Code_AbstractClassOrInterface)
     * @access private
     */
    public function getUnfilteredRawDependencies()
    {
        fwrite(STDERR, 'Since 0.9.5 ' . __METHOD__ . '() is deprecated.' . PHP_EOL);
        $dependencies = $this->_dependencies;
        foreach ($this->_interfaceReferences as $interfaceReference) {
            $interface = $interfaceReference->getType();
            if (in_array($interface, $dependencies, true) === false) {
                $dependencies[] = $interface;
            }
        }

        // No parent? Then use the parent implementation
        if ($this->getParentClass() === null) {
            return $dependencies;
        }

        $dependencies[] = $this->getParentClass();

        return $dependencies;
    }
    
    // @codeCoverageIgnoreEnd
    
}