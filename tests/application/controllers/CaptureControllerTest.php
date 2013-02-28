<?php

class CaptureControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();
    }

    public function testDownloadAction()
    {
        $params = array('action' => 'download', 'controller' => 'Capture', 'module' => 'default');
        $url = $this->url($this->urlizeOptions($params));
        $this->dispatch($url);    

		//assertions
		$this->assertModule('default');
		$this->assertAction('download');  
		$this->assertController('Capture');
    }
                      

}



