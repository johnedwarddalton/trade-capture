<?php

require_once 'Application/Test/ControllerTest.php';

class RestControllerTest extends Application_Test_ControllerTest
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();
    }


    public function testUnauthRestRequest()
    {
       
    	//$url = '/rest/table/'; 
		$_SERVER['argv'] = array( 'index.php', 'rest', 'table');
        $this->dispatch();
        
        // assertions
        $this->assertModule('default');
        $this->assertController('rest');
        $this->assertAction('unauth');
    }

    
    public function testAuthRestRequest()
    {
  	 
    	$request = $this->getRequest();
    	$request->setHeader('Authorization', "Basic dG90YWxkZXJpdjp0ckBkZWMmcHR1cmU=");
    	
		$_SERVER['argv'] = array( 'index.php', 'rest', 'table');
		$this->dispatch();   

		

    
    	// assertions
    	$this->assertModule('default');
    	$this->assertController('rest');
    	$this->assertAction('table');
    	$this->assertQueryContentContains('table','Trade Id');
    }

}





