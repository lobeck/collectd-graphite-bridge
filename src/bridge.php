<?php
include('TypesDB.php');
/**
* For now this is just a proof of concept!
* Only the following collectd plugins are supported: cpu, vmem, memory, interface
* Support for the collectd types.db will be included very soon
**/

// read data pushed by collectd - it´s posted, but not recognized by php as post data
$fd = fopen('php://input','r');

// debug bridge -> graphite stream
$graphiteConn = fopen('/var/www/html/graphite.log', 'a');

$typesDBObject = new CollectdTypesDBFactory('/usr/share/collectd/types.db');
$typesDB = $typesDBObject->getTypesDB();

// open connection to carbon
//$graphiteConn = fsockopen('192.168.100.199', 2003);

#HTTP_RAW_POST_DATA works - but its not possible to fwrite it
#syslog(5,$HTTP_RAW_POST_DATA);

// decode json received by collectd
$data = json_decode($HTTP_RAW_POST_DATA);

// pushed data mostly contains multiple values - iterate over it
foreach ($data as $row) { 

	// FIXME: detect ip addresses here - add more generic approach
	$hostChunks = explode('.', $row->host);

	$typeDefinition = $typesDB[$row->type];
	
	$metricArray = array();
	$metricArray[] = 'collectd';
	$metricArray[] = $hostChunks[0];
	$metricArray[] = $row->plugin;
	if ($row->plugin_instance != '') {
		$metricArray[] = $row->plugin_instance;
	}
	$metricArray[] = $row->type;
	if ($row->type_instance != '') {
		$metricArray[] = $row->type_instance;
	}
	
	for ($i = 0; $i < count($typeDefinition); $i++) {
		$metricArray['typeDataSource'] = $typeDefinition[$i]->getName();
		$carbonDataString = array();
		$carbonDataString[] = implode('.', $metricArray);
		$carbonDataString[] = $row->values[$i];
		$carbonDataString[] = $row->time;
		
		fwrite($graphiteConn, implode(' ', $carbonDataString).PHP_EOL);
	}
}
