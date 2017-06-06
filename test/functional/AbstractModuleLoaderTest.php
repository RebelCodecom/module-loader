<?php

namespace RebelCode\Modular\FuncTest;

use Dhii\Modular\ModuleInterface;
use RebelCode\Modular\AbstractModuleLoader;
use Xpmock\TestCase;

/**
 * Tests {@see RebelCode\Modular\AbstractModuleLoader}.
 *
 * @since [*next-version*]
 */
class AbstractModuleLoaderTest extends TestCase
{
    /**
     * The name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'RebelCode\\Modular\\AbstractModuleLoader';

    /**
     * The name of the module class or interface to use for testing.
     *
     * @since [*next-version*]
     */
    const MODULE_CLASSNAME = 'Dhii\\Modular\\ModuleInterface';

    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @return AbstractModuleLoader
     */
    public function createInstance($prepare = null, $canLoad = null, $handleUnloaded = null)
    {
        $mock = $this->mock(static::TEST_SUBJECT_CLASSNAME);

        if (!is_null($prepare)) {
            $mock->_prepareModuleList($prepare);
        }

        if (!is_null($canLoad)) {
            $mock->_canLoadModule($canLoad);
        }

        if (!is_null($handleUnloaded)) {
            $mock->_handleUnloadedModule($handleUnloaded);
        }

        return $mock->new();
    }

    /**
     * Creates an instance of a module.
     *
     * @since [*next-version*]
     *
     * @param string $id The ID of the module.
     *
     * @return ModuleInterface
     */
    public function createModuleInstance($id, $load = null)
    {
        return $this->mock(static::MODULE_CLASSNAME)
            ->getId(function() use ($id) { return $id; })
            ->load($load)
            ->new();
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = $this->createInstance();

        $this->assertInstanceOf(
            static::TEST_SUBJECT_CLASSNAME, $subject, 'Subject is not a valid instance.'
        );
    }

    /**
     * Tests the module loading attempt method.
     *
     * @since [*next-version*]
     */
    public function testAttemptLoadModule()
    {
        $subject = $this->createInstance(
            null,
            null,
            null
        );
        $expected = array('test-foo', 'no-prefix');
        $loaded   = array();
        $onLoad   = function() use (&$loaded) {
            $loaded[] = $this->getId();
        };

        $module1 = $this->createModuleInstance('test-foo', $onLoad);
        $module2 = $this->createModuleInstance('no-prefix', $onLoad);

        $subject->this()->_attemptLoadModule($module1);
        $subject->this()->_attemptLoadModule($module2);

        $this->assertEquals($expected, $loaded);
    }

    /**
     * Tests the module loading attempt method with a condition that filters modules.
     *
     * @since [*next-version*]
     */
    public function testAttemptLoadModuleWithCondition()
    {
        $subject = $this->createInstance(
            null,
            function($module) {
                return stripos($module->getId(), 'test-') === 0;
            },
            null
        );
        $expected = array('test-foo');
        $loaded   = array();
        $onLoad   = function() use (&$loaded) {
            $loaded[] = $this->getId();
        };

        $module1 = $this->createModuleInstance('test-foo', $onLoad);
        $module2 = $this->createModuleInstance('no-prefix', $onLoad);

        $subject->this()->_attemptLoadModule($module1);
        $subject->this()->_attemptLoadModule($module2);

        $this->assertEquals($expected, $loaded);
    }

    /**
     * Tests the module loading method.
     *
     * @since [*next-version*]
     */
    public function testLoad()
    {
        $subject = $this->createInstance(
            null,
            null,
            null
        );
        $expected = array('test-1', 'num-two', 'test-3');
        $loaded   = array();
        $onLoad   = function() use (&$loaded) {
            $loaded[] = $this->getId();
        };
        $modules = array(
            $this->createModuleInstance('test-1', $onLoad),
            $this->createModuleInstance('num-two', $onLoad),
            $this->createModuleInstance('test-3', $onLoad),
        );

        $subject->this()->_load($modules);

        $this->assertEquals($expected, $loaded);
    }

    /**
     * Tests the module loading method with module list preparation.
     *
     * @since [*next-version*]
     */
    public function testLoadWithPreparation()
    {
        $me = $this;

        $loaded   = array();
        $onLoad   = function() use (&$loaded) {
            $loaded[] = $this->getId();
        };

        $subject = $this->createInstance(
            function ($list) use($me, $onLoad) {
                $list[] = $me->createModuleInstance('new', $onLoad);

                return $list;
            },
            null,
            null
        );
        $expected = array('test-1', 'test-2', 'test-3', 'new');
        
        $modules = array(
            $this->createModuleInstance('test-1', $onLoad),
            $this->createModuleInstance('test-2', $onLoad),
            $this->createModuleInstance('test-3', $onLoad),
        );

        $subject->this()->_load($modules);

        $this->assertEquals($expected, $loaded);
    }

    /**
     * Tests the module loading method with a condition that filters modules.
     *
     * @since [*next-version*]
     */
    public function testLoadWithCondition()
    {
        $subject = $this->createInstance(
            null,
            function($module) {
                return stripos($module->getId(), 'test-') === 0;
            },
            null
        );
        $loaded = array();
        $onLoad = function() use (&$loaded) {
            $loaded[] = $this->getId();
        };
        $modules = array(
            $this->createModuleInstance('test-1', $onLoad),
            $this->createModuleInstance('num-two', $onLoad),
            $this->createModuleInstance('test-3', $onLoad),
        );

        $subject->this()->_load($modules);

        $this->assertEquals(array('test-1', 'test-3'), $loaded);
    }

    /**
     * Tests the method that handles unloaded modules.
     *
     * @since [*next-version*]
     */
    public function testLoadWithHandleUnloadedModule()
    {
        $unloaded = array();

        $subject  = $this->createInstance(
            null,
            function($module) {
                return stripos($module->getId(), 'test-') === 0;
            },
            function($module) use(&$unloaded) {
                return $unloaded[] = $module->getId();
            }
        );

        $expected = array('num-two');
        $modules  = array(
            $this->createModuleInstance('test-1'),
            $this->createModuleInstance('num-two'),
            $this->createModuleInstance('test-3'),
        );

        $subject->this()->_load($modules);

        $this->assertEquals($expected, $unloaded);
    }
}
