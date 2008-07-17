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
 * @package    Zend_Wildfire
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Log */
require_once 'Zend/Log.php';

/** Zend_Log_Writer_Abstract */
require_once 'Zend/Log/Writer/Abstract.php';

/** Zend_Wildfire_Plugin_FirePhp */
require_once 'Zend/Wildfire/Plugin/FirePhp.php';

/**
 * Writes log messages to the Firebug Console via FirePHP.
 * 
 * @category   Zend
 * @package    Zend_Wildfire
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Wildfire_FirebugLogWriter extends Zend_Log_Writer_Abstract
{

    /**
     * Maps logging priorities to logging display styles
     * @var array
     */
    protected $_priorityStyles = array(Zend_Log::EMERG  => Zend_Wildfire_Plugin_FirePhp::ERROR,
                                       Zend_Log::ALERT  => Zend_Wildfire_Plugin_FirePhp::ERROR,
                                       Zend_Log::CRIT   => Zend_Wildfire_Plugin_FirePhp::ERROR,
                                       Zend_Log::ERR    => Zend_Wildfire_Plugin_FirePhp::ERROR,
                                       Zend_Log::WARN   => Zend_Wildfire_Plugin_FirePhp::WARN,
                                       Zend_Log::NOTICE => Zend_Wildfire_Plugin_FirePhp::INFO,
                                       Zend_Log::INFO   => Zend_Wildfire_Plugin_FirePhp::INFO,
                                       Zend_Log::DEBUG  => Zend_Wildfire_Plugin_FirePhp::LOG);
    
    /**
     * The default logging style for un-mapped priorities
     * @var string
     */    
    protected $_defaultPriorityStyle = Zend_Wildfire_Plugin_FirePhp::LOG;
    
    /**
     * Set the default display style for user-defined priorities
     * 
     * @param string $style The default log display style
     * @return string Returns previous default log display style
     */    
    public function setDefaultPriorityStyle($style)
    {
        $previous = $this->_defaultPriorityStyle;
        $this->_defaultPriorityStyle = $style;
        return $previous;
    }
    
    /**
     * Set a display style for a logging priority
     * 
     * @param int $priority The logging priority
     * @param string $style The logging display style
     * @return string|boolean The previous logging display style if defined or TRUE otherwise
     */
    public function setPriorityStyle($priority, $style)
    {
        $previous = true;
        if (array_key_exists($priority,$this->_priorityStyles)) {
            $previous = $this->_priorityStyles[$priority];
        }
        $this->_priorityStyles[$priority] = $style;
        return $previous;
    }

    /**
     * Formatting is not possible on this writer
     *
     * @return void
     */
    public function setFormatter($formatter)
    {
        require_once 'Zend/Log/Exception.php';
        throw new Zend_Log_Exception(get_class() . ' does not support formatting');
    }

    /**
     * Log a message to the Firebug Console.
     *
     * @param array $event The event data
     * @return void
     */
    protected function _write($event)
    {
        if (array_key_exists($event['priority'],$this->_priorityStyles)) {
            $type = $this->_priorityStyles[$event['priority']];
        } else {
            $type = $this->_defaultPriorityStyle;
        }
        
        try {
          
            Zend_Wildfire_Plugin_FirePhp::getInstance()->send($event['message'], null, $type);
            
        } catch (Exception $e) {
            throw new Zend_Wildfire_Exception('You must initialize Zend_Controller_Front with a request and response object before logging messages that will be sent to Zend_Wildfire_FirebugLogWriter. You can do this manually or you can use just Zend_Wildfire_FirebugLogWriter in your model, view or controller files.');
        }
    }
}
