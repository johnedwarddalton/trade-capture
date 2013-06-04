<?php
//  application/controllers/RestController.php


/**
 * REST controller to give remote access to the data
 *
 * @author John Dalton
 *
 * @package Trade_Capture
 *
 */
class RestController extends Zend_Controller_Action
{

    /**
     * instance of class which is used to communicate with database
     *
     * @var TradeMapper
     *
     */
    protected $_tradeMapper = null;

    /** setup function for controller.
     *  
     * @access public
     */
    public function init()
    {	
    	// check http call using basic authentication
    	$this->_authorize();
    	
    	//  allow actions to return json data if specified
        $contextSwitch= $this->getHelper('contextSwitch');
    	$contextSwitch->addActionContext('retrieve', 'json')->initContext();
    	$contextSwitch->addActionContext('table', 'json')->initContext();
    	$contextSwitch->addActionContext('volume', 'json')->initContext();
    }
    
    
    /**
     *  authenticates request
     *  
     *  @access protected
     */
    protected function _authorize(){
    	$config = array(
    			'accept_schemes' => 'basic',
    			'realm'          => 'trade-capture',
    	);
    	
    	$adapter = new Zend_Auth_Adapter_Http($config);
    	
    	$options = $this->_getConfigOptions();
    	$basic_resolver_file = $options['auth']['file']['basic'];
    	$basic_resolver = new Zend_Auth_Adapter_Http_Resolver_File();
    	$basic_resolver->setFile($basic_resolver_file);
    	
    	$request = $this->getRequest();
    	$response = $this->getResponse();
    	$adapter->setBasicResolver($basic_resolver);
    	$adapter->setRequest($request);
    	$adapter->setResponse($response);
    	
    	$result = $adapter->authenticate();
    	
    	if ( !$result->isValid()){
    		$request->setActionName('unauth');
    	}
    			
    }
	
    
    /**
     * sets up the response for a unauthorised request
     * 
     * @access public
     */
    public function unauthAction()
    {
    	$this->getResponse()->setHttpResponseCode(401);
    	$this->_helper->json('Request unauthorised');
    }
    
    /**
     * returns data for a single trade if id is supplied.
 	 *
     * @return array
     * 
     * @access public
     */
    public function retrieveAction()
    {
    	
		$params = $this->getRequest()->getParams();
       
		if ( isset($params['id']) ){			//single trade request
       		$trade_id = (int) $params['id'];
       		$trade = new Application_Model_Trade();
       		$this->_getTradeMapper()->find($trade_id, $trade);
       		$entry = $trade->toArray();
       		
       		// set null values to blanks
       		foreach ($entry as $key=>$value){
       		if (!$value){
       			$entry[$key] = '';
       		}
       	}
       $this->view->entry = $entry;   
    	}
  }


    
 

    /**
     * datasource for jquery dataTables.  Returns data 
     * specifically formatted for use with dataTables
     *
     * @return array
     *
     * @access public
     */
    public function tableAction()
    {

    	$params = $this->getRequest()->getParams();
    	$options = $this->_getConfigOptions();
    	
    	//list of columns to be returned
    	$columns = array(
    			'unix_timestamp(creation_date)','trade_id','cleared', 'execution_date', 'eff_date', 'end_date', 'term', 'not_curr_1', 'not_curr_2', 'inst_type',
    			'inst_subtype', 'price', 'add_price','not_amount_1', 'not_amt_capped', 'not_amount_2','und_asset_1', 'und_asset_2', 'opt_strike',
    			 'opt_type','opt_curr', 'opt_premium', 'opt_start', 'opt_expiry','opt_tenor', 'opt_add_price_type_1', 'opt_add_price_1', 
    	);
    	
    	$results = $this->_getTradeMapper()->tradeQuery ($columns,$params, $options);
    	    	
    	$entries=array();
    	foreach ($results['trades'] as $trade){
    		$entry = array();
    		
    		//transfer returned columns
    		foreach ($columns as $col){
    			$entry[$col] = $trade->$col;
    		}
    		
    		// calculated data
    		$opt_prem_val = (double) str_replace(',', NULL, $trade->opt_premium) / 1e6;
    		$entry['opt_prem_val'] = round($opt_prem_val, 4);
    		
    		if ($trade->not_amount_1){       // avoid division by zero
    			$entry['opt_prem_bps'] = round(10000 * $opt_prem_val / $trade->not_amount_1,1);
    		}
    		
    		//ensure we have a locale set for Zend_Date functions
    		$locale = new Zend_Locale('en_GB');
    		
    		$date = new Zend_Date($trade->execution_date, $locale);
    		$entry['exec_date_short'] = $date->toString("EEE H:mm");
    		$date = new Zend_Date($trade->eff_date, $locale);
    		$entry['eff_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->end_date,$locale);
    		$entry['end_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_start,$locale);
    		$entry['opt_start_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_expiry,$locale);
    		$entry['opt_expiry_short'] = $date->toString("MMM-YY");
    		$entries[] = $entry;
    	}
    	$this->view->aaData = $entries;
    	$this->view->daily_vol = $results['daily_vol'];
    	$this->view->sEcho = $results['echo'];
    	$this->view->iTotalRecords = $results['total_rows'];
    	$this->view->iTotalDisplayRecords = $results['filtered_rows'];
    }
    
    /**
     *  max and average of daily notional amounts
     *
     * @return array
     *
     * @access public
     */
    public function volumeAction()
    {
    
    	$params = $this->getRequest()->getParams();
    	$options = $this->_getConfigOptions();
    	$results = $this->_getTradeMapper()->volumeHistory ($params, $options);
    	$dates = array();
    	$volumes = array();
    	foreach ($results as $row){
    		$dates[] = $row['date(execution_date)'];
    		$volumes[] = $row['sum(not_amount_1)'];
    	}
    	$this->view->dates = $dates;
    	$this->view->volumes = $volumes;
	
    }
    
    /**
     * instantiates _tradeMapper if it does not already exist and returns it
     *
     * @return Application_Model_TradeMapper
     *
     * @access protected
     *
     */
    protected function _getTradeMapper()
    {
    	if (!$this->_tradeMapper){
    		$this->_tradeMapper = new Application_Model_TradeMapper();
    	}
    	return $this->_tradeMapper;
    }
    
    /**
     * helper function which returns the initial options
     *
     * @return array
     *
     * @access protected
     */
    protected function _getConfigOptions(){
    	return $this->getFrontController()->getParam('bootstrap')->getApplication()->getOptions();
    }

}



