Trade Capture Application
=======================

Introduction
------------
This application provides two functions

i) Capture, processing and storage of the rates feed DATA from DTCC (see http://www.dtcc.com/products/derivserv/suite/us_swap_data_repository.php).  

ii)  Implementation of json-based, REST interface for data retrieval.


Installation
------------

The system requires access to a database with table called 'trade'.  This can be created by importing the SQL file 'tradecapture.sql' stored in the config directory. Application parameters are stored in the application.ini file.  If required, a test database can also be specified in the configuration file. 


Capture data
------------

The capture mechanism is set up to run only from the command line.  There are four possible actions:

Download data:  php index.php capture download [-v/verbose].
This initiates a one-off download and save of the RSS feed.

Download at intervals:  php index.php capture loop
Sets download to run at regular intervals for a maximum specified time.  The time between intervals and the cutoff time are specified in the configuration file.

Upload from a CSV file: php index.php capture upload [-f/file FILENAME]
Uploads data such as a daily download obtained from DTCC.  If no filename is specified, the RATES.csv in the data directory is assumed.

Archive data:  php index.php capture archive [-v/verbose]

 


REST API
------------

Single trades:
GET rest/retrieve/format/json/trade/ID   returns an object representing a single trade (sepcified by id);

Multiple trades:             

the `table` endpoint is designed to return JSON-formatted data suitable for use with the jQuery dataTables library.

Four optional parameters are allowed:
type - instrument type
currency - trade currency
since - time in hours of earliest trade
minimum - minimum trade size

Example:

GET rest/table/format/json/type/Option/currency/USD/since/3/minimum/10
returns an array of trades representing Dollar Options of at least 10mn in size executed in the last 3 hours


  

   

