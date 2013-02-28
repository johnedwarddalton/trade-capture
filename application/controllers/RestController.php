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
    	//  allow actions to return json data if specified
        $contextSwitch= $this->getHelper('contextSwitch');
    	$contextSwitch->addActionContext('retrieve', 'json')->initContext();
    	$contextSwitch->addActionContext('table', 'json')->initContext();	
    }
	
    /**
     * generic rest interface.  Returns data for a single trade if id is supplied.
     * Otherwise, returns trade objects from the database which satisfy the query
     * parameters
     * 
     * @return array
     * 
     * @access public
     */
    public function retrieveAction()
    {
       $params = $this->getRequest()->getParams();
       
       $entries = array();
       
       if ( isset($params['id']) ){			//single trade request
       	$trade_id = (int) $params['id'];
       	$trade = new Application_Model_Trade();
       	$this->getTradeMapper()->find($trade_id, $trade);
       	$entries[] = $trade->toArray();
       }
       else {								// multiple trades
       	   $trades = $this->tradeQuery($params);
       	   foreach ($trades as $trade){
       		    $entries[] = $trade->toArray();
       	}
       }
       
       $this->view->entries = $entries;
       
    }

    /**
     * datasource for jquery dataTables.  Returns data quivalent to the generic query
     * (retrieve) but specifically format for use with dataTables
     * 
     * @return array
     * 
     * @access public
     */
    public function tableAction()
    {
    	$params = $this->getRequest()->getParams();
    	$trades = $this->tradeQuery($params);
    	$entries=array();
    	$locale = new Zend_Locale('en_GB');
    	foreach ($trades as $trade){
    		$entry = $trade->toArray();
    		$date = new Zend_Date($trade->execution_date, $locale);
    		$entry['exec_date_short'] = $date->toString("EEE H:mm");
    		$date = new Zend_Date($trade->eff_date, $locale);
    		$entry['eff_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->end_date, $locale);
    		$entry['end_date_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_start, $locale);
    		$entry['opt_start_short'] = $date->toString("MMM-YY");
    		$date = new Zend_Date($trade->opt_expiry, $locale);
    		$entry['opt_expiry_short'] = $date->toString("MMM-YY");
    		$entries[] = $entry;
    	}
    	$this->view->aaData = $entries;
    }
    
    /**
     * extracts the query parameters and returns the relevant data
     * from the database
     *
     * @param array $modifiers
     *
     */
    protected function tradeQuery(array $modifiers)
    {
    	$options = $this->getConfigOptions();
    	$select = $this->getTradeMapper()->getDbTable()->select();
    	
    	if (isset($modifiers['currency'])){
    		$select->where('not_curr_1 = ?', $modifiers['currency']);
    	}
    	if (isset($modifiers['type'])){
    		$select->where('inst_type = ?', $modifiers['type']);
    	}
    	if (isset($modifiers['subtype'])){
    		$select->where('inst_subtype = ?', $modifiers['subtype']);
    	}
    	if (isset($modifiers['minimum'])){
    		$select->where('not_amount_1 >= ?', $modifiers['minimum']);
    	}
    	if (isset($modifiers['since'])){
    		$since = min( $options['rest']['maximum_since'], $modifiers['since']);
    	}
    	else{
    		$since = $options['rest']['default_since'];
    	}
    	$select->where('execution_date >=  DATE_SUB(utc_timestamp(), INTERVAL ? HOUR)', $since);
    	
    	return $this->getTradeMapper()->fetchSome($select);
    }

    /**
     * instantiates _tradeMapper if it does not already exist and returns it
     *
     * @return Application_Model_TradeMapper
     *
     * @access protected
     *
     */
    protected function getTradeMapper()
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
    protected function getConfigOptions(){
    	return $this->getFrontController()->getParam('bootstrap')->getApplication()->getOptions();
    }

}



