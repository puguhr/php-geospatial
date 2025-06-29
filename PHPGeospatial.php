class PHPGeospatial {
	private $type;
	private $properties = array();
	private $coordinates;
	private $geometry;

	private $geojson;
	private $wkt = array();

	private $error;

	const INSERT = 1;
	const UPDATE = 2;
	const INSERT_IGNORE = 3;
	const INSERT_UPDATE = 4;

	function __construct() 
	{

	}

	function fromGeojson($inputGeojson)
	{
		$json = json_decode($inputGeojson, true);
		if (!empty($json['features'])) 
		{
			foreach($json['features'] as $f) 
			{
				if(!empty($f['type']) && !empty($f['properties']) && !empty($f['geometry'])) 
				{
					if(strtolower($f['type'])=='feature') 
					{
						if(!empty($f['geometry']['type']) && !empty($f['geometry']['coordinates'])) 
						{
							$this->type = strtolower($f['geometry']['type']);
							$this->coordinates = $f['geometry']['coordinates'];
							$this->properties[] = $f['properties'];

							if($this->type == 'point') 
							{
								$this->wkt[] = "ST_GeomFromText(\"POINT(".implode(' ', $this->coordinates).")\")";
							}

							else if($this->type == 'multipolygon') 
							{
								$L1 = array();
								foreach($this->coordinates as $level_1) 
								{
									$L2 = array();
									foreach($level_1 as $level_2) 
									{
										$L3 = array();
										foreach($level_2 as $level_3) {
											$L3[] = implode(' ',$level_3);
										}
										$L2[] = "(".implode(',', $L3).")";
									}
									$L1[] = "(".implode(',',$L2).")";
								}
								$this->wkt[] = "ST_GeomFromText(\"MULTIPOLYGON(".implode(',', $L1).")\")";
							}

							$this->geojson = $json;

						} else
							$this->error = 'Empty geometry type or coordinates';

					} else
						$this->error = 'Not a feature';
				} else
					$this->error = 'Empty type, properties, or geometry';
			}
		} else
			$this->error = 'Empty features';
	}

	function toGeojson()
	{
		return $this->geojson;
	}

	function toWkt()
	{
		return sizeof($this->wkt)>1 ? $this->wkt : $this->wkt[0];
	}

	function getType()
	{
		return $this->type;
	}

	function getProperties()
	{
		return sizeof($this->properties)>1 ? $this->properties : $this->properties[0];
	}

	function getError()
	{
		return $this->error;
	}

	function insertSql($tableName, $geometryFieldName = 'geometry', $addFields = array(), $method = 4)
	{
		$result = array();
		for($i=0; $i<sizeof($this->properties); $i++) {
			$properties = array_merge($this->properties[$i], $addFields);
			foreach($properties as $key=>$value)
			{
				$properties[$key] = "'".str_ireplace("'", "`", $value)."'";
			}
			$properties[$geometryFieldName]=$this->wkt[$i];

			$sql = "INSERT INTO `" . $tableName . "` (" . implode(', ' , array_keys($properties)) . ") VALUES (" . implode(', ', array_values($properties)) . ")";
			
			if($method == $this::INSERT_IGNORE)
			{
				$sql = str_ireplace("INSERT INTO", "INSERT IGNORE INTO", $sql);
			}
			else if($method == $this::INSERT_UPDATE)
			{
				$update = array();
				foreach($properties as $key=>$value)
				{
					$update[] = "`" . $key . "` = " . $value;
				}
				$sql = $sql ." ON DUPLICATE KEY UPDATE " . implode(", ", $update);
			}

			$result[$i] = $sql . ";";
		}
		return sizeof($result)>1? $result : $result[0];
	}
}
