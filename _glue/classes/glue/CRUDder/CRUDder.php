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
namespace glue\CRUDder;

use \Symfony\Component\Yaml\Yaml;

abstract class CRUDder
{
    // These should be configured per-object
    protected static $cTable = null;
    protected static $cKey = null;
    protected static $cFields = array();

    // These are where state lives
    protected $cData = array();
    protected $cChanges = array();

    // These are the config options used by objects
    protected static $cConfig = array();
    protected static $cPath = "";
    protected static $cSort = null;

    // Need this to pass failures by reference
    const FALSEVAL = FALSE;

    //TODO: PDO transactions -- will need to be static and should work on the
    //entire class of objects. This may require altering CRUDder\DB to make it
    //store connections per-class instead of per-DSN. It's a slight performance
    //hit, but will allow much more intuitive and encapsulated transactions.

    /**
     * Create a new row in the table and return an object representing it
     * Note that this does create a new row in the database immediately
     * @param  [type] $cFields [description]
     * @return [type]         [description]
     */
    public static function create($cFields)
    {
        $class = get_called_class();
        $cols = array();
        $values = array();
        $conn = $class::getConnection();
        foreach ($class::$cFields as $fieldName => $fieldInfo) {
            if ($fieldName != $class::$cKey) {
                $col = $class::col($fieldName);
                if (!isset($fieldInfo['nullable']) || !$fieldInfo['nullable']) {
                    if (!isset($cFields[$fieldName])) {
                        throw new \Exception("Couldn't find field $fieldName", 1);
                    }
                }
                $cols[] = $col;
                $values[] = $conn->quote($class::prepValuePreQuery($fieldName,$cFields[$fieldName]));
            }
        }
        //build query as a string
        $query = 'INSERT INTO ' . $class::$cTable . PHP_EOL;
        $query .= '       (' . implode(', ', $cols) . ')' . PHP_EOL;
        $query .= 'VALUES (' . implode(', ', $values) . ')';
        $statement = $conn->prepare($query);
        $result = $statement->execute();
        $cKey = $conn->lastInsertId();
        return $class::read($cKey);
    }

    /**
     * Default factory returns a single item by searching on the key
     * @param  [type] $cKeyVal [description]
     * @return [type]         [description]
     */
    public static function read($cKeyVal)
    {
        $class = get_called_class();
        $result = $class::query(array(
                'sort' => false,
                'where' => '@@' . $class::$cKey . '@@ = :' . $class::$cKey,
                'limit' => 1
            ),
            array(
                $class::$cKey => $cKeyVal
            )
        );
        if (count($result) == 0) {
            return false;
        }else {
            return $result[0];
        }
    }

    /**
     * Save the current object's state into the database
     * @return boolean success or failure
     */
    public function update()
    {
        $class = get_called_class();
        $updates = array();
        $values = array(
            $class::$cKey => $this->getKey()
        );
        $conn = $class::getConnection();
        foreach ($this->cChanges as $fieldName => $c) {
            $fieldInfo = $class::$cFields[$fieldName];
            if ($fieldName != $class::$cKey) {
                $updates[] = '@@' . $fieldName . '@@ = :' . $fieldName;
                $values[$fieldName] = $class::prepValuePreQuery($fieldName,$this->__get($fieldName));
            }
        }
        //build query as a string
        $query = 'UPDATE ' . $class::$cTable . PHP_EOL;
        $query .= 'SET ' . implode(', ',$updates) . PHP_EOL;
        $query .= 'WHERE @@' . $class::$cKey . '@@ = :' . $class::$cKey . PHP_EOL;
        $query .= 'LIMIT 1';
        //Fix column names
        $query = $class::queryColNameFormatter($query);
        //Execute query
        $statement = $conn->prepare($query);
        $result = $statement->execute($values);
        if ($result) {
            $this->cChanges = array();
        }
        return $result;
    }

  //TODO: delete function

    /**
     * configure a CRUDder class from a YAML configuration string. Can set TABLE,
     * KEY, SORT and FIELDS
     * @param  string $yaml YAML string
     * @return void
     */
    public static function configFromYAML($yaml)
    {
        $data = Yaml::parse($yaml);
        $class = get_called_class();
        $class::$cTable = $data['TABLE'];
        $class::$cKey = $data['KEY'];
        $class::$cSort = $data['SORT'];
        $class::$cFields = $data['FIELDS'];
    }

    /**
     * Run a more complex query on this table
     * @param  [type] $options [description]
     * @return [type]          [description]
     */
    public static function query($options,$values=array())
    {
        $class = get_called_class();
        $defaults = array(
            'where'=>false,
            'sort'=>$class::$cSort,
            'limit'=>0,
            'offset'=>0,
        );
        $options = array_replace_recursive($defaults,$options);
        //build query as a string
        //SELECT FROM statement
        $query = 'SELECT ';
        $cols = array();
        foreach ($class::$cFields as $fieldName => $fieldInfo) {
            $cols[] = $fieldInfo['col'];
        }
        $query .= implode(', ', $cols) . PHP_EOL;
        $query .= 'FROM ' . $class::$cTable . PHP_EOL;
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
        if ($statement->execute($values) === false) {
            return false;
        }
        $results = $statement->fetchAll();
        $objects = array();
        foreach ($results as $row) {
            $objects[] = new $class($row);
        }
        return $objects;
    }

    /**
     * Preps a value for use in a query -- needs the value's type from this
     * object's config so that it knows what it's supposed to look like to SQL
     * @param  string $cKey   the name of the field
     * @param  mixed  $value the value to return a SQL representation of
     * @return string
     */
    protected static function prepValuePreQuery($cKey,$value) {
        $class = get_called_class();
        $type = $class::$cFields[$cKey]['type'];
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
        if (isset($class::$cFields[$cKey]['regexValidator'])) {
            if (!preg_match($class::$cFields[$cKey]['regexValidator'],$value)) {
                throw new \Exception("Invalid content for $class - $cKey (regexValidator)", 1);
            }
        }
        if (isset($class::$cFields[$cKey]['regexInvalidator'])) {
            if (preg_match($class::$cFields[$cKey]['regexInvalidator'],$value)) {
                throw new \Exception("Invalid content for $class - $cKey (regexInvalidator)", 1);
            }
        }
        //return
        return $value;
    }

    /**
     * runs on values returned from the database to make them into appropriate
     * PHP objects based on their type specified in object's config
     * @param  string $cKey
     * @param  string $value
     * @return mixed
     */
    protected static function prepValuePostQuery($cKey,$value) {
        $class = get_called_class();
        $type = $class::$cFields[$cKey]['type'];
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
     * Replace @@field@@ references in a query with the current class' actual column
     * names.
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    protected static function queryColNameFormatter($string) {
        $class = get_called_class();
        $string = preg_replace_callback('/@@([^@]+)@@/',function($match) use ($class) {
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
     */
    protected function __construct($input) {
        $class = get_called_class();
        foreach ($class::$cFields as $fieldID => $fieldInfo) {
            $this->cData[$fieldID] = $class::prepValuePostQuery($fieldID,$input[$fieldInfo['col']]);
        }
    }

    /**
     * Get the proper column name of a field
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    public static function col($field) {
        $class = get_called_class();
        if (isset($class::$cFields[$field])) {
            return $class::$cFields[$field]['col'];
        }
        return false;
    }

    /**
     * get the value of the current object's key column
     * @return mixed/false returns key value or false on new (un-written) objects
     */
    public function getKey() {
        $class = get_called_class();
        return $this->cData[$class::$cKey];
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
        foreach ($class::$cFields as $fieldID => $fieldInfo) {
            if (isset($this->cChanges[$fieldID]) && $this->cChanges[$fieldID]) {
                $dump .= "<tr bgcolor='#ffc'>";
            }else {
                $dump .= "<tr bgcolor='#fff'>";
            }
            $dump .= "<td align='right'>$fieldID</td>";
            $value = $this->$fieldID;
            $type = $class::$cFields[$fieldID]['type'];
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
            if (isset($this->cChanges[$fieldID])) {
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
        return $dump;
    }

    /**
     * Use a generic getter so that objects can be used in the manner $someVariable = $instance->fieldName
     * @param  string $name the name of the field to get
     * @return mixed     the value of the field by reference, could be almost anything
     */
    public function &__get($name) {
        if (array_key_exists($name, $this->cData)) {
            return $this->cData[$name];
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
		if (array_key_exists($name, $this->cData) && $name != $class::$cKey) {
			$this->cChanges[$name] = true;
			$this->cData[$name] = $value;
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
            static::$cConfig['DB']['DSN'],
            static::$cConfig['DB']['USERNAME'],
            static::$cConfig['DB']['PASSWORD']
        );
    }
    /**
     * Set connection parameters for a particular class
     * @param string $dsn      database connection string
     * @param string $username database username
     * @param string $password database password
     */
    public static function setCredentials($dsn,$username,$password) {
        static::$cConfig['DB']['DSN'] = $dsn;
        static::$cConfig['DB']['USERNAME'] = $username;
        static::$cConfig['DB']['PASSWORD'] = $password;
    }
}
