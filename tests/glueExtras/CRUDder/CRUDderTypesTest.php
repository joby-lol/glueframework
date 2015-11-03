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
namespace glue\CRUDder\TypesTest;

use \glueExtras\CRUDder\CRUDder;
use \glueExtras\CRUDder\CRUDderFormatter;
use \glueExtras\CRUDder\DB;

$createCorrect = array(
    'string' => 'String',
    'int' => 5,
    'float' => 1.75,
    'bool' => true,
    'datetime_timestamp' => 1446530471,
    'datetime_string' => new \DateTime('2015-11-03T07:01:11+01:00')
);
$createCoerced = array(
    'string' => 5.8975,
    'int' => '5',
    'float' => '1.75',
    'bool' => 1,
    'datetime_timestamp' => '1446530471',
    'datetime_string' => '2015-11-03T07:01:11+01:00'
);

class CRUDderTypesTest extends \PHPUnit_Extensions_Database_TestCase
{
    public static $conn = null;

    public function testTypeBasics()
    {
        global $createCorrect, $createCoerced;
        $correct = TypesObject::create($createCorrect);
        $coerced = TypesObject::create($createCoerced);
        //Both should successfully create an object
        $this->assertNotFalse($correct);
        $this->assertNotFalse($coerced);
        //Check types
        $this->assertInternalType('string',$correct->string);
        $this->assertInternalType('string',$coerced->string);
        $this->assertInternalType('int',$correct->int);
        $this->assertInternalType('int',$coerced->int);
        $this->assertInternalType('float',$correct->float);
        $this->assertInternalType('float',$coerced->float);
        $this->assertInternalType('bool',$correct->bool);
        $this->assertInternalType('bool',$coerced->bool);
        $this->assertInternalType('int',$correct->timestamp);
        $this->assertInternalType('int',$coerced->timestamp);
        $this->assertInstanceOf('DateTime',$correct->datetime);
        $this->assertInstanceOf('DateTime',$coerced->datetime);
    }

    /**
    * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
    */
    public function getConnection() {
        $pdo = DB::getConnection('sqlite::memory:', null, null);
        //basicObject schema
        $pdo->exec('DROP TABLE IF EXISTS TypesObject');
        $pdo->exec('CREATE TABLE TypesObject (
            to_id INTEGER PRIMARY KEY AUTOINCREMENT,
            to_string VARCHAR(30) NOT NULL,
            to_int INTEGER NOT NULL,
            to_float REAL NOT NULL,
            to_bool INTEGER NOT NULL,
            to_timestamp INTEGER NOT NULL,
            to_datetime_string VARCHAR(30) NOT NULL
        )');
        static::$conn = &$pdo;
        //return
        return $this->createDefaultDBConnection(static::$conn);
    }
    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    public function getDataSet() {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}

Class TestCRUDder extends CRUDder {}
TestCRUDder::configureDB('sqlite::memory:', null, null);

class TypesObject extends TestCRUDder
{
    protected static $cTable = 'BasicObject';
    protected static $cKey = 'id';
    protected static $cSort = '@@id@@ DESC';
    protected static $cFields = array(
        'id' => array(
            'col' => 'bo_id',
            'type' => 'int'
        ),
        'string' => array(
            'col' => 'bo_string',
            'type' => 'string'
        )
    );
}
TypesObject::configureClass(file_get_contents(__DIR__ . '/BasicObject.yaml'));
