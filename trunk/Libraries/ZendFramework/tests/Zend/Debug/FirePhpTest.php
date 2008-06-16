<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Debug
 * @subpackage FirePhp
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: VersionTest.php 8064 2008-02-16 10:58:39Z thomas $
 */

/**
 * Test helper
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';

/** Zend_Debug_FirePhp */
require_once 'Zend/Debug/FirePhp.php';

/** Zend_Controller_Request_Http */
require_once 'Zend/Controller/Request/Http.php';

/** Zend_Controller_Response_Http */
require_once 'Zend/Controller/Response/Http.php';

/** Zend_Json_Decoder */
require_once 'Zend/Json/Decoder.php';

/**
 * @category   Zend
 * @package    Zend_Debug
 * @subpackage FirePhp
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Debug_FirePhpTest extends PHPUnit_Framework_TestCase
{
  
    protected $_request = null;
    protected $_response = null;
    protected $_firephp = null;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("Zend_Debug_FirePhpTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setUp()
    {
        $this->_request = new Zend_Debug_FirePhpTest_Request();
        $this->_response = new Zend_Debug_FirePhpTest_Reponse();
        $this->_firephp = Zend_Debug_FirePhp::init($this->_request, $this->_response,
                                                   'Zend_Debug_FirePhpTest_FirePhp'); 
    }

    public function tearDown()
    {
        Zend_Debug_FirePhp::destroyInstance();
        unset($this->_firephp);
        unset($this->_request);
        unset($this->_response);
    }
    
    public function testInit()
    {
        Zend_Debug_FirePhp::destroyInstance();
        
        $firephp = null;
        
        try {
            $firephp = Zend_Debug_FirePhp::init($this->_request, $this->_response);
            // success
        } catch (Exception $e) {
            $this->fail('Should be able to re-initialize class after calling Zend_Debug_FirePhp::destroyInstance()');
        }
        
        $this->assertTrue(Zend_Debug_FirePhp::getInstance() === $firephp);
        
        Zend_Debug_FirePhp::destroyInstance();

        try {
            Zend_Debug_FirePhp::init($this->_response, $this->_request); 
            $this->fail('Should not be able to set invalid request and response class');
        } catch (Exception $e) {
            // success
        }
        try {
            Zend_Debug_FirePhp::init($this->_response, $this->_response); 
            $this->fail('Should not be able to set invalid request class');
        } catch (Exception $e) {
            // success
        }
        try {
            Zend_Debug_FirePhp::init($this->_request, $this->_request); 
            $this->fail('Should not be able to set invalid response class');
        } catch (Exception $e) {
            // success
        }
        
        Zend_Debug_FirePhp::init($this->_request, $this->_response);
        
        try {
            Zend_Debug_FirePhp::init($request, $request);
            $this->fail('Should not be able to re-initialize class');
        } catch (Exception $e) {
            // success
        }
    }

    public function testSetEnabled()
    {
        $this->assertFalse($this->_firephp->getEnabled());
        $this->assertFalse($this->_firephp->setEnabled(true));
        $this->assertTrue($this->_firephp->getEnabled());
        $this->assertTrue($this->_firephp->setEnabled(false));
        $this->assertFalse($this->_firephp->getEnabled());
    }

    public function testFire()
    {
        $var = 'Hello World';

        $this->assertFalse($this->_firephp->fire($var),'fire() when not enabled and wrong user-agent');
        $this->_firephp->setEnabled(true);
        $this->assertFalse($this->_firephp->fire($var),'fire() when enabled and wrong user-agent');
        $this->_request->setUserAgentExtensionEnabled(true);
        
        $this->assertTrue($this->_firephp->fire($var),'fire() when enabled and correct user-agent');
        $this->assertTrue($this->_firephp->verifyHeaderMessage('FirePHP.Firebug.Console',
                                                               array(Zend_Debug_FirePhp::LOG, $var)),
                          'fire() generated valid message in response headers');
        $this->_firephp->clearHeaders();
        
        $this->assertTrue($this->_firephp->fire($var,'Label'),'fire() when enabled and correct user-agent');
        $this->assertTrue($this->_firephp->verifyHeaderMessage('FirePHP.Firebug.Console',
                                                               array(Zend_Debug_FirePhp::LOG,
                                                                     array('Label', $var))),
                          'fire() generated valid message in response headers');
        $this->_firephp->clearHeaders();
        
    }

}

class Zend_Debug_FirePhpTest_FirePhp extends Zend_Debug_FirePhp {
  
    public static function getHeaderPrefix()
    {
        return self::$_headerPrefix;
    }
    
    public function clearHeaders()
    {
        $this->_clearHeaders();
    }
    
    public function verifyHeaderMessage($register, $message)
    {
        $message = serialize($message);
        
        $payload = $this->_getHeaderPayloadObject();
        
        if (!$payload || !$payload[$register]) {
            return false;
        }
 
        foreach ($payload[$register] as $msg) {
            if (serialize($msg)==$message) {
                return true;
            }
        }
        return false;      
    }
    
    protected function _getHeaderPayloadObject()
    {
        $headers = $this->_headers;
      
        ksort($headers, SORT_NUMERIC); 
        
        return Zend_Json_Decoder::decode(implode('', $headers));
    }
}


class Zend_Debug_FirePhpTest_Request extends Zend_Controller_Request_Http
{
    
    protected $_enabled = false;
    
    public function setUserAgentExtensionEnabled($enabled) {
        $this->_enabled = $enabled;
    }
    
    public function getHeader($header)
    {
        if ($header == 'User-Agent') {
            if ($this->_enabled) {
                return 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.14) Gecko/20080404 Firefox/2.0.0.14 FirePHP/0.1.0';
            } else {
                return 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.14) Gecko/20080404 Firefox/2.0.0.14';
            }         
        }
    }
}


class Zend_Debug_FirePhpTest_Reponse extends Zend_Controller_Response_Http
{

/*    
    public function verifyHeaderMessage($register, $message)
    {
        $message = serialize($message);
        
        $payload = $this->_getHeaderPayloadObject();
        
        if (!$payload || !$payload[$register]) {
            return false;
        }
 
        foreach ($payload[$register] as $msg) {
            if (serialize($msg)==$message) {
                return true;
            }
        }
        return false;      
    }
    
    protected function _getHeaderPayloadObject()
    {
        $headers = $this->getHeaders();
        if (!$headers) {
            return false;
        }
        
        $parts = array();
        
        $headerPrefix = strtolower(Zend_Debug_FirePhpTest_FirePhp::getHeaderPrefix());
        $headerPrefixLength = strlen($headerPrefix);
        
        foreach ($headers as $header ) {
            if (substr(strtolower($header['name']), 0, $headerPrefixLength) == $headerPrefix) {
                $parts[substr($header['name'],$headerPrefixLength)] = $header['value'];
            }
        }
        
        ksort($parts, SORT_NUMERIC); 
        
        return Zend_Json_Decoder::decode(implode('', $parts));
    }
*/    
}




// Call Zend_Debug_FirePhpTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Zend_Debug_FirePhpTest::main") {
    Zend_Debug_FirePhpTest::main();
}