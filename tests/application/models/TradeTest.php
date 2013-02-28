<?php

class TradeTest extends PHPUnit_Framework_TestCase
{
    public function testTradeInitialState()
    {
        $trade = new Application_Model_Trade();   
        $this->assertNull($trade->trade_id, '"trade_id" should initially be null');
        $this->assertNull($trade->execution_date, '"execution_date" should initially be null');
        $this->assertNull($trade->price, '"price" should initially be null');
    }

    public function testExchangeArraySetsPropertiesCorrectly()
    {
        $trade = new Application_Model_Trade();
        $data  = array('trade_id' => '123',
                       'execution_date'     => date('2010-03-01'),
                       'price'  => 3.50);

        $trade->exchangeArray($data);

        $this->assertSame($data['trade_id'], $trade->trade_id, '"trade_id" was not set correctly');
        $this->assertSame($data['execution_date'], $trade->execution_date, '"execution_date" was not set correctly');
        $this->assertSame($data['price'], $trade->price, '"price" was not set correctly'); 

		$this->assertNull($trade->eff_date, '"eff_date" should have defaulted to null');
        $this->assertNull($trade->currency, '"currency" should have defaulted to null');
        $this->assertNull($trade->inst_type, '"inst_type" should have defaulted to null');
    }

}