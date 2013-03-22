
-- Host: localhost    Database: tradecapture
-- ------------------------------------------------------
-- Server version	5.5.8

-- Table structure for table `trade`
--

DROP TABLE IF EXISTS `trade`;

CREATE TABLE `trade` (
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `depository` varchar(20) DEFAULT NULL,
  `trade_id` int(11) NOT NULL,
  `execution_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', 
  `cleared` varchar(4) DEFAULT NULL,
  `collat` varchar(4) DEFAULT NULL,
  `except` varchar(4) DEFAULT NULL,
  `other_price` varchar(4) DEFAULT NULL,
  `block` varchar(4) DEFAULT NULL,
  `venue` varchar(4) DEFAULT NULL,  
  `eff_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `term` double DEFAULT NULL, 
  `dcc` varchar(10) DEFAULT NULL,
  `currency` varchar(4) DEFAULT NULL,
  `trans_type` varchar(20) DEFAULT NULL,
  `asset_class` varchar(10) DEFAULT NULL, 
  `sub_asset_class` varchar(10) DEFAULT NULL,
  `category` varchar(20) DEFAULT NULL,
  `inst_type` varchar(20) DEFAULT NULL,
  `inst_subtype` varchar(20) DEFAULT NULL,
  `und_asset_1` varchar(20) DEFAULT NULL,
  `und_asset_2` varchar(20) DEFAULT NULL,
  `price_type` varchar(20) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `add_price_type` varchar(20) DEFAULT NULL,
  `add_price` varchar(20) DEFAULT NULL,
  `not_curr_1` varchar(4) DEFAULT NULL,
  `not_curr_2` varchar(4) DEFAULT NULL,
  `not_amount_1` double DEFAULT NULL,
  `not_amount_2` double DEFAULT NULL,
  `pay_freq_1` varchar(10) DEFAULT NULL,
  `pay_freq_2` varchar(10) DEFAULT NULL,
  `reset_freq_1` varchar(10) DEFAULT NULL,
  `reset_freq_2` varchar(10) DEFAULT NULL,  
  `opt_embed` varchar(20) DEFAULT NULL,
  `opt_strike` double DEFAULT NULL,
  `opt_type` varchar(10) DEFAULT NULL,
  `opt_family` varchar(2) DEFAULT NULL,
  `opt_curr` varchar(4) DEFAULT NULL,
  `opt_premium` varchar(20) DEFAULT NULL,
  `opt_start` date DEFAULT NULL,
  `opt_expiry` date DEFAULT NULL,
  `opt_tenor` int DEFAULT NULL,
  `opt_add_price_type_1` varchar(20) DEFAULT NULL,
  `opt_add_price_1` double DEFAULT NULL,
  `opt_add_price_type_2` varchar(20) DEFAULT NULL,
  `opt_add_price_2` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`trade_id`),
  UNIQUE KEY `id_UNIQUE` (`trade_id`),
  INDEX `execution_date` (`execution_date`),
  INDEX `currency` (`currency`),
  INDEX `inst_type` (`inst_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;  


-- Create trade archive table

DROP TABLE IF EXISTS `trade_archive`;

CREATE TABLE `trade_archive` LIKE `trade`;  


