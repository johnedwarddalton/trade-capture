<?php
//       application/router/cli.php

class Application_Router_Cli extends Zend_Controller_Router_Abstract
{
    public function route (Zend_Controller_Request_Abstract $dispatcher)
    {
    	try {
        	$getopt     = new Zend_Console_Getopt (array(
            	'verbose|v' => 'Print verbose output',
        		'file|f=s' => 'File to upload'));
        	$getopt->parse;
        	$arguments  = $getopt->getRemainingArgs();
    	}
    	catch (Zend_Console_Getopt_Exception $e) {
    		echo $e->getUsageMessage();
    		exit;
    	}

        if ($arguments)
        {
            $command = array_shift( $arguments );
            $action  = array_shift( $arguments );
            if(!preg_match ('~\W~', $command) )
            {
                $dispatcher->setControllerName( $command );
                $dispatcher->setActionName( $action );
                $dispatcher->setParams( $arguments );
                if (isset($getopt->v)){
                	$dispatcher->setParam('verbose', true);
                }
                if (isset($getopt->f)){
                	$dispatcher->setParam('file', $getopt->f);
                }
                return $dispatcher;
            }

            echo "Invalid command.\n", exit;

        }

        echo "No command given.\n", exit;
    }


    public function assemble ($userParams, $name = null, $reset = false, $encode = true)
    {
        echo "Not implemented\n", exit;
    }
}