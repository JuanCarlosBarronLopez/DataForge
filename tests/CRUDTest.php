<?php
namespace DataForge\Tests;

use PHPUnit\Framework\TestCase;

class CRUDTest extends TestCase
{
    private static string $testDbName = 'dataforge_phpunit_test';
    private static string $testTableName = 'test_products';
    private static bool $dbAvailable = false;

    public static function setUpBeforeClass(): void
    {
        try {
            $conn = getDbConnection();
            $conn->close();
            self::$dbAvailable = true;
        } catch (\Exception $e) {
            self::$dbAvailable = false;
        }
    }

    protected function setUp(): void
    {
        if (!self::$dbAvailable) {
            $this->markTestSkipped('Database not available.');
        }
        $_SESSION['df_user'] = ['id'=>999,'username'=>'phpunit','email'=>'t@t.com','theme'=>'neutral'];
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dbAvailable) {
            try {
                $conn = getDbConnection();
                $conn->query("DROP DATABASE IF EXISTS `" . self::$testDbName . "`");
                $conn->close();
            } catch (\Exception $e) {}
        }
    }

    public function testCreateDatabase(): void
    {
        $result = createDatabase(self::$testDbName);
        $this->assertTrue($result['success'], $result['message']);
    }

    public function testCreateDatabaseInvalidName(): void
    {
        $this->assertFalse(createDatabase('')['success']);
        $this->assertFalse(createDatabase('db-with-dashes!')['success']);
    }

    public function testListDatabases(): void
    {
        createDatabase(self::$testDbName);
        $this->assertContains(self::$testDbName, getDatabases());
    }

    public function testCannotDropSystemDb(): void
    {
        $this->assertFalse(dropDatabase('mysql')['success']);
    }

    public function testCreateTable(): void
    {
        createDatabase(self::$testDbName);
        $cols = [
            ['name'=>'product_name','type'=>'VARCHAR','length'=>100],
            ['name'=>'price','type'=>'DECIMAL'],
            ['name'=>'stock','type'=>'INT'],
            ['name'=>'is_active','type'=>'BOOLEAN'],
            ['name'=>'description','type'=>'TEXT'],
            ['name'=>'created_date','type'=>'DATE'],
        ];
        $result = createTable(self::$testDbName, self::$testTableName, $cols);
        $this->assertTrue($result['success'], $result['message']);
    }

    public function testCreateTableInvalidName(): void
    {
        $this->assertFalse(createTable(self::$testDbName, 'bad name!', [['name'=>'c','type'=>'INT']])['success']);
    }

    public function testCreateTableNoColumns(): void
    {
        $this->assertFalse(createTable(self::$testDbName, 'empty', [])['success']);
    }

    public function testAddRecord(): void
    {
        $this->testCreateTable();
        $data = ['product_name'=>'Widget','price'=>'9.99','stock'=>'50','is_active'=>'1','description'=>'Test','created_date'=>'2025-01-01'];
        $this->assertTrue(addRecord(self::$testDbName, self::$testTableName, $data)['success']);
    }

    public function testGetRecords(): void
    {
        $this->testAddRecord();
        $records = getRecords(self::$testDbName, self::$testTableName);
        $this->assertNotEmpty($records);
    }

    public function testUpdateRecord(): void
    {
        $this->testAddRecord();
        $result = updateRecord(self::$testDbName, self::$testTableName, 1, ['product_name'=>'Updated']);
        $this->assertTrue($result['success']);
    }

    public function testDeleteRecord(): void
    {
        $this->testAddRecord();
        $this->assertTrue(deleteRecord(self::$testDbName, self::$testTableName, 1)['success']);
    }

    public function testDropTable(): void
    {
        $this->testCreateTable();
        $this->assertTrue(dropTable(self::$testDbName, self::$testTableName)['success']);
    }

    public function testDropDatabase(): void
    {
        createDatabase(self::$testDbName);
        $this->assertTrue(dropDatabase(self::$testDbName)['success']);
    }
}
