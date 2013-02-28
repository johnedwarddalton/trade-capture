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
	public $creation_date;
	public $trade_id;
	public $execution_date;
	public $eff_date;
	public $end_date;
	public $term;
	public $currency;
	public $asset_class;
	public $category;
	public $inst_type;
	public $inst_subtype;
	public $und_asset_1;
	public $und_asset_2;
	public $price_type;
	public $price;
	public $not_curr_1;
	public $not_curr_2;
	public $not_amount_1;
	public $not_amount_2;
	public $opt_embed;
	public $opt_strike;
	public $opt_type;
	public $opt_curr;
	public $opt_premium;
	public $opt_start;
	public $opt_expiry;
	public $opt_term;
	
		
	/**
	 * Function to update/set each of the properties
	 * 
	 * @param array    $data       associative array mapping to properties
	 * 
	 */
	public function exchangeArray($data)
	{
		$this->creation_date   = (isset($data['creation_date'])) ? $data['creation_date'] : null;
		$this->trade_id    	= (isset($data['trade_id'])) ? $data['trade_id'] : null;
		$this->execution_date  = (isset($data['execution_date'])) ? $data['execution_date'] : null;
		$this->eff_date     = (isset($data['eff_date'])) ? $data['eff_date'] : null;
		$this->end_date     = (isset($data['end_date'])) ? $data['end_date'] : null;
		$this->term   = 	(isset($data['term'])) ? $data['term'] : null;
		$this->currency     = (isset($data['currency'])) ? $data['currency'] : null;
		$this->asset_class  = (isset($data['asset_class'])) ? $data['asset_class'] : null;
		$this->category     = (isset($data['category'])) ? $data['category'] : null;
		$this->inst_type    = (isset($data['inst_type'])) ? $data['inst_type'] : null;
		$this->inst_subtype   = (isset($data['inst_subtype'])) ? $data['inst_subtype'] : null;
		$this->und_asset_1    = (isset($data['und_asset_1'])) ? $data['und_asset_1'] : null;
		$this->und_asset_2    = (isset($data['und_asset_2'])) ? $data['und_asset_2'] : null;
		$this->price_type     = (isset($data['price_type'])) ? $data['price_type'] : null;
		$this->price     = 	(isset($data['price'])) ? $data['price'] : null;
		$this->not_curr_1 = (isset($data['not_curr_1'])) ? $data['not_curr_1'] : null;
		$this->not_curr_2 = (isset($data['not_curr_1'])) ? $data['not_curr_1'] : null;
		$this->not_amount_1 = (isset($data['not_amount_1'])) ? $data['not_amount_1'] : null;
		$this->not_amount_2 = (isset($data['not_amount_1'])) ? $data['not_amount_1'] : null;
		$this->opt_embed = (isset($data['opt_embed'])) ? $data['opt_embed'] : null;
		$this->opt_strike = (isset($data['opt_strike'])) ? $data['opt_strike'] : null;
		$this->opt_type = (isset($data['opt_type'])) ? $data['opt_type'] : null;
		$this->opt_curr = (isset($data['opt_curr'])) ? $data['opt_curr'] : null;
		$this->opt_premium = (isset($data['opt_premium'])) ? $data['opt_premium'] : null;
		$this->opt_start = (isset($data['opt_start'])) ? $data['opt_start'] : null;
		$this->opt_expiry = (isset($data['opt_expiry'])) ? $data['opt_expiry'] : null;
		$this->opt_term = (isset($data['opt_term'])) ? $data['opt_term'] : null;
	}
	
	/**
	 * transforms variables to associative array
	 * 
	 * @return array
	 */
	public function toArray()
	{
		$data = array();
		$data['creation_date']	= 	$this->creation_date;
		$data['trade_id'] 		=	$this->trade_id;
		$data['execution_date'] = 	$this->execution_date;
		$data['eff_date']		=	$this->eff_date;
		$data['end_date']		=	$this->end_date;
		$data['term']			=	$this->term;
		$data['currency']		=	$this->currency;
		$data['asset_class']	=	$this->asset_class;
		$data['category']		=	$this->category;
		$data['inst_type']		=	$this->inst_type;
		$data['inst_subtype']	=	$this->inst_subtype;
		$data['und_asset_1']	=	$this->und_asset_1;
		$data['und_asset_2']	=	$this->und_asset_2;
		$data['price_type']		=	$this->price_type;
		$data['price']			=	$this->price;
		$data['not_curr_1']		=	$this->not_curr_1;
		$data['not_curr_2']		=	$this->not_curr_2;
		$data['not_amount_1']	=	$this->not_amount_1;
		$data['not_amount_2']	=	$this->not_amount_2;
		$data['opt_embed']		=	$this->opt_embed;
		$data['opt_strike']		=	$this->opt_strike;
		$data['opt_type']		=	$this->opt_type;
		$data['opt_curr']		=	$this->opt_curr;
		$data['opt_premium']	=	$this->opt_premium;
		$data['opt_start']		=	$this->opt_start;
		$data['opt_expiry']		=	$this->opt_expiry;
		$data['opt_term']		=	$this->opt_term;
		
		return $data;
	}		
	
}

