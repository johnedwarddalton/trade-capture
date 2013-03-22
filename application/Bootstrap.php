<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	
	/**
	 * sets a non-standard router if called from command line
	 * 
	 * @access protected
	 */
	protected function _initRouter ()
	{
		// check environment variable and set registry
		if ('cli' == PHP_SAPI){
			Zend_Registry::set('cli', true);
		}
		else {
			Zend_Registry::set('cli', false);
		}
		
		// if running from command line, change router
		if (Zend_Registry::get('cli')){
        	$this->bootstrap( 'FrontController' );
        	$front = $this->getResource( 'FrontController' );
        	$front->setParam('disableOutputBuffering', true);
        	$front->setRouter( new Application_Router_Cli() );
        	$front->setRequest( new Zend_Controller_Request_Simple() );
        	$front->setResponse( new Zend_Controller_Response_Cli());
    	}
	}
	
	
	/**
	 * switches error handling for cli vs web
	 * 
	 * @access protected
	 */
	protected function _initError ()
	{
		$this->bootstrap( 'FrontController' );
		$front = $this->getResource( 'FrontController' );
		$front->registerPlugin( new Zend_Controller_Plugin_ErrorHandler() );
		$error = $front->getPlugin ('Zend_Controller_Plugin_ErrorHandler');
		$error->setErrorHandlerController('error');
	
		if(Zend_Registry::get('cli') )
		{
			$error->setErrorHandlerAction ('cli');
		}
	}
	
	/**
	 *  sets document type for html
	 *  
	 *  @access protected
	 */
	protected function _initDoctype(){
		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_STRICT');
	}
	
	
	/**
	 *   sets a global logger object
	 *   
	 *   @access protected
	 */
	
	protected function _initLogger()
	{
		$this->bootstrap("log");
		$logger = $this->getResource("log");
		Zend_Registry::set("logger", $logger);
	}
	
	/**
	 * sets default timezone and locale 
	 * 
	 * @access protected
	 */
	protected function _initDateSettings(){
		date_default_timezone_set('UTC');
		Zend_Locale::setDefault('en_GB');
	}
}

