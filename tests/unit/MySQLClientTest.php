<?php



class MySQLClientTest extends \PHPUnit_Framework_TestCase
{


    protected function setUp()
    {
        require_once 'paths.php';
        require_once CARL_UTIL_INC .'db/connectDB.php';

        $resource = connectDB('reason_connection');
        $this->resource = $resource;
    }

    protected function tearDown()
    {
    }

    // tests
    public function testConnection()
    {
        $resource = connectDB('reason_connection');
        $this->assertNotEmpty($resource);
    }

    public function testCurrentConnection() {
        // codecept_debug('hello');
        $name = get_current_db_connection_name();
        $this->assertEquals('reason_connection', $name);
    }

    public function testDbName() {
        $dbName = get_database_name();
        $this->assertEquals('reason', $dbName);
    }

    public function testCredentials() {
        $creds = get_db_credentials('reason_connection');
        // codecept_debug($creds);

        $this->assertEquals([
          'db' => 'reason',
          'user' => 'reason_user',
          'password' => 'some_password',
          'host' => '127.0.0.1',
          'charset' => 'utf8',
        ], $creds);
    }

    // public function testOldCreds() {
    //     $oldCreds = _legacy_get_db_credentials('reason_connection');
    //     $this->assertEquals([
    //       // 'db' => 'reason',
    //       // 'user' => 'reason_user',
    //       // 'password' => 'some_password',
    //       // 'host' => '127.0.0.1',
    //       // 'charset' => 'utf8',
    //     ], $oldCreds);
    // }

}
