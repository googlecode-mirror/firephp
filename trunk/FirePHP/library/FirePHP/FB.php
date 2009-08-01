<?php

include_once 'Console/Table.php';

/**
 * This is going to evolve into a complete ad-hock devel/debug class for CLI and Web use.
 */
class FB
{

    public function trace()
    {
        if(!class_exists('Console_Table')) {
            throw new Exception('PEAR:Console_Table not installed!');
        }

        $trace = debug_backtrace();
        array_splice($trace,0,1);

        $table = new Console_Table();
        $table->setHeaders(
            array('File', 'Line', 'Call', '#Args')
        );

        foreach( $trace as $item ) {

            $row = array();
            $row[] = $item['file'];
            $row[] = $item['line'];
            
            $row[] = $item['class'] . $item['type'] . $item['function'];
            
            $row[] = sizeof($item['args']);
            
            $table->addRow($row);
        }

        echo $table->getTable();
    } 
    
    public static function dump($variable)
    {
        var_dump($variable);
    }     

    public static function log($message, $label=null)
    {
        echo (($label)?$label.': ':'').$message . "\n";
    }     
}

?>