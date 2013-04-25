alter table tradecapture.`trade` add column `not_amt_capped` bool after `not_amount_1`;
alter table tradecapture.`trade_archive` add column `not_amt_capped` bool after `not_amount_1`;