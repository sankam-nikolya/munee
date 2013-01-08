<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\tests;

use munee\Request;

/**
 * Tests for the \munee\Request Class
 *
 * @author Cody Lundquist
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $jsDir = WEBROOT . DS . 'js';
        if (! file_exists($jsDir)) {
            mkdir($jsDir);
        }

        file_put_contents($jsDir . DS . 'foo.js', '//Temp foo.js File');
        file_put_contents($jsDir . DS . 'bar.js', '//Temp bar.js File');
    }

    protected function tearDown()
    {
        $jsDir = WEBROOT . DS . 'js';
        unlink($jsDir . DS . 'foo.js');
        unlink($jsDir . DS . 'bar.js');
        rmdir($jsDir);
    }

    public function testNoQueryString()
    {
        $this->setExpectedException('munee\ErrorException');
        new Request();
    }

    public function testConstructor()
    {
        $_GET = array(
            'files' => '/js/foo.js,/js/bar.js'
        );

        $Request = new Request(array('foo' => 'bar'));

        $this->assertEquals('js', $Request->ext);
        $this->assertEquals(array(WEBROOT . '/js/foo.js', WEBROOT . '/js/bar.js'), $Request->files);
        $this->assertEquals(array('foo' => 'bar'), $Request->options);
    }

    public function testFileNotExist()
    {
        $_GET = array(
            'files' => '/js/does-not-exist.js'
        );

        $this->setExpectedException('munee\ErrorException');
        new Request();
    }

    public function testExtensionNotSupported()
    {
        $_GET = array(
            'files' => '/js/foo.jpg,/js/bar.js'
        );

        $this->setExpectedException('munee\ErrorException');
        new Request();
    }

    public function testLegacyCode()
    {
        $_GET = array(
            'files' => '/minify/js/foo.js'
        );

        $Request = new Request();

        $this->assertEquals(array(WEBROOT . '/js/foo.js'), $Request->files);
        $this->assertEquals(array('minify' => 'true'), $Request->getRawParams());
    }

    public function testGetRawParams()
    {
        $_GET = array(
            'files' => '/js/foo.js,/js/bar.js',
            'minify' => 'true',
            'notAllowedParam' => 'foo'
        );

        $Request = new Request();
        
        $rawParams = $Request->getRawParams();
        $this->assertEquals(array('minify' => 'true', 'notAllowedParam' => 'foo'), $rawParams);
    }

    public function testParseParams()
    {
        $_GET = array(
            'files' => '/js/foo.js,/js/bar.js',
            'foo' => 'true',
            'b' => 'ba[42]',
            'notAllowedParam' => 'foo'

        );

        $Request = new Request();
        
        $this->assertEquals(array(), $Request->params);

        $allowedParams = array(
            'foo' => array(
                'alias' => 'f',
                'default' => 'false',
                'cast' => 'boolean'
            ),
            'bar' => array(
                'alias' => 'b',
                'arguments' => array(
                    'baz' => array(
                        'alias' => 'ba',
                        'regex' => '\d+',
                        'cast' => 'integer',
                        'default' => 24
                    ),
                )
            ),
            'qux' => array(
                'default' => 'no'
            )
        );

        $Request->parseParams($allowedParams);
        $assertedParams = array(
            'foo' => true,
            'bar' => array(
                'baz' => 42
            ),
            'qux' => 'no'
        );

        $this->assertEquals($assertedParams, $Request->params);
    }

    public function testWrongParamValue()
    {
        $_GET = array(
            'files' => '/js/foo.js',
            'foo' => 'not good'
        );

        $allowedParams = array(
            'foo' => array(
                'alias' => 'f',
                'regex' => 'true|false',
                'default' => 'false',
                'cast' => 'boolean'
            )
        );

        $Request = new Request();

        $this->setExpectedException('munee\ErrorException');
        $Request->parseParams($allowedParams);
    }
}