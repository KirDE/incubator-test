<?php

/**
 * This file is part of the Phalcon Incubator Test.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Incubator\Test\Traits;

use Phalcon\Di\DiInterface;
use Phalcon\Html\Escaper as PhEscaper;
use Phalcon\Mvc\Application as PhApplication;
use Phalcon\Mvc\Dispatcher as PhDispatcher;

trait FunctionalTestCase
{
    protected $application;

    /**
     * This method is called before a test is executed.
     */
    protected function setUpPhalcon()
    {
        parent::setUpPhalcon();

        // Set the dispatcher
        $this->di->setShared(
            'dispatcher',
            function () {
                $dispatcher = new PhDispatcher();

                $dispatcher->setControllerName('test');
                $dispatcher->setActionName('empty');
                $dispatcher->setParams([]);

                return $dispatcher;
            }
        );

        $this->di->set(
            'escaper',
            function () {
                return new PhEscaper();
            }
        );

        if ($this->di instanceof DiInterface) {
            $this->application = new PhApplication($this->di);
        }
    }

    /**
     * Ensures that each test has it's own DI and all globals are purged
     *
     * @return void
     */
    protected function tearDownPhalcon()
    {
        $this->di->reset();

        $this->application = null;

        $_SESSION = [];
        $_GET =  [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];
        $_FILES = [];
    }

    /**
     * Dispatches a given url and sets the response object accordingly
     *
     * @param  string $url The request url
     * @return void
     */
    protected function dispatch($url)
    {
        $this->di->setShared(
            'response',
            $this->application->handle($url)
        );
    }

    /**
     * Assert that the last dispatched controller matches the given controller class name
     *
     * @param  string $expected The expected controller name
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assertController($expected)
    {
        $dispatcher = $this->di->getShared('dispatcher');

        $actual = $dispatcher->getControllerName();

        if ($actual != $expected) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Failed asserting Controller name "%s", actual Controller name is "%s"',
                    $expected,
                    $actual
                )
            );
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * Assert that the last dispatched action matches the given action name
     *
     * @param  string $expected The expected action name
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assertAction($expected)
    {
        $dispatcher = $this->di->getShared('dispatcher');

        $actual = $dispatcher->getActionName();

        if ($actual != $expected) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Failed asserting Action name "%s", actual Action name is "%s"',
                    $expected,
                    $actual
                )
            );
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * Assert that the response headers contains the given array
     * <code>
     * $expected = ['Content-Type' => 'application/json']
     * </code>
     *
     * @param  array $expected The expected headers
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assertHeader(array $expected)
    {
        $response = $this->di->getShared('response');

        $headers = $response->getHeaders();

        foreach ($expected as $expectedField => $expectedValue) {
            $actualValue = $headers->get($expectedField);

            if ($actualValue != $expectedValue) {
                throw new \PHPUnit\Framework\ExpectationFailedException(
                    sprintf(
                        'Failed asserting "%s" has a value of "%s", actual "%s" header value is "%s"',
                        $expectedField,
                        $expectedValue,
                        $expectedField,
                        $actualValue
                    )
                );
            }

            $this->assertEquals($expectedValue, $actualValue);
        }
    }

    /**
     * Asserts that the response code matches the given one
     *
     * @param  string $expected the expected response code
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assertResponseCode($expected)
    {
        // convert to string if int
        if (is_integer($expected)) {
            $expected = (string) $expected;
        }

        $response = $this->di->getShared('response');

        $headers = $response->getHeaders();

        $actualValue = $headers->get('Status');

        if (empty($actualValue) || stristr($actualValue, $expected) === false) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Failed asserting response code is "%s", actual response status is "%s"',
                    $expected,
                    $actualValue
                )
            );
        }

        $this->assertContains($expected, $actualValue);
    }

    /**
     * Asserts that the dispatch is forwarded
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assertDispatchIsForwarded()
    {
        /* @var $dispatcher \Phalcon\Mvc\Dispatcher */
        $dispatcher = $this->di->getShared('dispatcher');

        $actual = $dispatcher->wasForwarded();

        if (!$actual) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                'Failed asserting dispatch was forwarded'
            );
        }

        $this->assertTrue($actual);
    }

    /**
     * Assert location redirect
     *
     * @param  string $location
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assertRedirectTo($location)
    {
        $response = $this->di->getShared('response');

        $headers = $response->getHeaders();

        $actualLocation = $headers->get('Location');

        if (!$actualLocation) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                'Failed asserting response caused a redirect'
            );
        }

        if ($actualLocation !== $location) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Failed asserting response redirects to "%s". It redirects to "%s".',
                    $location,
                    $actualLocation
                )
            );
        }

        $this->assertEquals($location, $actualLocation);
    }

    /**
     * Convenience method to retrieve response content
     *
     * @return string
     */
    public function getContent()
    {
        $response = $this->di->getShared('response');

        return $response->getContent();
    }

    /**
     * Assert response content contains string
     *
     * @param string $string
     */
    public function assertResponseContentContains($string)
    {
        $this->assertContains(
            $string,
            $this->getContent()
        );
    }
}
