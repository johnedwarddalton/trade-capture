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
     * 
     * @access public
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
     * reads the data from a CSV file and saves to database
     * 
     * @access public
     */
    public function uploadAction()
    {
    	//we don't require any output so turn off views and rendering
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender(TRUE);
    	 
    	// read the File feed and process each transaction
    	$this->processFileData ();
    	 
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
    	$cacheDir = $options['cache']['directory'];
    	
    	// set cache - this allows us to check whether the feed has been updated

    	$cache = Zend_Cache::factory('Core','File', array('lifetime' => null),
    			array('cache_dir' => $cacheDir));
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
    
    /**
     * reads the archive file instead of the live feed.
     *
     * @return array
     */
    protected function processFileData()
    {
    
    	//Get the datafile name from the configuration options
    	$options = $this->getConfigOptions();
    	$params = $this->getRequest()->getParams();
    	$data_dir = $options['data']['dir'];
    		if (isset ($params['file'])){
    			$data_file = $data_dir . $params['file'];
    		}
    		else {
    			$data_file = $data_dir . $options['data']['file'];
    		}
    	
    	$file_handle = fopen($data_file, "r");
    	$field_names = fgetcsv($file_handle);  //header row
		while ($entry = fgetcsv($file_handle) ) {
    		$feed_data = array_combine($field_names, $entry);
    		
    		if ($feed_data){
    			$feed_data['depository'] = 'DTCC';
    			$transaction = new Application_Model_Transaction();
    			$transaction->populateFromDescriptionData($feed_data);
    			$this->updateDatabase($transaction);
    		}	
		}
		fclose($file_handle);
    }

    
    /**
     * processes each of the RSS feed entries and converts them
     * to transaction objects
     * 
     * @param array $entries
     * @return multitype:Application_Model_Transaction
     * 
     * @access protected
     */
    protected function processFeedData( $entries)
    {
    	if (count($entries)){
    		$options = $this->getConfigOptions();
    		$header_file = $options['data']['dir'] . $options['data']['header'];
    		//read the data fields from file provided by DTCC  http:/dtcc.com
    		$header = fopen($header_file, 'r');
    		$field_names=fgetcsv($header);
    		fclose($header);  		
    	}
    	
    	//process the "description" field from each rss "item"
    	$transactions = array();
    	foreach ($entries as $entry){
    		// 'description' field is a CSV string so parse it 
    		$parsed_data=str_getcsv($entry['description']);
    		if ( count($field_names) === count ($parsed_data)){
    			$feed_data = array_combine($field_names, $parsed_data);
    			
    			//set the depository name here
    			$feed_data['depository'] = 'DTCC';

    			$transaction = new Application_Model_Transaction();
    			$transaction->populateFromDescriptionData($feed_data);		
    			$transactions[]=$transaction;
    		}
    		else{
    			$this->getLogger()->
    			    log('Feed data length does not match. Specification may have changed', Zend_Log::WARN);
    		}
    		
    	}    
    	return $transactions;
    }
    
  
    /**
     * saves the data from a transaction instance to the database
     * when appropriate conditions are met
     * 
     * @param Application_Model_Transaction $transaction
     * 
     * @access protected
     */
    protected function updateDatabase(Application_Model_Transaction $transaction){
    	$trade_data = $transaction -> getTradeData();
    	switch ($transaction->getAction()) {
    		case 'NEW':
    			$this->saveIfTrade($trade_data);
    			break;
    			
    		case 'CANCEL' :
    			$this->getTradeMapper()->delete($trade_data['trade_id']);
    			break;
    	
    		case 'CORRECT' :
    			$this->getTradeMapper()->delete($trade_data['trade_id']);
    			$this->saveIfTrade($trade_data);
    			break;
    		
    		default:
    			break;
    	
    	}
    }
    
    /**
     * 
     * checks to see if the transaction is a new trade and, if so, saves to the database
     * 
     * @param Transaction $transaction
     * 
     * @access protected
     */
    protected function saveIfTrade($data){
    	switch (strtolower($data['trans_type']) ){
    		case 'trade' :
    
    			$this->getTradeMapper()->save($data);
    			break;
    		case 'amendment':
    		case 'novation':
    		case 'termination':
    		default:
    			// do nothing
    			break;
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

