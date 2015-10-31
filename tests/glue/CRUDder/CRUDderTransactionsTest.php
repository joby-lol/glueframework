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
namespace glue\CRUDder\TransactionsTest;

use \glue\CRUDder\CRUDder;
use \glue\CRUDder\CRUDderFormatter;
use \glue\CRUDder\DB;

class CRUDderTransactionsTest extends \PHPUnit_Extensions_Database_TestCase
{
    public static $conn = null;
    protected static $createArray1 = array(
        'string' => 'Created Object 1'
    );
    protected static $createArray2 = array(
        'string' => 'Created Object 2'
    );

    public function testTransactions()
    {
        //Test the basics -- do commit() and rollback() work?
        $t = BasicObject::getTransacter();
        $this->assertTrue($t->beginTransaction());
        //add an object and rollback
        $f1 = BasicObject::create(static::$createArray1);
        $this->assertTrue($t->rollback());
        $this->assertTrue($t->beginTransaction());
        //there should be no ID 1
        $this->assertFalse(BasicObject::read(1),'rollback() didn\'t prevent changes from saving');
        //add an object and commit
        $f1 = BasicObject::create(static::$createArray1);
        $this->assertTrue($t->commit());//no way to test commit, so we just make sure it doesn't blow up
    }
    public function testTransactionResolutionOrder()
    {
        //Test that transactions enforce proper commit/rollback order
        $top = BasicObject::getTransacter();
        $middle = BasicObject::getTransacter();
        $bottom = BasicObject::getTransacter();
        $this->assertTrue($top->beginTransaction());
            $this->assertTrue($middle->beginTransaction());
                $this->assertFalse($top->rollback());
                $this->assertTrue($bottom->beginTransaction());
                    $this->assertFalse($top->rollback());
                    $this->assertFalse($middle->rollback());
                $this->assertTrue($bottom->rollback());
            $this->assertTrue($middle->rollback());
        $this->assertTrue($top->rollback());
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
