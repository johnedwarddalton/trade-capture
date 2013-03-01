Trade Capture Application
=======================

Introduction
------------
This application provides two functions

i) Capture, processing and storage of the rates feed DATA from DTCC (see http://www.dtcc.com/products/derivserv/suite/us_swap_data_repository.php).  

ii)  Implementation of json-based, REST interface for data retrieval.


Installation
------------

The system requires access to a database with table called 'trade'.  This can be created by importing the SQL file 'tradecapture.sql' stored in the config directory. Application parameters are stored in the application.ini file 


Capture data
------------

A capture of a single batch of data from the RSS feed is initiated by a call to the URL:  'capture/download'.  The RSS Reader will perform a conditional GET from the server and only update from the web if the data is new. 

The RSS feed is updated sporadically.  This necessitates continuous checking.  At present, this is done via the commandLine tool 'refreshLoop.php' which is designed to run at set intervals, up to a certain maximum number.   


REST API
------------

GET rest/retrieve/format/json/trade/ID   returns an object representing a single trade

If trade is not specified, four other optional parameters can be specified: type, currency, since, minimum.

So, for example:
GET rest/retrieve/format/json/type/Option/currency/USD/since/3/minimum/10
returns an array of trades representing Dollar Options of at least 10mn in size executed in the last 3 hours

A similar endpoint at rest/table retrieves the same information formatted for use with the jQuery dataTables plug-in 

  

   

