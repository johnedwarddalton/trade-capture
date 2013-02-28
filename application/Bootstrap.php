<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initDoctype(){
		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_STRICT');
	}
	
	
	/**
	 * sets default timezone and locale in order
	 */
	protected function _initDateSettings(){
		date_default_timezone_set('UTC');
		Zend_Locale::setDefault('en_GB');
	}
}

