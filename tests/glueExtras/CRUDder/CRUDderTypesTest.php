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

class CRUDderTypesTest extends \PHPUnit_Extensions_Database_TestCase
{
    public static $conn = null;
    // protected static $createArray1 = array(
    //     'string' => 'Created Object 1'
    // );
    // protected static $createArray2 = array(
    //     'string' => 'Created Object 2'
    // );

    public function testStrings()
    {

    }
    public function testNumerics()
    {

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
            to_datetime_timestamp INTEGER NOT NULL,
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
