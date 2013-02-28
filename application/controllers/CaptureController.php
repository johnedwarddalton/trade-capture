<?php
//   application/controllers/CaptureController.php


/**
 * 
 * Controller class for trade capture application
 * 
 * @author John Dalton
 * 
 * @package Trade_Capture
 *
 */
class CaptureController extends Zend_Controller_Action
{
	/**
	 * 
	 * @var Zend_Log_Writer_Stream
	 * 
	 * @access protected
	 */
	protected $_logger;
	
	/**
	 * instance of class which is used to communicate with database 
	 * 
	 * @var TradeMapper
	 */
	protected $_tradeMapper = NULL;
	
	/**
	 * sets up the controller
	 */
    public function init()
    {

    }

    /**
     * default action
     */
    public function indexAction()
    {

    }


    /**
     * reads the RSS data and, if there is any new data, saves to the database
     */
    public function downloadAction()
    {	 	
    	//we don't require any output so turn off views and rendering
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender(TRUE);
    	
    	// read the RSS feed and get a list of transactions
    	$transactions = $this->processFeedData ($this->getFeedData());
    	
    	//process each indivual transaction
    	foreach ($transactions as $transaction){
    		$this->updateDatabase($transaction);
    	}
    }
    

    /**
     * reads the RSS feeds and extracts the 'description' field from each one.
     * Uses caching and http conditional get to ensure that the feed is only
     * read if it has been updated
     * 
     * @return array 
     */
    protected function getFeedData()
    {
    
    	//Get the  feed url from the configuration options
    	$options = $this->getConfigOptions();
    	$feedURL = $options['feed']['url'];
    	
    	// set cache - this allows us to check whether the feed has been updated

    	$cache = Zend_Cache::factory('Core','File', array('lifetime' => null),
    			array('cache_dir' => APPLICATION_PATH . '/cache/'));
    	Zend_Feed_Reader::setCache($cache); 
    
    	// set Reader properties to allow Conditional GET Requests
    	Zend_Feed_Reader::useHttpConditionalGet();
    
    	// disable SSL peer  verification as it can cause problems from some isp's
    	$options = array ('ssl' => array ('verify_peer' => false, 'allow_self_signed' => true));
    	$adapter = new Zend_Http_Client_Adapter_Socket();
    	$adapter->setStreamContext( $options);
    	Zend_Feed_Reader::getHttpClient()->setAdapter($adapter);    		
    
    	// interrogate the RSS feed
    	$rssData = Zend_Feed_Reader::import($feedURL);
    	
    	// response status will be 200 if new data, 304 if not modified
    	$responseStatus = Zend_Feed_Reader::getHttpClient()->getLastResponse()->getStatus();
   
    	$entries = array();
    	
    	// Only process if new data
    	if (200 === $responseStatus){
    		foreach ($rssData as $item){
    			$entry['description']=$item->getDescription();
    			$entries[]=$entry;
    		}
    	} 
	
    	return $entries;
    }
    
    
    protected function processFeedData( $entries)
    {
    	if (count($entries)){
    		//read the data fields from file provided by DTCC  http:/dtcc.com
    		$headerFile = fopen(APPLICATION_PATH . '/data/RATES_HEADER.csv','r');
    		$fieldNames=fgetcsv($headerFile);
    		fclose($headerFile);  		
    	}
    	
    	//process the "description" field from each rss "item"
    	$transactions = array();
    	foreach ($entries as $entry){
    		// 'description' field is a CSV string so parse it 
    		$parsedData=str_getcsv($entry['description']);
    		if ( count($fieldNames) === count ($parsedData)){
    			$feedData = array_combine($fieldNames, $parsedData);
    			$transaction = new Application_Model_Transaction();
    			$transaction->populateFromDescriptionData($feedData);
    			$transactions[]=$transaction;
    		}
    		else{
    			$this->getLogger()->
    			    log('Feed data length does not match. Specification may have changed', Zend_Log::WARN);
    		}
    		
    	}    
    	return $transactions;
    }
    
    protected function updateDatabase(Application_Model_Transaction $transaction){
    	$trade_data = $transaction -> getTradeData();
    	switch ($transaction->getAction()) {
    		case 'CANCEL' :
    			$this->getTradeMapper()->delete($trade_data['trade_id']);
    			break;
    	
    		case 'CORRECT' :
    			$this->getTradeMapper()->delete($trade_data['trade_id']);
    			$this->getTradeMapper()->save($trade_data);
    			break;
    	
    		case 'NEW':
    		default :
    			switch (strtolower($transaction->getTradeType()) ){
    				case 'trade' :
    					$this->getTradeMapper()->save($trade_data);
    					break;
    				case 'amendment':
    				case 'novation':
    				case 'termination':
    				default:
    					// do nothing
    					break;
    			}
    	}
    }
    
    
    /**
     * instantiates _logger if it does not already exist and returns it
     * 
     * @return Zend_Log_Writer_Stream
     * 
     * @access protected
     */   
    protected function getLogger(){
    	if (!$this->_logger){
    		$writer = new Zend_Log_Writer_Stream('php://stderr');
    		$this->_logger = new Zend_Log($writer);
    	}
    	return $this->_logger;
    }
    
	/**
	 * helper function which returns the initial options
	 * 
	 * @return array
	 * 
	 * @access protected
	 */
    protected function getConfigOptions(){
    	return $this->getFrontController()->getParam('bootstrap')->getApplication()->getOptions();
    }
    
    /**
     * instantiates _tradeMapper if it does not already exist and returns it
     * 
     * @return Application_Model_TradeMapper
     * 
     * @access protected
     */
    protected function getTradeMapper(){
    	if (!$this->_tradeMapper){
    		$this->_tradeMapper = new Application_Model_TradeMapper();
    	}
    	return $this->_tradeMapper;
    }
    
    
    
}

