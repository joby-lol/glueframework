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
namespace glue\Tests;

use \glue\CRUDder\CRUDder;

class CRUDderTest extends PHPUnit_Extensions_Database_TestCase
{
    public static $conn = null;
    protected static $createArray1 = array(
        'string' => 'Created Object 1'
    );
    protected static $createArray2 = array(
        'string' => 'Created Object 2'
    );

    public function testCreateAndRead()
    {
        //insert one object
        $ins = BasicObject::create(static::$createArray1);
        $this->assertNotFalse($ins, 'BasicObject::create() failed');
        $this->assertEquals(1, $ins->id);
        $this->assertEquals(static::$createArray1['string'], $ins->string);
        //do it again
        $ins2 = BasicObject::create(static::$createArray2);
        $this->assertNotFalse($ins2, 'BasicObject::create() failed');
        $this->assertEquals(2, $ins2->id);
        $this->assertEquals(static::$createArray2['string'], $ins2->string);
    }

    public function testUpdateAndRead()
    {
        //add five objects to the table
        BasicObject::create(static::$createArray1);
        BasicObject::create(static::$createArray2);
        BasicObject::create(static::$createArray1);
        BasicObject::create(static::$createArray2);
        BasicObject::create(static::$createArray1);
    }

    /**
    * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
    */
    public function getConnection() {
        $pdo = new PDO('sqlite::memory:');
        //basicObject schema
        $pdo->exec('CREATE TABLE BasicObject (
            bo_id INTEGER PRIMARY KEY AUTOINCREMENT,
            bo_string VARCHAR(30) NOT NULL)');
        static::$conn = $pdo;
        //return
        return $this->createDefaultDBConnection(static::$conn);
    }
    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    public function getDataSet() {
        return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}

Class TestCRUDder extends CRUDder
{
    protected static function &getConnection() {
        return CRUDderTest::$conn;
    }
}
class BasicObject extends TestCRUDder
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
