<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace MysqlBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MysqlBackup\Test\TestCase\Utility\BackupExport;

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('MysqlBackup.bin.bzip2', which('bzip2'));
        Configure::write('MysqlBackup.bin.gzip', which('gzip'));
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        //Deletes all backups
        foreach (glob(Configure::read('MysqlBackup.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $instance = new BackupExport();

        $connection = $instance->getConnection();
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');

        $this->assertNull($instance->getCompression());
        $this->assertEquals('sql', $instance->getExtension());
        $this->assertNull($instance->getFilename());
        $this->assertNull($instance->getRotate());
    }

    /**
     * Test for `compression()` method. This also tests for `$extension`
     *  property
     * @test
     */
    public function testCompression()
    {
        $instance = new BackupExport();

        $instance->compression('bzip2');
        $this->assertEquals('bzip2', $instance->getCompression());
        $this->assertEquals('sql.bz2', $instance->getExtension());

        $instance->compression('gzip');
        $this->assertEquals('gzip', $instance->getCompression());
        $this->assertEquals('sql.gz', $instance->getExtension());

        $instance->compression(false);
        $this->assertEquals(false, $instance->getCompression());
        $this->assertEquals('sql', $instance->getExtension());
    }

    /**
     * Test for `compression()` method, with an invalid stringvalue
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidString()
    {
        (new BackupExport())->compression('invalidValue');
    }

    /**
     * Test for `compression()` method, with an invalid boolean value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidBool()
    {
        (new BackupExport())->compression(true);
    }

    /**
     * Test for `filename()` method. This also tests for `$compression`
     * @test
     */
    public function testFilename()
    {
        $instance = new BackupExport();

        $instance->filename('backup.sql');
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql', $instance->getFilename());
        $this->assertFalse($instance->getCompression());

        $instance->filename('backup.sql.gz');
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.gz', $instance->getFilename());
        $this->assertEquals('gzip', $instance->getCompression());

        $instance->filename('backup.sql.bz2');
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.bz2', $instance->getFilename());
        $this->assertEquals('bzip2', $instance->getCompression());

        //Absolute path
        $instance->filename(Configure::read('MysqlBackup.target') . DS . 'other.sql');
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'other.sql', $instance->getFilename());
        $this->assertFalse($instance->getCompression());
    }

    /**
     * Test for `filename()` method. This checks that the `filename()` method
     *  overwrites the `compression()` method
     * @test
     */
    public function testFilenameRewritesCompression()
    {
        $instance = new BackupExport();

        $instance->compression('gzip')->filename('backup.sql.bz2');
        $this->assertEquals('backup.sql.bz2', basename($instance->getFilename()));
        $this->assertEquals('bzip2', $instance->getCompression());

        $instance->compression('bzip2')->filename('backup.sql');
        $this->assertEquals('backup.sql', basename($instance->getFilename()));
        $this->assertFalse($instance->getCompression());
    }

    /**
     * Test for `filename()` method, with a file that already exists
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `/tmp/backups/backup.sql` already exists
     */
    public function testFilenameAlreadyExists()
    {
        (new BackupExport())->filename('backup.sql')->export();

        //Again, same filename
        (new BackupExport())->filename('backup.sql')->export();
    }

    /**
     * Test for `filename()` method, with patterns
     * @test
     */
    public function testFilenameWithPatterns()
    {
        $instance = new BackupExport();

        $instance->filename('{$DATABASE}.sql');
        $this->assertEquals('test.sql', basename($instance->getFilename()));

        $instance->filename('{$DATETIME}.sql');
        $this->assertRegExp('/^[0-9]{14}\.sql$/', basename($instance->getFilename()));

        $instance->filename('{$HOSTNAME}.sql');
        $this->assertEquals('localhost.sql', basename($instance->getFilename()));

        $instance->filename('{$TIMESTAMP}.sql');
        $this->assertRegExp('/^[0-9]{10}\.sql$/', basename($instance->getFilename()));
    }

    /**
     * Test for `filename()` method, with invalid directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingDir` not writable
     * @test
     */
    public function testFilenameWithInvalidDirectory()
    {
        (new BackupExport())->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid file extension
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        (new BackupExport())->filename('backup.txt');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $instance = new BackupExport();

        $instance->rotate(10);
        $this->assertEquals(10, $instance->getRotate());
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid rotate value
     * @test
     */
    public function testRotateWithInvalidValue()
    {
        (new BackupExport())->rotate(-1)->export();
    }

    /**
     * Test for `_storeAuth()` method
     * @test
     */
    public function testStoreAuth()
    {
        $auth = (new BackupExport())->getAuth();

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[mysqldump]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
    }

    /**
     * Test for `_getExecutable()` method
     * @test
     */
    public function testExecutable()
    {
        $mysqldump = Configure::read('MysqlBackup.bin.mysqldump');
        $bzip2 = Configure::read('MysqlBackup.bin.bzip2');
        $gzip = Configure::read('MysqlBackup.bin.gzip');

        $this->assertEquals($mysqldump . ' --defaults-file=%s %s | ' . $bzip2 . ' > %s', (new BackupExport())->getExecutable('bzip2'));
        $this->assertEquals($mysqldump . ' --defaults-file=%s %s | ' . $gzip . ' > %s', (new BackupExport())->getExecutable('gzip'));
        $this->assertEquals($mysqldump . ' --defaults-file=%s %s > %s', (new BackupExport())->getExecutable(false));
    }

    /**
     * Test for `_getExecutable()` method, with the `bzip2` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `bzip2` executable not available
     * @test
     */
    public function testExecutableWithBzip2NotAvailable()
    {
        Configure::write('MysqlBackup.bin.bzip2', false);

        (new BackupExport())->getExecutable('bzip2');
    }

    /**
     * Test for `_getExecutable()` method, with the `gzip` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `gzip` executable not available
     * @test
     */
    public function testExecutableWithGzipNotAvailable()
    {
        Configure::write('MysqlBackup.bin.gzip', false);

        (new BackupExport())->getExecutable('gzip');
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $instance = new BackupExport();

        $filename = $instance->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        $filename = $instance->compression('bzip2')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        $filename = $instance->compression('gzip')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));

        $filename = $instance->filename('backup.sql')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql', basename($filename));

        $filename = $instance->filename('backup.sql.bz2')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.bz2', basename($filename));

        $filename = $instance->filename('backup.sql.gz')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.gz', basename($filename));
    }
}
