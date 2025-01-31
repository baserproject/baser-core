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

namespace BaserCore\Test\TestCase\Service;

use BaserCore\Service\BcDatabaseService;
use BaserCore\Service\BcDatabaseServiceInterface;
use BaserCore\Service\SiteConfigsServiceInterface;
use BaserCore\Service\ThemesService;
use BaserCore\Service\ThemesServiceInterface;
use BaserCore\Test\Factory\SiteConfigFactory;
use BaserCore\Test\Factory\SiteFactory;
use BaserCore\Test\Factory\UsersUserGroupFactory;
use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\Test\Scenario\SmallSetContentFoldersScenario;
use BaserCore\Utility\BcContainerTrait;
use BaserCore\Utility\BcFile;
use BaserCore\Utility\BcFolder;
use BaserCore\Utility\BcSiteConfig;
use BaserCore\Utility\BcUtil;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\Utility\Inflector;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use Composer\Package\Archiver\ZipArchiver;
use Laminas\Diactoros\UploadedFile;

/**
 * ThemesServiceTest
 * @property ThemesService $ThemesService
 */
class ThemesServiceTest extends \BaserCore\TestSuite\BcTestCase
{

    /**
     * Trait
     */
    use BcContainerTrait;
    use IntegrationTestTrait;
    use ScenarioAwareTrait;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->ThemesService = $this->getService(ThemesServiceInterface::class);
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * test add
     * @return void
     */
    public function test_add()
    {
        $path = ROOT . DS . 'plugins' . DS . 'BcPluginSample';
        $zipSrcPath = TMP . 'zip' . DS;
        $folder = new BcFolder($zipSrcPath);
        $folder->create();
        //copy
        $folder = new BcFolder($path);
        $folder->copy( $zipSrcPath . 'BcPluginSample2');
        $theme = 'BcPluginSample2';
        $zip = new ZipArchiver();
        $testFile = $zipSrcPath . $theme . '.zip';
        $zip->archive($zipSrcPath, $testFile, true);

        $size = filesize($path);
        $type = BcUtil::getContentType($testFile);

        $this->setUploadFileToRequest('file', $testFile);

        $files = new UploadedFile(
            $testFile,
            $size,
            UPLOAD_ERR_OK,
            $theme . '.zip',
            $type
        );

        //成功した場合の戻り値
        $rs = $this->ThemesService->add(["file" => $files]);
        $this->assertEquals('BcPluginSample2', $rs);

        // 成功した場合に plugins 配下に新しいディレクトリが存在する
        $this->assertTrue(is_dir(ROOT . DS . 'plugins' . DS . $theme));

        // 既に存在するテーマと同じテーマをアップロードした場合の戻り値の変化
        $folder = new BcFolder($zipSrcPath);
        $folder->create();
        //copy
        $folder = new BcFolder($path);
        $folder->copy($zipSrcPath . 'BcPluginSample2');
        $zip = new ZipArchiver();
        $zip->archive($zipSrcPath, $testFile, true);
        $this->setUploadFileToRequest('file', $testFile);
        $files = new UploadedFile(
            $testFile,
            $size,
            UPLOAD_ERR_OK,
            $theme . '.zip',
            $type
        );

        $rs = $this->ThemesService->add(["file" => $files]);
        $this->assertEquals('BcPluginSample3', $rs);

        //テスト実行後不要ファイルを削除
        (new BcFolder(ROOT . DS . 'plugins' . DS . $theme))->delete();
        (new BcFolder(ROOT . DS . 'plugins' . DS . 'BcPluginSample3'))->delete();
        (new BcFolder($zipSrcPath))->delete();

        // 失敗した場合の Exception メッセージ
        $this->expectException("Laminas\Diactoros\Exception\UploadedFileAlreadyMovedException");
        $this->expectExceptionMessage("Cannot move file; already moved!");
        $this->ThemesService->add(["file" => $files]);
    }

    /**
     * 初期データのセットを取得する
     */
    public function testGetDefaultDataPatterns()
    {
        $options = ['useTitle' => false];
        $result = $this->ThemesService->getDefaultDataPatterns('BcThemeSample', $options);
        $expected = [
            'BcThemeSample.default' => 'default',
            'BcThemeSample.empty' => 'empty'
        ];
        $this->assertEquals($expected, $result, '初期データのセットのタイトルを外して取得できません');
        $result = $this->ThemesService->getDefaultDataPatterns('BcThemeSample');
        $expected = [
            'BcThemeSample.default' => 'サンプルテーマ ( default )',
            'BcThemeSample.empty' => 'サンプルテーマ ( empty )'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testChangeHelper
     */

     public function testChangeHelper()
     {
        $rs = $this->ThemesService->copy('BcThemeSample');
        $this->assertTrue($rs);
        //コピーを確認
        $this->assertTrue(is_dir(BASER_THEMES . 'BcThemeSampleCopy'), 'テーマのコピーが確認できませんでした。');

        $pluginPath = BcUtil::getPluginPath('BcThemeSampleCopy');

        // プラグインのnamespace置き換えを確認
        $file = new BcFile($pluginPath . 'src' . DS . 'BcThemeSampleCopyPlugin.php');
        $data = $file->read();
        $this->assertTrue(str_contains($data, 'namespace BcThemeSampleCopy'), 'pluginファイルのnamespace の書き換えが確認できませんでした。');


        $file = new BcFile($pluginPath . 'src' . DS . 'View' . DS .'Helper' . DS . 'BcThemeSampleCopyHelper.php');
        $data = $file->read();
        // ヘルパーのnamespaceの置き換えを確認
        $this->assertTrue(str_contains($data, 'namespace BcThemeSampleCopy'), 'Helperファイルのnamespace の書き換えが確認できませんでした。');
        // ヘルパーのクラス名書き換えを確認
        $this->assertTrue(str_contains($data,'class BcThemeSampleCopyHelper'),'Helperファイルのクラス名の書き換えが確認できませんでした。');

        $this->ThemesService->delete('BcThemeSampleCopy');
     }
 
    /**
     * test copy
     * @return void
     */
    public function testCopy()
    {
        $rs = $this->ThemesService->copy('BcFront');
        $this->assertTrue($rs);
        //コピーを確認
        $this->assertTrue(is_dir(BASER_THEMES . 'BcFrontCopy'), 'テーマのコピーが確認できませんでした。');

        $pluginPath = BcUtil::getPluginPath('BcFrontCopy');
        $file = new BcFile($pluginPath . 'src' . DS . 'BcFrontCopyPlugin.php');
        $data = $file->read();
        //namespaceの書き換えを確認
        $this->assertTrue(str_contains($data, 'namespace BcFrontCopy;'), 'namespace の書き換えが確認できませんでした。');

        $this->ThemesService->delete('BcFrontCopy');
    }
    /**
     * test delete
     * @return void
     */
    public function testDelete()
    {
        $this->ThemesService->copy('BcFront');
        $rs = $this->ThemesService->delete('BcFrontCopy');
        $this->assertTrue($rs);
        $this->assertTrue(!is_dir(BASER_THEMES . 'BcFrontCopy'));
    }
    /**
     * test getThemesDefaultDataInfo
     * @return void
     */
    public function testGetThemesDefaultDataInfo()
    {
        $theme = 'BcFront';
        $themePath = BcUtil::getPluginPath($theme);

        mkdir($themePath . 'plugins', 0777);
        mkdir($themePath . 'plugins/test', 0777);

        $file = new BcFile($themePath . 'plugins/test/test.txt');
        $file->create();
        $file->write('test file plugin');

        $file = new BcFile($themePath . 'plugins/test2.txt');
        $file->create();
        $file->write('test file 2');

        $info = [
            'このテーマは下記のプラグインを同梱しています。',
            '	・test'
        ];
        $expected = [
            'このテーマは下記のプラグインを同梱しています。',
            '	・test',
            '',
            'このテーマは初期データを保有しています。',
            'Webサイトにテーマに合ったデータを適用するには、初期データ読込を実行してください。'
        ];

        $rs = $this->execPrivateMethod($this->ThemesService, 'getThemesDefaultDataInfo', [$theme, $info]);
        $this->assertEquals($expected, $rs);

        (new BcFolder($themePath . 'plugins'))->delete();
    }

    /**
     * test getMarketThemes
     * @return void
     */
    public function testGetMarketThemes()
    {
        $this->markTestIncomplete('TODO 外部サイトに直接ではなくモックのテストに切り替える');
        $themes = $this->ThemesService->getMarketThemes();
        $this->assertEquals(true, count($themes) > 0);
    }

    /**
     * 指定したテーマをダウンロード用のテーマとして一時フォルダに作成する
     * @return void
     */
    public function testCreateDownloadToTmp()
    {
        $tmpDir = TMP . 'theme' . DS;
        $theme = 'BcFront';
        $tmpThemeDir = $tmpDir . $theme;

        $result = $this->ThemesService->createDownloadToTmp($theme);
        $this->assertEquals($tmpDir, $result);
        $this->assertTrue(is_dir($tmpThemeDir));

        (new BcFolder($tmpThemeDir))->delete();
    }

    /**
     * 初期データチェックする
     * @return void
     */
    public function testCheckDefaultDataPattern()
    {
        $theme = Configure::read('BcApp.coreFrontTheme');
        $configDataPath = BASER_THEMES . Inflector::dasherize($theme) . DS . 'config' . DS . 'data';
        $Folder = new BcFolder($configDataPath . DS . 'default' . DS . 'BaserCore');
        $coreTables = $Folder->getFiles();

        // 一つ目のダミーフォルダを作る
        $pattern = 'dummy1';
        $dummyFolder = new BcFolder($configDataPath . DS . $pattern);
        $dummyFolder->create();
        // BaserCoreフォルダを作る
        (new BcFolder($configDataPath . DS . $pattern . DS . 'BaserCore'))->create();
        // テーブルファイルを作る
        foreach ($coreTables as $table) {
            (new BcFile($configDataPath . DS . $pattern . DS . 'BaserCore' . DS . $table))->create();
        }
        $result = $this->ThemesService->checkDefaultDataPattern($theme, $pattern);
        $dummyFolder->delete();
        // 成功を確認
        $this->assertTrue($result);

        // 二つ目のダミーフォルダを作る
        $pattern = 'dummy2';
        $dummyFolder = new BcFolder($configDataPath . DS . $pattern);
        $dummyFolder->create();
        $result = $this->ThemesService->checkDefaultDataPattern($theme, $pattern);
        $dummyFolder->delete();
        // 失敗を確認
        $this->assertFalse($result);
    }

    /**
     * 現在のDB内のデータをダウンロード用のCSVとして一時フォルダに作成する
     * @return void
     */
    public function testCreateDownloadDefaultDataPatternToTmp()
    {
        $this->ThemesService->createDownloadDefaultDataPatternToTmp();
        $tmpDir = TMP . 'csv' . DS;
        // CSVファイルが作成されている事を確認
        $baserCoreFolder = new BcFolder($tmpDir . 'BaserCore' . DS);
        $csvFiles = $baserCoreFolder->find('.*\.csv');
        $this->assertNotEmpty($csvFiles);
        // 作成されたディレクトリを削除
        (new BcFolder($tmpDir))->delete();
    }

    /**
     * 一覧データ取得
     */
    public function testGetIndex()
    {
        $themes = $this->ThemesService->getIndex();
        $this->assertEquals('BcFront', $themes[array_key_last($themes)]->name);
    }

    /**
     * 指定したテーマが梱包するプラグイン情報を取得
     */
    public function testGetThemesPluginsInfo()
    {
        $theme = 'BcFront';
        $themePath = BcUtil::getPluginPath($theme);
        $pluginName = 'test';
        $folder = new BcFolder($themePath . 'plugins/' . $pluginName);
        $folder->create();

        $pluginsInfo = $this->execPrivateMethod($this->ThemesService, 'getThemesPluginsInfo', [$theme]);
        $this->assertEquals('このテーマは下記のプラグインを同梱しています。', $pluginsInfo[0]);
        $this->assertEquals('	・' . $pluginName, $pluginsInfo[1]);

        (new BcFolder($themePath . 'plugins'))->delete();
    }

    /**
     * site_configs テーブルにて、 CSVに出力しないフィールドを空にする
     */
    public function test_modifySiteConfigsCsv()
    {
        SiteConfigFactory::make(['name' => 'email', 'value' => 'chuongle@mediabridge.asia'])->persist();
        SiteConfigFactory::make(['name' => 'google_analytics_id', 'value' => 'gg123'])->persist();
        SiteConfigFactory::make(['name' => 'version', 'value' => '1.1.1'])->persist();

        $this->ThemesService->createDownloadDefaultDataPatternToTmp();
        $path = TMP . 'csv' . DS . 'BaserCore' . DS . 'site_configs.csv';
        $this->execPrivateMethod($this->ThemesService, '_modifySiteConfigsCsv', [$path]);

        $targets = ['email', 'google_analytics_id', 'version'];
        $fp = fopen($path, 'a+');
        while(($record = BcUtil::fgetcsvReg($fp, 10240)) !== false) {
            if (in_array($record[1], $targets)) {
                $this->assertEmpty($record[2]);
            }
        }
    }

    /**
     * CSVファイルを書きだす
     * @return void
     */
    public function test_writeCsv()
    {
        $plugin = 'BaserCore';
        $dbService = $this->getService(BcDatabaseServiceInterface::class);
        $tableList = $dbService->getAppTableList($plugin);
        $path = TMP . 'testWriteCsv' . DS;
        $csvFolder = new BcFolder($path);
        $csvFolder->create();
        BcUtil::emptyFolder($path);
        $this->execPrivateMethod($this->ThemesService, '_writeCsv', [$plugin, $path]);
        $files = $csvFolder->getFiles();
        foreach ($tableList as $table) {
            $this->assertTrue(in_array($table . '.csv', $files));
        }
    }

    /**
     * テーマを適用する
     */
    public function testApply()
    {
        $beforeTheme = 'BcPluginSample';
        $afterTheme = 'BcFront';
        $this->loadFixtureScenario(SmallSetContentFoldersScenario::class);
        SiteFactory::make(['id' => 1, 'title' => 'Test Title', 'name' => 'Test Site', 'theme'=> $beforeTheme, 'status' => 1])->persist();
        $site = SiteFactory::get(1);
        Router::setRequest($this->getRequest());
        $result = $this->ThemesService->apply($site, $afterTheme);
        $site = SiteFactory::get(1);
        $this->assertNotEquals($beforeTheme, $site->theme);
        $this->assertCount(2, $result);
        $this->assertEquals('このテーマは初期データを保有しています。', $result[0]);
        $this->assertEquals('Webサイトにテーマに合ったデータを適用するには、初期データ読込を実行してください。', $result[1]);
    }

    /**
     * 初期データを読み込む
     */
    public function testLoadDefaultDataPattern()
    {
        $this->loadFixtureScenario(InitAppScenario::class);
        Router::setRequest($this->loginAdmin($this->getRequest()));
        $theme = 'BcThemeSample';
        $pattern = 'default';
        $plugin = 'BaserCore';
        $result = $this->ThemesService->loadDefaultDataPattern($theme, $theme . '.' . $pattern);

        // --- 初期データ読み込みを確認 start ---
        $path = BcUtil::getDefaultDataPath($theme, $pattern);
        $this->assertNotNull($path);
        $Folder = new BcFolder($path . DS . $plugin);
        $csvList = $Folder->getFiles(['full'=>true]);
        $BcDatabaseService = new BcDatabaseService();
        $tableList = $BcDatabaseService->getAppTableList($plugin);
        foreach ($csvList as $path) {
            $table = basename($path, '.csv');
            if (!in_array($table, $tableList)) continue;
            $records = $BcDatabaseService->loadCsvToArray($path);
            $appTable = TableRegistry::getTableLocator()->get('BaserCore.App');
            $schema = $appTable->getConnection()->getSchemaCollection()->describe($table);
            $appTable->setTable($table);
            $appTable->setSchema($schema);
            $this->assertCount($appTable->find()->count(), $records);
        }
        // --- 初期データ読み込みを確認 end ---

        // --- システムデータの初期化を確認 start ---
        // user_groups　テーブルにデータが登録されている事を確認
        $userGroupTable = TableRegistry::getTableLocator()->get('BaserCore.UserGroups');
        $this->assertTrue($userGroupTable->find()->where(['UserGroups.name' => 'admins'])->count() > 0);
        // users_user_groups　テーブルにデータが登録されている事を確認
        $corePath = BcUtil::getPluginPath(Inflector::camelize(Configure::read('BcApp.coreFrontTheme'), '-')) . 'config' . DS . 'data' . DS . 'default' . DS . 'BaserCore';
        $usersUserGroups = $BcDatabaseService->loadCsvToArray($corePath . DS . 'users_user_groups.csv');
        $this->assertCount(UsersUserGroupFactory::count(), $usersUserGroups);
        // site_configs テーブルの email / google_analytics_id / first_access / admin_theme / version の設定状況を確認
        $siteConfigsService = $this->getService(SiteConfigsServiceInterface::class);
        $this->assertEquals($siteConfigsService->getValue('email'), BcSiteConfig::get('email'));
        $this->assertEquals($siteConfigsService->getValue('google_analytics_id'), BcSiteConfig::get('google_analytics_id'));
        $this->assertEquals(null, $siteConfigsService->getValue('first_access'));
        $this->assertEquals($siteConfigsService->getValue('admin_theme'), BcSiteConfig::get('admin_theme'));
        $this->assertEquals($siteConfigsService->getValue('version'), BcSiteConfig::get('version'));
        // sites テーブルの theme の設定状況を確認
        $this->assertEquals($theme, SiteFactory::get(1)->theme);
        // --- システムデータの初期化を確認 end ---

        // 戻り値を確認
        $this->assertTrue($result);
    }

}
