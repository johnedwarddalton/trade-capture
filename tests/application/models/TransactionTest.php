<?php

class TransactionTest extends PHPUnit_Framework_TestCase
{

    public function testPopulateTransactionData()
    {
        $transaction = new Application_Model_Transaction();


    	
    	$file_handle = fopen(APPLICATION_PATH . '/data/EXAMPLE.csv', "r");
    	$field_names = fgetcsv($file_handle);  //header row
		$entry = fgetcsv($file_handle);        //first row
		fclose($file_handle);
		
    	$feed_data = array_combine($field_names, $entry);
    	$feed_data['depository'] = 'DTCC';

    	$transaction->populateFromDescriptionData($feed_data);

	
        $this->assertSame($transaction->getAction(), 'NEW', 'transaction not set correctly');
        
        $trade_data = $transaction->getTradeData();
        $this->assertSame($trade_data['trade_id'], 1311449, 'trade data not set correctly');
        $this->assertSame($trade_data['inst_type'], 'IRSwap', 'categories not parsed correctly');
        $this->assertSame($trade_data['not_amount_1'], 250.0, 'size not calculated correctly');
        $this->assertSame($trade_data['term'], 10.0, 'term not calculated correctly');

    }

}