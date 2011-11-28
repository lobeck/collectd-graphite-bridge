<?php

class CollectdType {
	private $name;
	private $type;
	private $min;
	private $max;

	public function __construct($name, $type, $min, $max) {
		$this->name = $name;
		$this->type = $type;
		$this->min = $min;
		$this->max = $max;
	}
	
	public function getName() {
		return $this->name;
	}
}

class CollectdTypesDBFactory {
	private $typesDBFilePath;
	private $cachedTypes;
	
	public function __construct($filePath) {
		$this->typesDBFilePath = $filePath;
		
		if (function_exists('apc_fetch')) {
			$returnValue = false;
			syslog(5, 'retrieving cached typesDB');
			$this->cachedTypes = apc_fetch('cachedTypesDB', $retunValue);
			if ($returnValue) return;
		}
		
		$typesDBFileHandle = fopen($this->typesDBFilePath, 'r');
		$typesDBData = array();
		
		while(($typeLine = fgets($typesDBFileHandle)) !== false) {
			$typeLine = preg_replace('/\s+/', "\t", $typeLine);
			$typeData = explode("\t", $typeLine, 2);
			
			$typeObjects = array();
			
			$typeName = $typeData[0];
			$typeDefinition = explode(',',$typeData[1]);
			
			
			foreach ($typeDefinition as $type) {
				$typeChunks = explode(':',trim($type));
				$typeObject = new CollectdType($typeChunks[0], $typeChunks[1], $typeChunks[2], $typeChunks[3]);
				$typeObjects[] = $typeObject;
			}
			
			//$typeObject = new CollectdType($name, $type, $min, $max)
			$typesDBData[$typeName] = $typeObjects;
		}
		$this->cachedTypes = $typesDBData;
		if (function_exists('apc_store')) {
			apc_store('cachedTypesDB', $this->cachedTypes);
			syslog(5, 'pushing new typesDB');
		}
	}
	
	public function getTypesDB() {
		return $this->cachedTypes;
	}

}

