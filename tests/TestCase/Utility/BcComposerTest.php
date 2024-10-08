<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace BaserCore\Test\TestCase\Utility;

use BaserCore\TestSuite\BcTestCase;
use BaserCore\Utility\BcComposer;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;

/**
 * BcComposer Test
 */
class BcComposerTest extends BcTestCase
{

    /**
     * tear down
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        if(file_exists(ROOT . DS . 'composer.json.bak')) {
            rename(ROOT . DS . 'composer.json.bak', ROOT . DS . 'composer.json');
        }
        if(file_exists(ROOT . DS . 'composer.lock.bak')) {
            rename(ROOT . DS . 'composer.lock.bak', ROOT . DS . 'composer.lock');
        }
    }

    /**
     * installComposer
     */
    public function test_installComposer()
    {
        if(file_exists(ROOT . DS . 'composer' . DS . 'composer.phar')) {
            unlink(ROOT . DS . 'composer' . DS . 'composer.phar');
        }
        BcComposer::installComposer();
        $this->assertFileDoesNotExist(BcComposer::$composerDir . 'composer.phar');

        BcComposer::$composerDir = ROOT . DS . 'composer' . DS;
        BcComposer::$export = "export HOME=" . BcComposer::$composerDir . ";";
        BcComposer::$php = '/usr/local/bin/php';
        $result = BcComposer::installComposer();
        $this->assertEquals(0, $result['code']);
        $this->assertFileExists(BcComposer::$composerDir . 'composer.phar');
    }

    /**
     * test setVersion
     */
    public function test_require()
    {
        $this->markTestIncomplete('このテストは、5.0.2リリース時に実装する予定です。');
        $orgPath = ROOT . DS . 'composer.json';
        $backupPath = ROOT . DS . 'composer.json.bak';
        $orgLockPath = ROOT . DS . 'composer.lock';
        $backupLockPath = ROOT . DS . 'composer.lock.bak';

        // バックアップ作成
        copy($orgPath, $backupPath);
        copy($orgLockPath, $backupLockPath);

        // replace を削除
        $file = new File($orgPath);
        $data = $file->read();
        $regex = '/("replace": {.+?},)/s';
        $data = preg_replace($regex, '' , $data);
        $file->write($data);
        $file->close();

        // インストール
        BcComposer::setup();
        $result = BcComposer::require('baser-core', '5.0.0');
        $this->assertEquals(0, $result['code']);
        $file = new File($orgPath);
        $data = $file->read();
        $this->assertNotFalse(strpos($data, '"baserproject/baser-core": "5.0.0"'));

        // アップデート
        BcComposer::setup();
        $result = BcComposer::require('baser-core', '5.0.1');
        $this->assertEquals(0, $result['code']);
        $file = new File($orgPath);
        $data = $file->read();
        $this->assertNotFalse(strpos($data, '"baserproject/baser-core": "5.0.1"'));

        // ダウングレード
        BcComposer::setup();
        $result = BcComposer::require('baser-core', '5.0.0');
        $this->assertEquals(0, $result['code']);
        $file = new File($orgPath);
        $data = $file->read();
        $this->assertNotFalse(strpos($data, '"baserproject/baser-core": "5.0.0"'));

        // エラー
        $result = BcComposer::require('bc-content-link', '5.0.1');
        $this->assertEquals(2, $result['code']);

        // バックアップ復元
        rename($backupPath, $orgPath);
        rename($backupLockPath, $orgLockPath);
        $folder = new Folder();
        $folder->delete(ROOT . DS . 'vendor' . DS . 'baserproject');
    }

}
