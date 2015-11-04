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
use \glueExtras\CRUDder\DB;

$createCorrect = array(
    'string' => 'String',
    'int' => 5,
    'float' => 1.75,
    'bool' => true,
    'timestamp' => 1446530471,
    'datetime' => new \DateTime('2015-11-03T07:15:30+00:00'),
    'datetime_cformat' => new \DateTime('2015-11-03T07:15:00+00:00')
);
$createCoerced = array(
    'string' => 5.8975,
    'int' => '5',
    'float' => '1.75',
    'bool' => 1,
    'timestamp' => '1446530471',
    'datetime' => '2015-11-03T07:01:11+00:00',
    'datetime_cformat' => 'November 11, 2015, 7:15 am'
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
        $this->assertInternalType('string', $correct->string);
        $this->assertInternalType('string', $coerced->string);
        $this->assertInternalType('int', $correct->int);
        $this->assertInternalType('int', $coerced->int);
        $this->assertInternalType('float', $correct->float);
        $this->assertInternalType('float', $coerced->float);
        $this->assertInternalType('bool', $correct->bool);
        $this->assertInternalType('bool', $coerced->bool);
        $this->assertInternalType('int', $correct->timestamp);
        $this->assertInternalType('int', $coerced->timestamp);
        $this->assertInstanceOf('DateTime', $correct->datetime);
        $this->assertInstanceOf('DateTime', $coerced->datetime);
        $this->assertInstanceOf('DateTime', $correct->datetime_cformat);
        $this->assertInstanceOf('DateTime', $coerced->datetime_cformat);
        //Check equality on correct instance
        $this->assertEquals($createCorrect['string'], $correct->string);
        $this->assertEquals($createCorrect['int'], $correct->int);
        $this->assertEquals($createCorrect['float'], $correct->float);
        $this->assertEquals($createCorrect['bool'], $correct->bool);
        $this->assertEquals($createCorrect['timestamp'], $correct->timestamp);
        $this->assertEquals($createCorrect['datetime'], $correct->datetime);
        $this->assertEquals($createCorrect['datetime_cformat'], $correct->datetime_cformat);
        //Check equality on coerced instance
        $this->assertEquals($createCoerced['string'], $coerced->string);
        $this->assertEquals($createCoerced['int'], $coerced->int);
        $this->assertEquals($createCoerced['float'], $coerced->float);
        $this->assertEquals($createCoerced['bool'], $coerced->bool);
        $this->assertEquals($createCoerced['timestamp'], $coerced->timestamp);
        $this->assertNotEquals($createCoerced['datetime'], $coerced->datetime);
        $this->assertNotEquals($createCoerced['datetime_cformat'], $coerced->datetime_cformat);
    }

    /**
    * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
    */
    public function getConnection() {
        $pdo = DB::getConnection('sqlite::memory:', null, null);
        //TypesObject schema
        $pdo->exec('DROP TABLE IF EXISTS TypesObject');
        $pdo->exec('CREATE TABLE TypesObject (
            to_id INTEGER PRIMARY KEY AUTOINCREMENT,
            to_string VARCHAR(30) NOT NULL,
            to_int INTEGER NOT NULL,
            to_float REAL NOT NULL,
            to_bool INTEGER NOT NULL,
            to_timestamp INTEGER NOT NULL,
            to_datetime VARCHAR(30) NOT NULL,
            to_datetime_cformat VARCHAR(30) NOT NULL
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

Class TestCRUDder extends CRUDder {
    protected static $conn;
}
TestCRUDder::configureDB('sqlite::memory:', null, null);

class TypesObject extends TestCRUDder {
    protected static $config = array();
    protected static $formatter;
}
TypesObject::configureClass(file_get_contents(__DIR__ . '/TypesObject.yaml'));
