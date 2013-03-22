<?php

require_once 'Application/Test/ControllerTest.php';

class CaptureControllerTest extends Application_Test_ControllerTest
{	
    public function testDownloadAction()
    {

		$_SERVER['argv'] = array( 'index.php', 'capture', 'download');

        $this->dispatch();    

		//assertions
		$this->assertModule('default');
		$this->assertAction('download');  
		$this->assertController('capture');
    }
    
    public function testArchiveAction()
    {
    
    	$_SERVER['argv'] = array( 'index.php', 'capture', 'archive');
    
    	$this->dispatch();
    
    	//assertions
    	$this->assertModule('default');
    	$this->assertAction('archive');
    	$this->assertController('capture');
    }
                      
}



