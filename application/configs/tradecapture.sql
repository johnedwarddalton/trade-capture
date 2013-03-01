
-- Host: localhost    Database: tradecapture
-- ------------------------------------------------------
-- Server version	5.5.8

-- Table structure for table `trade`
--

DROP TABLE IF EXISTS `trade`;

CREATE TABLE `trade` (
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `trade_id` int(11) NOT NULL,
  `execution_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `eff_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `term` double DEFAULT NULL,
  `currency` varchar(4) DEFAULT NULL,
  `asset_class` varchar(20) DEFAULT NULL,
  `category` varchar(20) DEFAULT NULL,
  `inst_type` varchar(20) DEFAULT NULL,
  `inst_subtype` varchar(20) DEFAULT NULL,
  `und_asset_1` varchar(20) DEFAULT NULL,
  `und_asset_2` varchar(20) DEFAULT NULL,
  `price_type` varchar(20) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `not_curr_1` varchar(4) DEFAULT NULL,
  `not_curr_2` varchar(4) DEFAULT NULL,
  `not_amount_1` double DEFAULT NULL,
  `not_amount_2` double DEFAULT NULL,
  `opt_embed` varchar(20) DEFAULT NULL,
  `opt_strike` double DEFAULT NULL,
  `opt_type` varchar(20) DEFAULT NULL,
  `opt_curr` varchar(4) DEFAULT NULL,
  `opt_premium` double DEFAULT NULL,
  `opt_start` date DEFAULT NULL,
  `opt_expiry` date DEFAULT NULL,
  `opt_term` double DEFAULT NULL,
  PRIMARY KEY (`trade_id`),
  UNIQUE KEY `id_UNIQUE` (`trade_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

