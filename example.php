<form method="post" enctype="multipart/form-data">
	<input type="file" name="userfile" required> 
	<input type="submit">
</form>

<?php
if (!empty($_FILES['userfile'])) {
  require_once "PHPGeospatial.php";
  $obj = new PHPGeospatial();
  $obj->fromGeojson(file_get_contents($_FILES['userfile']['tmp_name']));

  // get geospatial type
  
  echo "Geospatial type : " . $obj->getType() . "<br>";

  // get features properties
  // return as array of feature properties, if only one feature it return the first feature

  echo "Features : <pre>" . json_encode($obj->getProperties()) . "</pre> <br>";

  // get sql string to insert to database table
  // return as array of feature properties, if only one feature it return the first feature
  // the first parameter is the name of the table
  // the second is for geometry field name
  // the third is array for additional fields, for example array('updated_on'=>date('Y-m-d H:i:s'))
  // the fourth is for the the insert method : INSERT, INSERT_IGNORE, INSERT_UPDATE
  // the fifth is for the the fields mapping, for example array('old_field_name'=>'new_field_name', 'field_to_removed'=>null)

  $insertSql = $obj->insertSql('tableName', 'geometry', array(), $obj::INSERT, array());
  echo "SQL : <pre>";
  foreach ($insertSql as $sql)
	  echo $sql . "\n";
  echo "</pre>";
}
?>
