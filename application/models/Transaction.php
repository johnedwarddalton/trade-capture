<?php
// application/models/Transaction.php


/**  Class to represent the description of a trade received from DTCC.  This class
 * is where the key application logic resides and would need to be changed if the 
 * feed specification is changed.
 * 
 * @package trade_capture
 * 
 * @author John Dalton
*/
class Application_Model_Transaction
{
	/**
	 * unique transaction id
	 * 
	 * @var integer
	 * 
	 * @access protected
	 */
	protected $_id = NULL;
	
	
	/**
	 * type of transaction.  Possible values are 'NEW', 'CORRECT' or 'CANCEL'
	 * 
	 * @var string
	 * 
	 * @access protected
	 */
	protected $_action = NULL;
	
	/** 
	 * Modifier for a transaction. Possible values are 'Trade' for a completely new trade,
	 * 'Amendment' for an amendment or reset, 'Novation' for an assignment or 'Termination'.   
	 * 
	 * @var string
	 * 
	 * @access protected
	 */
	protected $_trade_type = NULL;
	
	/**
	 * associative array which holds the trade data
	 * 
	 * @var array
	 * 
	 * @access protected
	 */
	protected $_trade_data = NULL;
	
	
	/**
	 * @return integer
	 * 
	 * @access public
	 */
	public function getId()
	{
		return $this->_id;
	}
	
	
	/**
	 * @return string
	 * 
	 * @access public
	 */
	public function getAction(){
		return $this->_action;
	}
	
	
	/**
	 * @return string
	 * 
	 * @access public
	 */
	public function getTradeType(){
		return $this->_trade_type;
	}
	
	
	/**
	 * 
	 * @return array
	 * 
	 * @access public
	 */
	public function getTradeData(){
		return $this->_trade_data;
	}
	
	
	/**
	 * 
	 * @param array $data
	 * 
	 * @access public
	 */
	public function setTradeData($data){
		if (is_array ($data)){
			$this->_trade_data = $data;
		}
	}


	/**
	 * receives an associate array of raw data from the description field of the
	 * RSS feed and parses it into sensible data to be stored in the database.
	 * 
	 * @param array $feedData
	 * 
	 * @access public
	 */
	public function populateFromDescriptionData(array $feedData){
		$this->_id = (int) $feedData['DISSEMINATION_ID'];
		$this->_action = $feedData['ACTION'];
		$this->_trade_type = $feedData['PRICE_FORMING_CONTINUATION_DATA'];

		
		//  Set up the trade data.  Refer to Trade object for fields
		$data = array();

		if ($feedData['ORIGINAL_DISSEMINATION_ID'] === ''){
			$data['trade_id'] = $this->getId();
		}
		else {
			$data['trade_id'] = (int) $feedData['ORIGINAL_DISSEMINATION_ID'];
		}

		// These fields are assigned directly from the RSS feed
		$data['execution_date'] 		= $feedData['EXECUTION_TIMESTAMP'];
		$data['cleared']        		= $feedData['CLEARED'];
		$data['collat']         		= $feedData['INDICATION_OF_COLLATERALIZATION'];
		$data['except']         		= $feedData['INDICATION_OF_END_USER_EXCEPTION'];
		$data['other_price']         	= $feedData['INDICATION_OF_OTHER_PRICE_AFFECTING_TERM'];
		$data['block']          		= $feedData['BLOCK_TRADES_AND_LARGE_NOTIONAL_OFF-FACILITY_SWAPS'];
		$data['venue']          		= $feedData['EXECUTION_VENUE'];
		$data['eff_date'] 				= $feedData['EFFECTIVE_DATE'];
		$data['end_date'] 				= $feedData['END_DATE'];
		$data['dcc'] 		    		= $feedData['DAY_COUNT_CONVENTION'];
		$data['currency'] 				= $feedData['SETTLEMENT_CURRENCY'];
		$data['asset_class'] 			= $feedData['ASSET_CLASS'];
		$data['sub_asset_class'] 		= $feedData['SUB-ASSET_CLASS_FOR_OTHER_COMMODITY'];
		$data['trans_type'] 			= $feedData['PRICE_FORMING_CONTINUATION_DATA'];
		$data['und_asset_1'] 			= $feedData['UNDERLYING_ASSET_1'];
		$data['und_asset_2'] 			= $feedData['UNDERLYING_ASSET_2'];
		$data['price_type'] 			= $feedData['PRICE_NOTATION_TYPE'];
		$data['add_price_type'] 		= $feedData['ADDITIONAL_PRICE_NOTATION_TYPE'];
		$data['not_curr_1'] 			= $feedData['NOTIONAL_CURRENCY_1'];
		$data['not_curr_2'] 			= $feedData['NOTIONAL_CURRENCY_2'];
		$data['pay_freq_1'] 			= $feedData['PAYMENT_FREQUENCY_1'];
		$data['pay_freq_2'] 			= $feedData['PAYMENT_FREQUENCY_2'];
		$data['reset_freq_1'] 			= $feedData['RESET_FREQUENCY_1'];
		$data['reset_freq_2'] 			= $feedData['RESET_FREQUENCY_2'];
		$data['opt_embed'] 				= $feedData['EMBEDED_OPTION'];
		$data['opt_type'] 				= $feedData['OPTION_TYPE'];
		$data['opt_family'] 			= $feedData['OPTION_FAMILY'];
		$data['opt_curr'] 				= $feedData['OPTION_CURRENCY'];
		$data['opt_start'] 				= $feedData['OPTION_LOCK_PERIOD'];
		$data['opt_expiry'] 			= $feedData['OPTION_EXPIRATION_DATE'];
		$data['opt_add_price_type_1'] 	= $feedData['PRICE_NOTATION2_TYPE'];
		$data['opt_add_price_type_2'] 	= $feedData['PRICE_NOTATION3_TYPE'];
		
		//  The following price fields are for fees and/or premiums. 
		//  Since we will not use them for sorting it is more efficient to keep them
		//  as formatted strings, rather than parsing them.
		$data['add_price'] 			= $feedData['ADDITIONAL_PRICE_NOTATION'];
		$data['opt_add_price_2'] 	= $feedData['PRICE_NOTATION3'];
		$data['opt_premium']		= $feedData['OPTION_PREMIUM'];


		//These require validation and/or further calculation

		$data['price'] 				= (double) str_replace(',', NULL, $feedData['PRICE_NOTATION']);
		$data['opt_strike'] 		= (double) str_replace(',', NULL, $feedData['OPTION_STRIKE_PRICE']);
		$data['opt_add_price_1'] 	= (double) str_replace(',', NULL, $feedData['PRICE_NOTATION2']);



		/* Amounts are given as rounded amounts in currency formats
		 * with optional characters e.g. 20,000,000+.  We need to parse these */
		$int_value 	= (int) str_replace(',',NULL,$feedData['ROUNDED_NOTIONAL_AMOUNT_1']);
		$data['not_amount_1'] 	= round($int_value / 1000000.0,6);
		$int_value 	= (int) str_replace(',',NULL,$feedData['ROUNDED_NOTIONAL_AMOUNT_2']);
		$data['not_amount_2'] 	= round($int_value / 1000000.0,6);
		


		//ensure we have a locale set for Zend_Date functions
		$locale = new Zend_Locale('en_GB');
		
		// Calculate Term in Years
		$startDate = new Zend_Date($data['eff_date'], $locale);
		$endDate  = new Zend_Date($data['end_date'], $locale);
		$interval = $endDate->sub($startDate);
		$term = $interval->toValue() / (365*86400);
		if ($term > 1){
			$data['term'] = round($term,1);
		}
		else{
			$data['term'] = round($term,2);
		}
		
		// Calculate Option Tenor in Days
		if ($data['opt_expiry'] !== ''){
			$optStartDate = new Zend_Date($data['execution_date'], $locale);
			if ($data['opt_start'] <> ''){
				$optStartDate = new Zend_Date($data['opt_start'], $locale);
			}
			$optEndDate  = new Zend_Date($data['opt_expiry'], $locale);
			$interval = $optEndDate->sub($optStartDate);
			$opt_tenor = $interval->toValue() / 86400;
			$data['opt_tenor'] = round($opt_tenor,0);

		}

		//split the taxonomy field into its components
		$taxonomy = explode(':', $feedData['TAXONOMY']);
		$data['category'] = $taxonomy[0];

		$taxLen = count($taxonomy);
		$data['inst_type'] = ($taxLen > 1) ? $taxonomy[1] : NULL;
		$data['inst_subtype'] = ($taxLen > 2) ? $taxonomy[2] : NULL;
		
		
		//set inst_subtype for Caps and Floors
		if ('CapFloor' === $data['inst_type']){
			$data['inst_subtype'] = 'Cap/Flr';
		}

		$data['depository'] = $feedData['depository'];
		
		$this->setTradeData($data);
	}
}