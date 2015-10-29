<?php
/**
  * Glue Framework
  * Copyright (C) 2015 Joby Elliott
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License along
  * with this program; if not, write to the Free Software Foundation, Inc.,
  * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
namespace CRUDder;
abstract class CRUDder {
  // These should be configured per-object
  protected static $KEY = null;
  protected static $FIELDS = array();

  // These are where state lives
  protected $DATA = array();
  protected $CHANGED = array();
  protected $ISNEW = false;

  // These are the config options used by objects
  protected static $CONFIG = array();
  protected static $PATH = "";
  protected static $SORT = null;

  // Need this to pass failures by reference
  const FALSEVAL = FALSE;

  /**
   * Create a new row in the table and return an object representing it
   * Note that this does create a new row in the database immediately
   * @param  [type] $fields [description]
   * @return [type]         [description]
   */
  public static function create($fields) {
    $class = get_called_class();
    $cols = array();
    $values = array();
    $conn = $class::getConnection();
    foreach ($class::$FIELDS as $fieldName => $fieldInfo) {
      if ($fieldName != $class::$KEY) {
        $col = $class::col($fieldName);
        if (!isset($fieldInfo['nullable']) || !$fieldInfo['nullable']) {
          if (!isset($fields[$fieldName])) {
            throw new \Exception("Couldn't find field $fieldName", 1);
          }
        }
        $cols[] = $col;
        $values[] = $conn->quote($class::prepValuePreQuery($fieldName,$fields[$fieldName]));
      }
    }
    $query = 'INSERT INTO ' . $class::$TABLE . PHP_EOL;
    $query .= '       (' . implode(', ',$cols) . ')' . PHP_EOL;
    $query .= 'VALUES (' . implode(', ',$values) . ')';
    $statement = $conn->prepare($query);
    $statement->execute();
    $key = $conn->lastInsertId();
    return $class::read($key);
  }

  public static function prepValuePreQuery($key,$value) {
    $class = get_called_class();
    $type = $class::$FIELDS[$key]['type'];
    if (is_a($value,'\CRUDder\CRUDder')) {
      $value = $value->getKey();
    }
    //date/time handlers
    if (is_a($value,'DateTime')) {
      if ($type == 'date') {
        $value = $value->format('Y-m-d');
      }else {
        $value = $value->format('Y-m-d H:i:s');
      }
    }
    //pass strings right through
    if ($type == 'string') {
      return $value;
    }
    //numeric handlers
    if ($type == 'int') {
      //$value = round($value);
    }
    if ($type == 'number') {
      //$value = floatval($value);
    }
    //boolean handler
    if ($type == 'boolean') {
      if ($value) {
        $value = 1;
      }else {
        $value = 0;
      }
    }
    //regex validation and invalidation
    if (isset($class::$FIELDS[$key]['regexValidator'])) {
      if (!preg_match($class::$FIELDS[$key]['regexValidator'],$value)) {
        throw new \Exception("Invalid content for $class - $key (regexValidator)", 1);
      }
    }
    if (isset($class::$FIELDS[$key]['regexInvalidator'])) {
      if (preg_match($class::$FIELDS[$key]['regexInvalidator'],$value)) {
        throw new \Exception("Invalid content for $class - $key (regexInvalidator)", 1);
      }
    }
    //return
    return $value;
  }

  /**
   * Default factory returns a single item by searching on the key
   * @param  [type] $keyVal [description]
   * @return [type]         [description]
   */
  public static function read($keyVal) {
    $class = get_called_class();
    $result = $class::query(array(
      'sort' => $class::$SORT,
      'where' => '@' . $class::$KEY . '@ = :' . $class::$KEY,
      'limit' => 1
    ),
    array(
      $class::$KEY => $keyVal
    ));
    if (count($result) == 0) {
      return false;
    }else {
      return $result[0];
    }
  }

  /**
   * Run a more complex query on this table
   * @param  [type] $options [description]
   * @return [type]          [description]
   */
  public static function query($options,$values=array()) {
    $class = get_called_class();
    $defaults = array(
      'where'=>false,
      'sort'=>$class::$SORT,
      'limit'=>0,
      'offset'=>0,
    );
    $options = array_replace_recursive($defaults,$options);
    //build query as a string
    //SELECT FROM statement
      $query = 'SELECT ';
      $cols = array();
      foreach ($class::$FIELDS as $fieldName => $fieldInfo) {
        $cols[] = $fieldInfo['col'];
      }
      $query .= implode(', ', $cols) . PHP_EOL;
      $query .= 'FROM ' . $class::$TABLE . PHP_EOL;
    //WHERE statement
    if ($options['where']) {
      $query .= 'WHERE' . PHP_EOL;
      $query .= $options['where'] . PHP_EOL;
    }
    //ORDER BY statement
    if ($options['sort']) {
      $query .= 'ORDER BY' . PHP_EOL;
      $query .= $options['sort'] . PHP_EOL;
    }
    //LIMIT/OFFSET statement
    if ($options['limit']) {
      $query .= 'LIMIT ' . $options['limit'] .PHP_EOL;
    }
    if ($options['offset']) {
      $query .= 'OFFSET ' . $options['offset'] .PHP_EOL;
    }
    //Fix column names
    $query = $class::queryColNameFormatter($query);
    //Retrieve result
    $conn = $class::getConnection();
    $statement = $conn->prepare($query);
    $statement->execute($values);
    $results = $statement->fetchAll();
    $objects = array();
    foreach ($results as $row) {
      $objects[] = new $class($row);
    }
    return $objects;
  }

  /**
   * Replace @field@ references in a query with the current class' actual column
   * names.
   * @param  [type] $string [description]
   * @return [type]         [description]
   */
  protected static function queryColNameFormatter($string) {
    $class = get_called_class();
    $string = preg_replace_callback('/@([^@]+)@/',function($match) use ($class) {
      $col = $class::col($match[1]);
      if ($col) {
        return $col;
      }else {
        throw new \Exception("Couldn't find a field named " . $match[1], 1);
      }
    },$string);
    return $string;
  }

  /**
   * constructor should only be called by factories, so it's protected
   * @param array $input PDO results from factory, or data from create() if you're building a whole new entry
   * @param boolean $isNew whether or not this is a new entry
   */
  protected function __construct($input,$isNew=FALSE) {
    $class = get_called_class();
    foreach ($class::$FIELDS as $fieldID => $fieldInfo) {
      $this->DATA[$fieldID] = $class::prepValuePostQuery($fieldID,$input[$fieldInfo['col']]);
    }
  }

  protected static function prepValuePostQuery($key,$value) {
    $class = get_called_class();
    $type = $class::$FIELDS[$key]['type'];
    switch ($type) {
      case 'date':
        $value = date_create($value);
        break;
      case 'datetime':
        $value = date_create($value);
        break;
    }
    return $value;
  }

  /**
   * Get the proper column name of a field
   * @param  [type] $field [description]
   * @return [type]        [description]
   */
  public static function col($field) {
    $class = get_called_class();
    if (isset($class::$FIELDS[$field])) {
      return $class::$FIELDS[$field]['col'];
    }
    return false;
  }

  /**
   * get the value of the current object's key column
   * @return mixed/false returns key value or false on new (un-written) objects
   */
  public function getKey() {
    $class = get_called_class();
    if ($this->ISNEW) {
      return false;
    }else {
      return $this->DATA[$class::$KEY];
    }
  }

  /**
   * convert to string
   * @return string class and key value
   */
  public function __toString() {
    return get_called_class() . "(".$this->getKey().")";
  }

  /**
   * Utility function for debug output
   * @return [type] [description]
   */
  public function dump($echo=false) {
    $class = get_called_class();
    $dump = "<table border='1' cellspacing='0' cellpadding='5' class='CRUDder-dump'>\n";
    $dump .= "<tr bgcolor='#eee'><td colspan='2'><strong>$class</strong></td><td>Changed</td></tr>\n";
    foreach ($class::$FIELDS as $fieldID => $fieldInfo) {
      if (isset($this->CHANGED[$fieldID]) && $this->CHANGED[$fieldID]) {
        $dump .= "<tr bgcolor='#ffc'>";
      }else {
        $dump .= "<tr bgcolor='#fff'>";
      }
      $dump .= "<td align='right'>$fieldID</td>";
      $value = $this->$fieldID;
      $type = $class::$FIELDS[$fieldID]['type'];
      switch ($type) {
        case 'date':
          $dump .= "<td>".$this->__get($fieldID)->format('Y-m-d H:i:s')."</td>";
          break;
        case 'datetime':
          $dump .= "<td>".$this->__get($fieldID)->format('Y-m-d H:i:s')."</td>";
          break;
        default:
          $dump .= "<td>".$this->$fieldID."</td>";
      }
      if (isset($this->CHANGED[$fieldID])) {
        $dump .= "<td>X</td>";
      }else {
        $dump .= "<td>&nbsp;</td>";
      }
      $dump .= "</tr>\n";
    }
    $dump .= "</table>";
    if ($echo) {
      echo $dump;
    }
    var_dump($this->CHANGED);
    return $dump;
  }

  /**
   * Use a generic getter so that objects can be used in the manner $someVariable = $instance->fieldName
   * @param  string $name the name of the field to get
   * @return mixed     the value of the field, could be almost anything
   */
  public function &__get($name) {
    if (array_key_exists($name, $this->DATA)) {
      return $this->DATA[$name];
    }
    return static::FALSEVAL;
  }

  /**
	 * Use a generic setter so that objects can be used in the manner $instance->fieldName = "some value"
	 * @param string $name the name of the field to set
	 * @param mixed $value the value to place in it
	 * @return mixed the value of the field, could be almost anything
	 */
	public function __set($name,$value) {
    $class = get_called_class();
		if (array_key_exists($name, $this->DATA) && $name != $class::$KEY) {
			$this->CHANGED[$name] = true;
			$this->DATA[$name] = $value;
			return true;
		}
		return false;
	}

  /**
   * internal method for getting a database connection
   * @return [type] [description]
   */
  protected static function &getConnection() {
    return DB::getConnection(
      static::$CONFIG['DB']['DSN'],
      static::$CONFIG['DB']['USERNAME'],
      static::$CONFIG['DB']['PASSWORD']
    );
  }
  /**
   * Set connection parameters for a particular class
   * @param string $DSN      database connection string
   * @param string $USERNAME database username
   * @param string $PASSWORD database password
   */
  public static function setCredentials($DSN,$USERNAME,$PASSWORD) {
    static::$CONFIG['DB']['DSN'] = $DSN;
    static::$CONFIG['DB']['USERNAME'] = $USERNAME;
    static::$CONFIG['DB']['PASSWORD'] = $PASSWORD;
  }
}
