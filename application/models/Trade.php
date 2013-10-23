<?php
//  application/models/Trade.php

/**
 * Class which represents a single trade object
 * 
 * @package Trade_Capture
 * 
 * @author John Dalton
 *
 */
class Application_Model_Trade
{
	public $creation_date_secs;
	public $depository;
	public $trade_id;
	public $exec_date_secs;
	public $cleared;
	public $collat;
	public $except;
	public $other_price;
	public $block;
	public $venue;
	public $eff_date;
	public $end_date;
	public $term;
	public $dcc;
	public $currency;
	public $trans_type;	
	public $asset_class;
	public $sub_asset_class;
	public $category;
	public $inst_type;
	public $inst_subtype;
	public $und_asset_1;
	public $und_asset_2;
	public $price_type;
	public $price;
	public $add_price_type;
	public $add_price;
	public $not_curr_1;
	public $not_curr_2;
	public $not_amount_1;
	public $not_amount_2;
	public $pay_freq_1;
	public $pay_freq_2;
	public $reset_freq_1;
	public $reset_freq_2;
	public $opt_embed;
	public $opt_strike;
	public $opt_type;
	public $opt_family;
	public $opt_curr;
	public $opt_premium;
	public $opt_start;
	public $opt_expiry;
	public $opt_tenor;
	public $opt_add_price_type_1;
	public $opt_add_price_1;
	public $opt_add_price_type_2;
	public $opt_add_price_2;
	
		
	/**
	 * Function to update/set each of the properties
	 * 
	 * @param array    $data       associative array mapping to properties
	 * 
	 */
	public function exchangeArray($data)
	{
		foreach ($data as $key => $value){
			$this->{$key}   = $value;
		}
	}
	
	/**
	 * transforms variables to associative array
	 * 
	 * @return array
	 */
	public function toArray()
	{
		$data = (array) $this;
		return $data;
	}		
	

	
}

