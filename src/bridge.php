<?php
include('TypesDB.php');
/**
* For now this is just a proof of concept!
* Only the following collectd plugins are supported: cpu, vmem, memory, interface
* Support for the collectd types.db will be included very soon
**/

// read data pushed by collectd - it¬¥s posted, but not recognized by php as post data
$fd = fopen('php://input','r');

// debug bridge -> graphite stream
//$graphiteConn = fopen('/var/www/html/graphite.log', 'a');

$typesDBObject = new CollectdTypesDBFactory('/usr/share/collectd/types.db');
$typesDB = $typesDBObject->getTypesDB();

// open connection to carbon
$graphiteConn = fsockopen('192.168.100.199', 2003);

#HTTP_RAW_POST_DATA works - but its not possible to fwrite it
#syslog(5,$HTTP_RAW_POST_DATA);

// decode json received by collectd
$data = json_decode($HTTP_RAW_POST_DATA);

// pushed data mostly contains multiple values - iterate over it
foreach ($data as $row) { 

	// FIXME: detect ip addresses here - add more generic approach
	$hostChunks = explode('.', $row->host);

	// FIXME: plugin instance should not be included if not existent
	$pluginInstance = 'default';

	if ($row->plugin_instance != '') {
		$pluginInstance = $row->plugin_instance;
	}
	
	$typeDefinition = $typesDB[$row->type];
	
	for ($i = 0; $i < count($typeDefinition); $i++) {
		fwrite($graphiteConn, 'collectd.'.$hostChunks[0].'.'.$row->plugin.'.'.$pluginInstance.'.'.$row->type.'.'.$row->type_instance.'.'. $typeDefinition[$i]->getName().' '.$row->values[$i].' '.$row->time.PHP_EOL);
	}
}

