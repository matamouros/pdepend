<?php
/**
 * This file is part of PHP_Depend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@pdepend.org>.
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
 * @category  QualityAssurance
 * @package   PHP_Depend
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://pdepend.org/
 */

require_once dirname(__FILE__) . '/../AbstractTest.php';

/**
 * Test case implementation for the default node builder implementation.
 *
 * @category  QualityAssurance
 * @package   PHP_Depend
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://pdepend.org/
 *
 * @covers PHP_Depend_Builder_Default
 * @group pdepend
 * @group pdepend::builder
 * @group unittest
 */
class PHP_Depend_Builder_DefaultTest extends PHP_Depend_AbstractTest
{
    /**
     * testBuilderAddsMultiplePackagesForClassesToListOfPackages
     *
     * @return void
     */
    public function testBuilderAddsMultiplePackagesForClassesToListOfPackages()
    {
        $builder = $this->createBuilder();

        $package = $builder->buildPackage(__FUNCTION__);
        $package->addType($builder->buildClass(__FUNCTION__));

        $package = $builder->buildPackage(__CLASS__);
        $package->addType($builder->buildClass(__CLASS__));

        $this->assertEquals(2, $builder->getPackages()->count());
    }

    /**
     * testBuilderAddsMultiplePackagesForFunctionsToListOfPackages
     *
     * @return void
     */
    public function testBuilderAddsMultiplePackagesForFunctionsToListOfPackages()
    {
        $builder = $this->createBuilder();

        $package = $builder->buildPackage(__FUNCTION__);
        $builder->buildFunction(__FUNCTION__);

        $package = $builder->buildPackage(__CLASS__);
        $builder->buildFunction(__CLASS__);

        $this->assertEquals(2, $builder->getPackages()->count());
    }

    /**
     * testBuilderNotAddsNewPackagesOnceItHasReturnedTheListOfPackages
     *
     * @return void
     */
    public function testBuilderNotAddsNewPackagesOnceItHasReturnedTheListOfPackages()
    {
        $builder = $this->createBuilder();

        $package = $builder->buildPackage(__FUNCTION__);
        $package->addFunction($builder->buildFunction(__FUNCTION__));

        $builder->getPackages();

        $package = $builder->buildPackage(__CLASS__);
        $package->addType($builder->buildClass(__CLASS__));

        $this->assertEquals(1, $builder->getPackages()->count());
    }

    /**
     * testRestoreFunctionAddsFunctionToPackage
     *
     * @return void
     */
    public function testRestoreFunctionAddsFunctionToPackage()
    {
        $builder = $this->createBuilder();
        $package = $builder->buildPackage(__CLASS__);

        $function = new PHP_Depend_Code_Function(__FUNCTION__);
        $function->setPackage($package);

        $builder->restoreFunction($function);
        self::assertEquals(1, count($package->getFunctions()));
    }

    /**
     * testRestoreFunctionUsesGetPackageNameMethod
     *
     * @return void
     */
    public function testRestoreFunctionUsesGetPackageNameMethod()
    {
        $function = $this->getMock(
            PHP_Depend_Code_Function::CLAZZ, array(), array(__FUNCTION__)
        );
        $function->expects($this->once())
            ->method('getPackageName');

        $builder = $this->createBuilder();
        $builder->restoreFunction($function);
    }

    /**
     * Tests that the node builder creates a class for the same name only once.
     *
     * @return void
     */
    public function testBuildClassUnique()
    {
        $builder = $this->createBuilder();

        $class = $builder->buildClass(__FUNCTION__);
        $class->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreClass($class);

        self::assertSame($class, $builder->getClass(__FUNCTION__));
    }

    /**
     * Tests that the {@link PHP_Depend_Builder_Default::buildClass()} method
     * creates two different class instances for the same class name, but
     * different packages.
     *
     * @return void
     */
    public function testBuildClassCreatesTwoDifferentInstancesForDifferentPackages()
    {
        $builder = $this->createBuilder();

        $class1 = $builder->buildClass('php\depend1\Parser');
        $class2 = $builder->buildClass('php\depend2\Parser');

        $this->assertNotSame($class1, $class2);
    }

    /**
     * Tests that {@link PHP_Depend_Builder_Default::buildClass()} returns
     * a previous class instance for a specified package, if it is called for a
     * same named class in the default package.
     *
     * @return void
     */
    public function testBuildClassReusesExistingNonDefaultPackageInstanceForDefaultPackage()
    {
        $builder = $this->createBuilder();

        $class1 = $builder->buildClass('php\depend\Parser');
        $class1->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreClass($class1);

        self::assertSame(
            $class1->getPackage(),
            $builder->getClass('Parser')->getPackage()
        );
    }

    /**
     * Tests that the node build generates an unique interface instance for the
     * same identifier.
     *
     * @return void
     */
    public function testBuildInterfaceUnique()
    {
        $builder = $this->createBuilder();

        $interface = $builder->buildInterface(__FUNCTION__);
        $interface->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreInterface($interface);

        self::assertSame($interface, $builder->getInterface(__FUNCTION__));
    }

    /**
     * Tests that the {@link PHP_Depend_Builder_Default::buildInterface()}
     * method only removes/replaces a previously created class instance, when
     * this class is part of the default namespace. Otherwise there are two user
     * types with the same local or package internal name.
     *
     * @return void
     */
    public function testBuildInterfaceDoesntRemoveClassForSameNamedInterface()
    {
        $builder = $this->createBuilder();

        $package1 = $builder->buildPackage('package1');
        $package2 = $builder->buildPackage('package2');

        $class = $builder->buildClass('Parser');
        $package1->addType($class);

        $this->assertEquals(1, $package1->getTypes()->count());

        $interface = $builder->buildInterface('Parser');

        $this->assertEquals(1, $package1->getTypes()->count());
    }

    /**
     * Tests that {@link PHP_Depend_Builder_Default::buildInterface()} creates
     * different interface instances for different parent packages.
     *
     * @return void
     */
    public function testBuildInterfacesCreatesDifferentInstancesForDifferentPackages()
    {
        $builder = $this->createBuilder();

        $interfaces1 = $builder->buildInterface('php\depend1\ParserI');
        $interfaces2 = $builder->buildInterface('php\depend2\ParserI');

        $this->assertNotSame($interfaces1, $interfaces2);
    }

    /**
     * Tests that {@link PHP_Depend_Builder_Default::buildInterface()}
     * replaces an existing default package interface instance, if it creates a
     * more specific version.
     *
     * @return void
     */
    public function testCanCreateMultipleInterfaceInstancesWithIdenticalNames()
    {
        $builder = $this->createBuilder();

        $interface1 = $builder->buildInterface('php\depend\ParserI');
        $interface2 = $builder->buildInterface('php\depend\ParserI');

        $this->assertNotSame($interface1, $interface2);
        self::assertSame(
            $interface1->getPackage(),
            $interface2->getPackage()
        );
    }

    /**
     * Tests that {@link PHP_Depend_Builder_Default::buildInterface()} returns
     * a previous interface instance for a specified package, if it is called
     * for a same named interface in the default package.
     *
     * @return void
     */
    public function testBuildInterfaceReusesExistingNonDefaultPackageInstanceForDefaultPackage()
    {
        $builder = $this->createBuilder();

        $interface = $builder->buildInterface('php\depend\ParserI');
        $interface->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreInterface($interface);

        self::assertSame($builder->getInterface('ParserI'), $interface);
        self::assertSame(
            $builder->getInterface('ParserI')->getPackage(),
            $interface->getPackage()
        );
    }

    /**
     * Tests the PHP_Depend_Code_Method build method.
     *
     * @return void
     */
    public function testBuildMethod()
    {
        $this->assertInstanceOf(
            'PHP_Depend_Code_Method',
            $this->createBuilder()->buildMethod('method')
        );
    }

    /**
     * Tests that the node builder creates a package for the same name only once.
     *
     * @return void
     */
    public function testBuildPackageUnique()
    {
        $builder  = $this->createBuilder();
        $package1 = $builder->buildPackage('package1');
        $package2 = $builder->buildPackage('package1');

        self::assertSame($package1, $package2);
    }

    /**
     * Tests the implemented {@link IteratorAggregate}.
     *
     * @return void
     */
    public function testGetIteratorWithPackages()
    {
        $builder = $this->createBuilder();

        $packages = array(
            'package1'  =>  $builder->buildPackage('package1'),
            'package2'  =>  $builder->buildPackage('package2'),
            'package3'  =>  $builder->buildPackage('package3')
        );

        foreach ($builder as $name => $package) {
            self::assertSame($packages[$name], $package);
        }
    }

    /**
     * Tests the {@link PHP_Depend_Builder_Default::getPackages()} method.
     *
     * @return void
     */
    public function testGetPackages()
    {
        $builder = $this->createBuilder();

        $packages = array(
            'package1'  =>  $builder->buildPackage('package1'),
            'package2'  =>  $builder->buildPackage('package2'),
            'package3'  =>  $builder->buildPackage('package3')
        );

        foreach ($builder->getPackages() as $name => $package) {
            self::assertSame($packages[$name], $package);
        }
    }

    /**
     * There was a missing check within an if statement, so that the builder
     * has alway overwritten previously created instances.
     *
     * @return void
     */
    public function testBuildClassDoesNotOverwritePreviousInstances()
    {
        $builder = $this->createBuilder();

        $class0 = $builder->buildClass('FooBar');
        $class0->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreClass($class0);

        $class1 = $builder->buildClass('FooBar');
        $class1->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreClass($class1);

        $this->assertNotSame($class0, $class1);
        self::assertSame($class0, $builder->getClass('FooBar'));
    }

    /**
     * There was a missing check within an if statement, so that the builder
     * has alway overwritten previously created instances.
     *
     * @return void
     */
    public function testBuildInterfaceDoesNotOverwritePreviousInstances()
    {
        $builder = $this->createBuilder();

        $interface = $builder->buildInterface('FooBar');
        $interface->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreInterface($interface);

        $this->assertNotSame($interface, $builder->buildInterface('FooBar'));
        self::assertSame($interface, $builder->getInterface('FooBar'));
    }

    /**
     * Tests that the node builder works case insensitive for class names.
     *
     * @return void
     */
    public function testBuildClassWorksCaseInsensitiveIssue26()
    {
        $builder = $this->createBuilder();

        $class = $builder->buildClass('PHP_Depend_Parser');
        $class->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreClass($class);

        self::assertSame($class, $builder->getClass('php_Depend_parser'));
    }

    /**
     * Tests that the node builder works case insensitive for interface names.
     *
     * @return void
     */
    public function testBuildInterfaceWorksCaseInsensitiveIssue26()
    {
        $builder = $this->createBuilder();

        $interface = $builder->buildInterface('PHP_Depend_TokenizerI');
        $interface->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreInterface($interface);

        self::assertSame(
            $interface,
            $builder->getInterface('php_Depend_tokenizeri')
        );
    }

    /**
     * Tests that the node builder works case insensitive for interface names.
     *
     * @return void
     */
    public function testBuildClassOrInterfaceWorksCaseInsensitive1Issue26()
    {
        $builder = $this->createBuilder();

        $interface = $builder->buildInterface('PHP_Depend_TokenizerI');
        $interface->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreInterface($interface);

        self::assertSame(
            $interface,
            $builder->getClassOrInterface('php_Depend_tokenizeri')
        );
    }

    /**
     * Tests that the node builder works case insensitive for interface names.
     *
     * @return void
     */
    public function testBuildClassOrInterfaceWorksCaseInsensitive2Issue26()
    {
        $builder = $this->createBuilder();

        $class = $builder->buildClass('PHP_Depend_Parser');
        $class->setPackage($builder->buildPackage(__FUNCTION__));

        $builder->restoreClass($class);

        self::assertSame($class, $builder->getClassOrInterface('php_Depend_parser'));
    }

    /**
     * Tests that the builder throws the expected exception when some one tries
     * to build a new node, when the internal state flag is frozen.
     *
     * @return void
     */
    public function testBuildASTClassOrInterfaceReferenceThrowsExpectedExceptionWhenStateIsFrozen()
    {
        $builder = $this->createBuilder();
        $builder->buildASTClassOrInterfaceReference('Foo');

        // Freeze object
        $builder->getClass('Foo');

        $this->setExpectedException(
            'BadMethodCallException',
            'Cannot create new nodes, when internal state is frozen.'
        );

        $builder->buildASTClassOrInterfaceReference('Bar');
    }

    /**
     * Tests that the builder throws the expected exception when some one tries
     * to build a new node, when the internal state flag is frozen.
     *
     * @return void
     */
    public function testBuildClassThrowsExpectedExceptionWhenStateIsFrozen()
    {
        $builder = $this->createBuilder();
        $builder->buildClass('Foo');

        // Freeze object
        $builder->getClass('Foo');

        $this->setExpectedException(
            'BadMethodCallException',
            'Cannot create new nodes, when internal state is frozen.'
        );

        $builder->buildClass('Bar');
    }

    /**
     * Tests that the builder throws the expected exception when some one tries
     * to build a new node, when the internal state flag is frozen.
     *
     * @return void
     */
    public function testBuildASTClassReferenceThrowsExpectedExceptionWhenStateIsFrozen()
    {
        $builder = $this->createBuilder();
        $builder->buildASTClassReference('Foo');

        // Freeze object
        $builder->getClass('Foo');

        $this->setExpectedException(
            'BadMethodCallException',
            'Cannot create new nodes, when internal state is frozen.'
        );

        $builder->buildASTClassReference('Bar');
    }

    /**
     * Tests that the builder throws the expected exception when some one tries
     * to build a new node, when the internal state flag is frozen.
     *
     * @return void
     */
    public function testBuildInterfaceThrowsExpectedExceptionWhenStateIsFrozen()
    {
        $builder = $this->createBuilder();
        $builder->buildInterface('Inter');

        // Freeze object
        $builder->getInterface('Inter');

        $this->setExpectedException(
            'BadMethodCallException',
            'Cannot create new nodes, when internal state is frozen.'
        );

        $builder->buildInterface('Face');
    }

    /**
     * Tests that the builder throws the expected exception when some one tries
     * to build a new node, when the internal state flag is frozen.
     *
     * @return void
     */
    public function testBuildMethodThrowsExpectedExceptionWhenStateIsFrozen()
    {
        $builder = $this->createBuilder();
        $builder->buildMethod('call');

        // Freeze object
        $builder->getInterface('Inter');

        $this->setExpectedException(
            'BadMethodCallException',
            'Cannot create new nodes, when internal state is frozen.'
        );

        $builder->buildMethod('invoke');
    }

    /**
     * Tests that the builder throws the expected exception when some one tries
     * to build a new node, when the internal state flag is frozen.
     *
     * @return void
     */
    public function testBuildFunctionThrowsExpectedExceptionWhenStateIsFrozen()
    {
        $builder = $this->createBuilder();
        $builder->buildFunction('func');

        // Freeze object
        $builder->getInterface('Inter');

        $this->setExpectedException(
            'BadMethodCallException',
            'Cannot create new nodes, when internal state is frozen.'
        );

        $builder->buildFunction('prop');
    }

    /**
     * testBuildASTCommentReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTCommentReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTComment::CLAZZ,
            $this->createBuilder()->buildASTComment('// Hello')
        );
    }

    /**
     * testBuildASTPrimitiveTypeReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTPrimitiveTypeReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTPrimitiveType::CLAZZ,
            $this->createBuilder()->buildASTPrimitiveType('1')
        );
    }

    /**
     * testBuildASTTypeArrayReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTTypeArrayReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTTypeArray::CLAZZ,
            $this->createBuilder()->buildASTTypeArray()
        );
    }

    /**
     * testBuildASTTypeCallableReturnsExpectedType
     *
     * @return void
     * @since 0.11.0
     */
    public function testBuildASTTypeCallableReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTTypeCallable::CLAZZ,
            $this->createBuilder()->buildASTTypeCallable()
        );
    }

    /**
     * testBuildASTHeredocReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTHeredocReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTHeredoc::CLAZZ,
            $this->createBuilder()->buildASTHeredoc()
        );
    }

    /**
     * testBuildASTIdentifierReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTIdentifierReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTIdentifier::CLAZZ,
            $this->createBuilder()->buildASTIdentifier('ID')
        );
    }

    /**
     * testBuildASTLiteralReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTLiteralReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTLiteral::CLAZZ,
            $this->createBuilder()->buildASTLiteral('false')
        );
    }

    /**
     * testBuildASTStringReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTStringReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTString::CLAZZ,
            $this->createBuilder()->buildASTString()
        );
    }

    /**
     * testBuildASTArrayReturnsExpectedType
     *
     * @return void
     * @since 0.11.0
     */
    public function testBuildASTArrayReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTArray::CLAZZ,
            $this->createBuilder()->buildASTArray()
        );
    }

    /**
     * testBuildASTArrayElementReturnsExpectedType
     *
     * @return void
     * @since 0.11.0
     */
    public function testBuildASTArrayElementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTArrayElement::CLAZZ,
            $this->createBuilder()->buildASTArrayElement()
        );
    }

    /**
     * testBuildASTScopeReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTScopeReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTScope::CLAZZ,
            $this->createBuilder()->buildASTScope()
        );
    }

    /**
     * testBuildASTVariableReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTVariableReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTVariable::CLAZZ,
            $this->createBuilder()->buildASTVariable('$name')
        );
    }

    /**
     * testBuildASTVariableVariableReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTVariableVariableReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTVariableVariable::CLAZZ,
            $this->createBuilder()->buildASTVariableVariable('$$x')
        );
    }

    /**
     * testBuildASTCompoundVariableReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTCompoundVariableReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTCompoundVariable::CLAZZ,
            $this->createBuilder()->buildASTCompoundVariable('${x}')
        );
    }

    /**
     * testBuildASTFieldDeclarationReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTFieldDeclarationReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTFieldDeclaration::CLAZZ,
            $this->createBuilder()->buildASTFieldDeclaration()
        );
    }

    /**
     * testBuildASTConstantReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTConstantReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTConstant::CLAZZ,
            $this->createBuilder()->buildASTConstant('X')
        );
    }

    /**
     * testBuildASTConstantDeclaratorReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTConstantDeclaratorReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTConstantDeclarator::CLAZZ,
            $this->createBuilder()->buildASTConstantDeclarator('X')
        );
    }

    /**
     * testBuildASTConstantDefinitionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTConstantDefinitionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTConstantDefinition::CLAZZ,
            $this->createBuilder()->buildASTConstantDefinition('X')
        );
    }

    /**
     * testBuildASTConstantPostfixReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTConstantPostfixReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTConstantPostfix::CLAZZ,
            $this->createBuilder()->buildASTConstantPostfix('X')
        );
    }

    /**
     * testBuildASTAssignmentExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTAssignmentExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTAssignmentExpression::CLAZZ,
            $this->createBuilder()->buildASTAssignmentExpression('=')
        );
    }

    /**
     * testBuildASTBooleanAndExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTBooleanAndExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTBooleanAndExpression::CLAZZ,
            $this->createBuilder()->buildASTBooleanAndExpression()
        );
    }

    /**
     * testBuildASTBooleanOrExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTBooleanOrExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTBooleanOrExpression::CLAZZ,
            $this->createBuilder()->buildASTBooleanOrExpression()
        );
    }

    /**
     * testBuildASTCastExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTCastExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTCastExpression::CLAZZ,
            $this->createBuilder()->buildASTCastExpression('(boolean)')
        );
    }

    /**
     * testBuildASTCloneExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTCloneExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTCloneExpression::CLAZZ,
            $this->createBuilder()->buildASTCloneExpression('clone')
        );
    }

    /**
     * testBuildASTCompoundExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTCompoundExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTCompoundExpression::CLAZZ,
            $this->createBuilder()->buildASTCompoundExpression()
        );
    }

    /**
     * testBuildASTConditionalExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTConditionalExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTConditionalExpression::CLAZZ,
            $this->createBuilder()->buildASTConditionalExpression()
        );
    }

    /**
     * testBuildASTEvalExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTEvalExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTEvalExpression::CLAZZ,
            $this->createBuilder()->buildASTEvalExpression('eval')
        );
    }

    /**
     * testBuildASTExitExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTExitExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTExitExpression::CLAZZ,
            $this->createBuilder()->buildASTExitExpression('exit')
        );
    }

    /**
     * testBuildASTExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTExpression::CLAZZ,
            $this->createBuilder()->buildASTExpression()
        );
    }

    /**
     * testBuildASTIncludeExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTIncludeExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTIncludeExpression::CLAZZ,
            $this->createBuilder()->buildASTIncludeExpression()
        );
    }

    /**
     * testBuildASTInstanceOfExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTInstanceOfExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTInstanceOfExpression::CLAZZ,
            $this->createBuilder()->buildASTInstanceOfExpression('instanceof')
        );
    }

    /**
     * testBuildASTIssetExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTIssetExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTIssetExpression::CLAZZ,
            $this->createBuilder()->buildASTIssetExpression()
        );
    }

    /**
     * testBuildASTListExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTListExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTListExpression::CLAZZ,
            $this->createBuilder()->buildASTListExpression('list')
        );
    }

    /**
     * testBuildASTLogicalAndExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTLogicalAndExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTLogicalAndExpression::CLAZZ,
            $this->createBuilder()->buildASTLogicalAndExpression('AND')
        );
    }

    /**
     * testBuildASTLogicalOrExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTLogicalOrExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTLogicalOrExpression::CLAZZ,
            $this->createBuilder()->buildASTLogicalOrExpression('OR')
        );
    }

    /**
     * testBuildASTLogicalXorExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTLogicalXorExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTLogicalXorExpression::CLAZZ,
            $this->createBuilder()->buildASTLogicalXorExpression('XOR')
        );
    }

    /**
     * testBuildASTRequireExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTRequireExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTRequireExpression::CLAZZ,
            $this->createBuilder()->buildASTRequireExpression()
        );
    }

    /**
     * testBuildASTStringIndexExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTStringIndexExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTStringIndexExpression::CLAZZ,
            $this->createBuilder()->buildASTStringIndexExpression()
        );
    }

    /**
     * testBuildASTUnaryExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTUnaryExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTUnaryExpression::CLAZZ,
            $this->createBuilder()->buildASTUnaryExpression('+')
        );
    }

    /**
     * testBuildASTBreakStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTBreakStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTBreakStatement::CLAZZ,
            $this->createBuilder()->buildASTBreakStatement('break')
        );
    }

    /**
     * testBuildASTCatchStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTCatchStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTCatchStatement::CLAZZ,
            $this->createBuilder()->buildASTCatchStatement('catch')
        );
    }

    /**
     * testBuildASTDeclareStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTDeclareStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTDeclareStatement::CLAZZ,
            $this->createBuilder()->buildASTDeclareStatement()
        );
    }

    /**
     * testBuildASTIfStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTIfStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTIfStatement::CLAZZ,
            $this->createBuilder()->buildASTIfStatement('if')
        );
    }

    /**
     * testBuildASTElseIfStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTElseIfStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTElseIfStatement::CLAZZ,
            $this->createBuilder()->buildASTElseIfStatement('elseif')
        );
    }

    /**
     * testBuildASTContinueStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTContinueStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTContinueStatement::CLAZZ,
            $this->createBuilder()->buildASTContinueStatement('continue')
        );
    }

    /**
     * testBuildASTDoWhileStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTDoWhileStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTDoWhileStatement::CLAZZ,
            $this->createBuilder()->buildASTDoWhileStatement('while')
        );
    }

    /**
     * testBuildASTForStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTForStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTForStatement::CLAZZ,
            $this->createBuilder()->buildASTForStatement('for')
        );
    }

    /**
     * testBuildASTForInitReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTForInitReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTForInit::CLAZZ,
            $this->createBuilder()->buildASTForInit()
        );
    }

    /**
     * testBuildASTForUpdateReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTForUpdateReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTForUpdate::CLAZZ,
            $this->createBuilder()->buildASTForUpdate()
        );
    }

    /**
     * testBuildASTForeachStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTForeachStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTForeachStatement::CLAZZ,
            $this->createBuilder()->buildASTForeachStatement('foreach')
        );
    }

    /**
     * testBuildASTFormalParametersReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTFormalParametersReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTFormalParameters::CLAZZ,
            $this->createBuilder()->buildASTFormalParameters()
        );
    }

    /**
     * testBuildASTFormalParameterReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTFormalParameterReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTFormalParameter::CLAZZ,
            $this->createBuilder()->buildASTFormalParameter()
        );
    }

    /**
     * testBuildASTGlobalStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTGlobalStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTGlobalStatement::CLAZZ,
            $this->createBuilder()->buildASTGlobalStatement()
        );
    }

    /**
     * testBuildASTGotoStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTGotoStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTGotoStatement::CLAZZ,
            $this->createBuilder()->buildASTGotoStatement('goto')
        );
    }

    /**
     * testBuildASTLabelStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTLabelStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTLabelStatement::CLAZZ,
            $this->createBuilder()->buildASTLabelStatement('LABEL')
        );
    }

    /**
     * testBuildASTReturnStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTReturnStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTReturnStatement::CLAZZ,
            $this->createBuilder()->buildASTReturnStatement('return')
        );
    }

    /**
     * testBuildASTScopeStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTScopeStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTScopeStatement::CLAZZ,
            $this->createBuilder()->buildASTScopeStatement()
        );
    }

    /**
     * testBuildASTStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTStatement::CLAZZ,
            $this->createBuilder()->buildASTStatement()
        );
    }

    /**
     * testBuildASTSwitchStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTSwitchStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTSwitchStatement::CLAZZ,
            $this->createBuilder()->buildASTSwitchStatement()
        );
    }

    /**
     * testBuildASTThrowStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTThrowStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTThrowStatement::CLAZZ,
            $this->createBuilder()->buildASTThrowStatement('throw')
        );
    }

    /**
     * testBuildASTTryStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTTryStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTTryStatement::CLAZZ,
            $this->createBuilder()->buildASTTryStatement('try')
        );
    }

    /**
     * testBuildASTUnsetStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTUnsetStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTUnsetStatement::CLAZZ,
            $this->createBuilder()->buildASTUnsetStatement()
        );
    }

    /**
     * testBuildASTWhileStatementReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTWhileStatementReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTWhileStatement::CLAZZ,
            $this->createBuilder()->buildASTWhileStatement('while')
        );
    }

    /**
     * testBuildASTArrayIndexExpressionReturnsExpectedType
     * 
     * @return void
     */
    public function testBuildASTArrayIndexExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTArrayIndexExpression::CLAZZ,
            $this->createBuilder()->buildASTArrayIndexExpression()
        );
    }

    /**
     * testBuildASTClosureReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTClosureReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTClosure::CLAZZ,
            $this->createBuilder()->buildASTClosure()
        );
    }

    /**
     * testBuildASTParentReferenceReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTParentReferenceReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTParentReference::CLAZZ,
            $this->createBuilder()->buildASTParentReference(
                $this->createBuilder()->buildASTClassOrInterfaceReference(__CLASS__)
            )
        );
    }

    /**
     * testBuildASTSelfReferenceReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTSelfReferenceReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTSelfReference::CLAZZ,
            $this->createBuilder()->buildASTSelfReference(
                $this->createBuilder()->buildClass(__CLASS__)
            )
        );
    }

    /**
     * testBuildASTStaticReferenceReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTStaticReferenceReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTStaticReference::CLAZZ,
            $this->createBuilder()->buildASTStaticReference(
                $this->createBuilder()->buildClass(__CLASS__)
            )
        );
    }

    /**
     * testBuildASTClassReferenceReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTClassOrInterfaceReferenceReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTClassOrInterfaceReference::CLAZZ,
            $this->createBuilder()->buildASTClassOrInterfaceReference(__CLASS__)
        );
    }

    /**
     * testBuildASTClassReferenceReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTClassReferenceReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTClassReference::CLAZZ,
            $this->createBuilder()->buildASTClassReference(__CLASS__)
        );
    }

    /**
     * testBuildASTPrimitiveTypeReturnsInstanceOfExpectedType
     *
     * @return void
     */
    public function testBuildASTPrimitiveTypeReturnsInstanceOfExpectedType()
    {
        $builder  = $this->createBuilder();
        $instance = $builder->buildASTPrimitiveType(__FUNCTION__);

        $this->assertInstanceOf(PHP_Depend_Code_ASTPrimitiveType::CLAZZ, $instance);
    }

    /**
     * testBuildASTAllocationExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTAllocationExpressionReturnsExpectedType()
    {
        $object = $this->createBuilder()
            ->buildASTAllocationExpression(__FUNCTION__);

        $this->assertInstanceOf(
            PHP_Depend_Code_ASTAllocationExpression::CLAZZ,
            $object
        );
    }

    /**
     * testBuildASTArgumentsReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTArgumentsReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTArguments::CLAZZ,
            $this->createBuilder()->buildASTArguments()
        );
    }

    /**
     * testBuildASTSwitchLabel
     *
     * @return void
     */
    public function testBuildASTSwitchLabel()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTSwitchLabel::CLAZZ,
            $this->createBuilder()->buildASTSwitchLabel('m')
        );
    }

    /**
     * testBuildASTEchoStatetmentReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTEchoStatetmentReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTEchoStatement::CLAZZ,
            $this->createBuilder()->buildASTEchoStatement('echo')
        );
    }

    /**
     * testBuildASTVariableDeclaratorReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTVariableDeclaratorReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTVariableDeclarator::CLAZZ,
            $this->createBuilder()->buildASTVariableDeclarator('foo')
        );
    }

    /**
     * testBuildASTStaticVariableDeclarationReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTStaticVariableDeclarationReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTStaticVariableDeclaration::CLAZZ,
            $this->createBuilder()->buildASTStaticVariableDeclaration('$foo')
        );
    }

    /**
     * testBuildASTPostfixExpressionReturnsExpectedType
     * 
     * @return void
     */
    public function testBuildASTPostfixExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTPostfixExpression::CLAZZ,
            $this->createBuilder()->buildASTPostfixExpression('++')
        );
    }

    /**
     * testBuildASTPreDecrementExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTPreDecrementExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTPreDecrementExpression::CLAZZ,
            $this->createBuilder()->buildASTPreDecrementExpression()
        );
    }

    /**
     * testBuildASTPreIncrementExpressionReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTPreIncrementExpressionReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTPreIncrementExpression::CLAZZ,
            $this->createBuilder()->buildASTPreIncrementExpression()
        );
    }

    /**
     * testBuildASTMemberPrimaryPrefixReturnsExpectedType
     * 
     * @return void
     * @since 0.11.0
     */
    public function testBuildASTMemberPrimaryPrefixReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTMemberPrimaryPrefix::CLAZZ,
            $this->createBuilder()->buildASTMemberPrimaryPrefix('::')
        );
    }

    /**
     * testBuildASTFunctionPostfixReturnsExpectedType
     *
     * @return void
     */
    public function testBuildASTFunctionPostfixReturnsExpectedType()
    {
        $this->assertInstanceOf(
            PHP_Depend_Code_ASTFunctionPostfix::CLAZZ,
            $this->createBuilder()->buildASTFunctionPostfix('foo')
        );
    }

    /**
     * Creates a clean builder test instance.
     *
     * @return PHP_Depend_Builder_Default
     */
    protected function createBuilder()
    {
        $builder = new PHP_Depend_Builder_Default();
        $builder->setCache($this->getMock('PHP_Depend_Util_Cache_Driver'));

        return $builder;
    }
}
