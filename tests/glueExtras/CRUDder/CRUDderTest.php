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
namespace glueExtras\CRUDder\CoreTest;

use \glueExtras\CRUDder\CRUDder;
use \glueExtras\CRUDder\CRUDderFormatter;
use \glueExtras\CRUDder\DB;

class CRUDderTest extends \PHPUnit_Extensions_Database_TestCase
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
        $f1 = BasicObject::create(static::$createArray1);
        $f2 = BasicObject::create(static::$createArray2);
        $f3 = BasicObject::create(static::$createArray1);
        $f4 = BasicObject::create(static::$createArray2);
        $f5 = BasicObject::create(static::$createArray1);
        //edit items' fields
        $f1->string = 'Updated object 1';
        $f1->update();
        $f3->string = 'Updated object 3';
        $f3->update();
        $f4->string = 'Updated object 4';
        $f4->update();
        //re-create items
        $f1 = BasicObject::read($f1->id);
        $f2 = BasicObject::read($f2->id);
        $f3 = BasicObject::read($f3->id);
        $f4 = BasicObject::read($f4->id);
        $f5 = BasicObject::read($f5->id);
        //assertions
        $this->assertEquals('Updated object 1', $f1->string);
        $this->assertEquals(static::$createArray2['string'], $f2->string);
        $this->assertEquals('Updated object 3', $f3->string);
        $this->assertEquals('Updated object 4', $f4->string);
        $this->assertEquals(static::$createArray1['string'], $f5->string);
    }

    public function testCreateAndQueryAndDelete()
    {
        //add five objects to the table
        $f1 = BasicObject::create(static::$createArray1);
        $f2 = BasicObject::create(static::$createArray2);
        $f3 = BasicObject::create(static::$createArray1);
        $f4 = BasicObject::create(static::$createArray2);
        $f5 = BasicObject::create(static::$createArray1);
        //count objects
        $q = BasicObject::query(
            array(
                'where' => '@@id@@ > 0'
            ),
            array()
        );
        $this->assertEquals(5, count($q));
        //remove 1 item
        $f2->delete();
        //count objects
        $q = BasicObject::query(
            array(
                'where' => '@@id@@ > 0'
            ),
            array()
        );
        $this->assertEquals(4, count($q));
        //check that removed object is actually gone and
        //remove all items
        foreach ($q as $item) {
            $this->assertNotEquals($f2->id,$item->id);
            $item->delete();
        }
        //count objects
        $q = BasicObject::query(
            array(
                'where' => '@@id@@ > 0'
            ),
            array()
        );
        $this->assertEquals(0, count($q));
    }

    /**
    * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
    */
    public function getConnection() {
        $pdo = DB::getConnection('sqlite::memory:', null, null);
        //basicObject schema
        $pdo->exec('DROP TABLE IF EXISTS BasicObject');
        $pdo->exec('CREATE TABLE BasicObject (
            bo_id INTEGER PRIMARY KEY AUTOINCREMENT,
            bo_string VARCHAR(30) NOT NULL)');
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
BasicObject::configureClass(file_get_contents(__DIR__ . '/BasicObject.yaml'));
