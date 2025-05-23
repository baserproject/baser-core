<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) baserCMS Users Community <https://basercms.net/community/>
 *
 * @copyright       Copyright (c) baserCMS Users Community
 * @link            https://basercms.net baserCMS Project
 * @since           baserCMS v 3.0.6
 * @license         https://basercms.net/license/index.html
 */

namespace BaserCore\Test\TestCase\View\Helper;

use BaserCore\Model\Entity\Content;
use BaserCore\Model\Entity\Site;
use BaserCore\Test\Factory\ContentFactory;
use BaserCore\Test\Scenario\ContentsScenario;
use BaserCore\Test\Scenario\PermissionsScenario;
use BaserCore\Test\Scenario\SitesScenario;
use BaserCore\Test\Scenario\UserGroupsScenario;
use BaserCore\Test\Scenario\UserScenario;
use BaserCore\Test\Scenario\UsersUserGroupsScenario;
use Cake\Routing\Router;
use BaserCore\View\BcAdminAppView;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Utility\BcUtil;
use BaserCore\View\Helper\BcContentsHelper;
use Cake\Utility\Hash;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

/**
 * BcContents helper library.
 *
 * @property BcContentsHelper $BcContents
 */
class BcContentsHelperTest extends BcTestCase
{
    use ScenarioAwareTrait;
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loadFixtureScenario(UserScenario::class);
        $this->loadFixtureScenario(UserGroupsScenario::class);
        $this->loadFixtureScenario(UsersUserGroupsScenario::class);
        $this->loadFixtureScenario(SitesScenario::class);
        $this->loadFixtureScenario(ContentsScenario::class);
        $this->loadFixtureScenario(PermissionsScenario::class);
        $this->BcContents = new BcContentsHelper(new BcAdminAppView($this->getRequest('/')));
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BcAdminAppView);
        unset($this->BcContents);
        Router::reload();
        parent::tearDown();
    }

    /**
     * testInitialize
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertNotEmpty($this->BcContents->_Contents);
        $this->assertNotEmpty($this->BcContents->request);
        $this->assertNotEmpty($this->BcContents->ContentsService);
        $this->assertNotEmpty($this->BcContents->PermissionsService);
        $this->assertArrayHasKey('BcBaser', $this->BcContents->helpers);

    }

    /**
     * testSetUp
     *
     * @return void
     */
    public function testSetUp(): void
    {
        // $itemsがないの場合
        $this->BcContents->setUp();
        $result = $this->BcContents->getConfig('items');
        $this->assertNull($result);

        // $itemsがある場合
        $View = new BcAdminAppView($this->loginAdmin($this->getRequest('/')));
        $View->set('contentsItems', BcUtil::getContentsItem());
        $this->BcContents = new BcContentsHelper($View);
        $this->BcContents->setUp();

        $result = $this->BcContents->getConfig('items');
        $this->assertNotNull($result);
        $this->assertEquals('無所属コンテンツ', $result["Default"]["title"]);
    }

    /**
     * test _getExistsTitles
     */
    public function test_getExistsTitles()
    {
        $rs = $this->execPrivateMethod($this->BcContents, '_getExistsTitles', []);

        $this->assertEquals('サイトID3のフォルダ', $rs["BaserCore.ContentFolder"]);
        $this->assertEquals('サイトID3の固定ページ3', $rs["BaserCore.Page"]);
    }

    /**
     * ページリストを取得する
     *
     * @param int $pageCategoryId カテゴリID
     * @param int $level 関連データの階層
     * @param int $expectedCount 期待値
     * @param string $expectedTitle
     * @param string $message テストが失敗した時に表示されるメッセージ
     * @dataProvider getPageListDataProvider
     */
    public function testGetTree($id, $level, $expectedCount, $expectedTitle)
    {
        $this->truncateTable('contents');
        //データ生成
        ContentFactory::make(['id' => 1, 'lft' => 1, 'rght' => 100, 'site_root' => true])->persist();
        //'id' => 1の子
        ContentFactory::make(['id' => 2, 'lft' => 2, 'rght' => 14, 'title' => 'トップページ', 'parent_id' => 1])->persist();
        ContentFactory::make(['id' => 3, 'lft' => 15, 'rght' => 16, 'parent_id' => 1])->persist();
        ContentFactory::make(['id' => 4, 'lft' => 17, 'rght' => 18, 'parent_id' => 1])->persist();
        ContentFactory::make(['id' => 5, 'lft' => 19, 'rght' => 20, 'parent_id' => 1])->persist();

        //'id' => 2の子
        ContentFactory::make(['id' => 6, 'lft' => 3, 'rght' => 7, 'title' => 'サービス', 'parent_id' => 2])->persist();
        ContentFactory::make(['id' => 7, 'lft' => 8, 'rght' => 9, 'parent_id' => 2])->persist();
        ContentFactory::make(['id' => 8, 'lft' => 10, 'rght' => 11, 'parent_id' => 2])->persist();
        ContentFactory::make(['id' => 9, 'lft' => 12, 'rght' => 13, 'parent_id' => 2])->persist();
        //'id' => 6の子
        ContentFactory::make(['id' => 10, 'lft' => 5, 'rght' => 6, 'title' => 'サブサービス１', 'parent_id' => 6])->persist();
        //'id' => 3の子

        $result = $this->BcContents->getTree($id, $level)->toArray();

        $resultTitle = null;
        $resultCount = null;
        switch ($level) {
            case 1:
                if (!empty($result[0]['title'])) {
                    $resultTitle = $result[0]['title'];
                    $resultCount = count($result);
                }
                break;
            case 2:
                if ($result) {
                    foreach ($result as $data) {
                        if ($data['children']) {
                            $resultTitle = $data['children'][0]['title'];
                            $resultCount = count($data['children']);
                        }
                    }
                }
                break;
            case 3:
                if ($result) {
                    foreach ($result as $data) {
                        if ($data['children']) {
                            foreach ($data['children'] as $data2) {
                                if ($data2['children']) {
                                    $resultTitle = $data2['children'][0]['title'];
                                    $resultCount = count($data2['children']);
                                }
                            }
                        }
                    }
                }
                break;
        }
        $this->assertEquals($expectedCount, $resultCount);
        $this->assertEquals($expectedTitle, $resultTitle);
    }

    public static function getPageListDataProvider()
    {
        return [
            // PC版
            [1, 1, 4, 'トップページ'],
            [1, 2, 4, 'サービス'],
            [1, 3, 1, 'サブサービス１'],
            // ケータイ
            [2, 1, 4, 'サービス']
        ];
    }

    /**
     * @dataProvider isSiteRelatedDataProvider
     */
    public function testIsSiteRelated($expect, $data)
    {
        $result = $this->BcContents->isSiteRelated($data);
        $this->assertEquals($expect, $result);
    }

    public static function isSiteRelatedDataProvider()
    {
        return [
            [true, new Content(['main_site_content_id' => 1, 'alias_id' => 1, 'type' => 'BlogContent', 'site' => new Site(['relate_main_site' => true])])],
            [false, new Content(['main_site_content_id' => 1, 'alias_id' => 1, 'type' => 'BlogContent', 'site' => new Site(['relate_main_site' => false])])],
            [false, new Content(['main_site_content_id' => null, 'alias_id' => 1, 'type' => 'BlogContent', 'site' => new Site(['relate_main_site' => true])])],
            [false, new Content(['main_site_content_id' => 1, 'alias_id' => null, 'type' => 'BlogContent', 'site' => new Site(['relate_main_site' => true])])],
            [true,new Content(['main_site_content_id' => 1, 'alias_id' => null, 'type' => 'ContentFolder', 'site' => new Site(['relate_main_site' => true])])]
        ];
    }

    /**
     * アクションが利用可能かどうか確認する
     * isActionAvailable
     *
     * @param string $type コンテンツタイプ
     * @param string $action アクション
     * @param int $entityId コンテンツを特定するID
     * @param bool $expect 期待値
     * @dataProvider isActionAvailableDataProvider
     */
    public function testIsActionAvailable($type, $action, $entityId, $userGroup, $expect)
    {
        // TODO: configが設定されてない場合だとすべてtrueで通ってしまうため再確認要
        $this->loginAdmin($this->getRequest(), $userGroup);
        $this->BcContents->setConfig('items.' . $type . '.permissionCheckUrl.' . $action, 'sample');
        $result = $this->BcContents->isActionAvailable($type, $action, $entityId);
        $this->assertEquals($expect, $result);
    }

    public static function isActionAvailableDataProvider()
    {
        return [
            // 管理ユーザー
            // ['Default', 'index', 1, 2, false], // 存在しないアクション
            ['ContentFolder', 'add', 1, 1, true], // 存在するアクション
            ['ContentFolder', 'edit', 1, 1, true], // 存在するアクション
            ['ContentFolder', 'delete', 1, 1, true], // 存在するアクション
            // ['BlogContent', 'manage', 1, 1, true], // 存在するアクション
            // ['MailContent', 'manage', 1, 1, true], // 存在するアクション
            // ['Page', 'copy', 1, 1, true], // 存在するアクション
            // 運営ユーザー
            // ['ContentFolder', 'hoge', 2, 2, false], // 存在しないアクション
            // ['Page', 'add', 2, 2, true], // 存在するアクション（権限あり）
            // ['Page', 'edit', 2, 2, true], // 存在するアクション（権限あり）
            // ['Page', 'delete', 1, 2, true], // 存在するアクション（権限あり）
            // ['ContentFolder', 'edit', 1, 2, false], // 存在するアクション（権限なし）
            // ['ContentAlias', 'add', 1, 2, false], // 存在するアクション（権限なし）
            // ['ContentLink', 'add', 1, 2, false], // 存在するアクション（権限なし）
            // ['BlogContent', 'add', 1, 2, false], // 存在するアクション（権限なし）
            // ['MailContent', 'edit', 2, 2, false], // 存在するアクション（権限なし）
        ];
    }

    /**
     * コンテンツIDよりURLを取得する
     * getUrlById
     *
     * public function testGetUrlById() {
     * $this->markTestIncomplete('このメソッドは、モデルをラッピングしているメソッドのためスキップします。');
     * }
     */

    /**
     * フルURLを取得する
     * getUrl
     *
     * public function testGetUrl() {
     * $this->markTestIncomplete('このメソッドは、モデルをラッピングしているメソッドのためスキップします。');
     * }
     */

    /**
     * プレフィックスなしのURLを取得する
     * getPureUrl
     *
     * public function testGetPureUrl() {
     * $this->markTestIncomplete('このメソッドは、モデルをラッピングしているメソッドのためスキップします。');
     * }
     */

    /**
     * test getContentFolderList
     */
    public function testGetContentFolderList()
    {
        $result = $this->BcContents->getContentFolderList(1);
        $this->assertEquals(
            [
                1 => "baserCMSサンプル",
                6 => "　　　└サービス",
                18 => '　　　└ツリー階層削除用フォルダー(親)',
                19 => '　　　　　　└ツリー階層削除用フォルダー(子)',
                20 => '　　　　　　　　　└ツリー階層削除用フォルダー(孫)',
                21 => '　　　└testEdit',
            ],
            $result);

        $result = $this->BcContents->getContentFolderList(1, ['conditions' => ['site_root' => false]]);
        $this->assertEquals([
            6 => 'サービス',
            18 => 'ツリー階層削除用フォルダー(親)',
            19 => '　　　└ツリー階層削除用フォルダー(子)',
            20 => '　　　　　　└ツリー階層削除用フォルダー(孫)',
            21 => 'testEdit',
        ], $result);
    }

    /**
     * 現在のURLを元に指定したサブサイトのURLを取得する
     * getCurrentRelatedSiteUrl
     * フロントエンド専用メソッド
     * @param string $siteName
     * @param mixed|string $expect 期待値
     * @dataProvider getCurrentRelatedSiteUrlDataProvider
     */
    public function testGetCurrentRelatedSiteUrl($siteName, $expect)
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->BcContents->request = $this->getRequest('/');
        $_SERVER['HTTP_USER_AGENT'] = 'iPhone';
        $result = $this->BcContents->getCurrentRelatedSiteUrl($siteName);
        $this->assertEquals($expect, $result);
    }

    public static function getCurrentRelatedSiteUrlDataProvider()
    {
        return [
            // 戻り値が空でないもの（）
            ['smartphone', '/s/'],
            ['mobile', '/m/'],
            // $siteNameの値が空の場合、返り値も空
            ['', ''],
            ['hoge', ''],
        ];
    }

    /**
     * 関連サイトのコンテンツを取得
     * getRelatedSiteContents
     * フロントエンド専用メソッド
     * @param int $id コンテンツID = Null
     * @param array $options
     * @param array | false $expect 期待値
     * @dataProvider getRelatedSiteContentsDataProvider
     */
    public function testGetRelatedSiteContents($id, $options, $expect)
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->BcContents->request = $this->getRequest('/');
        $_SERVER['HTTP_USER_AGENT'] = 'iPhone';
        $result = $this->BcContents->getRelatedSiteContents($id, $options);
        if (!empty($result[1]['Content']['id'])) {
            $result = $result[1]['Content']['id'];
        }
        $this->assertEquals($expect, $result);
    }

    public static function getRelatedSiteContentsDataProvider()
    {
        return [
            // コンテンツIDが空 オプションも空
            [null, [], 9],
            // コンテンツIDが空  オプション excludeIds 0~1
            ['', ['excludeIds' => [0]], 10],
            ['', ['excludeIds' => [1]], 10],
            // コンテンツIDが空  オプション excludeIds 2~
            ['', ['excludeIds' => [2]], 9],
            ['', ['excludeIds' => [99]], 9],
            // コンテンツIDに値が入っていれば、false
            [1, ['excludeIds' => []], 2],
            [99, [], []],
        ];
    }

    /**
     * 関連サイトのリンク情報を取得する
     * フロントエンド専用メソッド
     * getRelatedSiteLinks
     * @param int $id
     * @param array $options
     * @param array $expect 期待値
     * @dataProvider getRelatedSiteLinksDataProvider
     */
    public function testGetRelatedSiteLinks($id, $options, $expect)
    {
        ContentFactory::make(['site_id' => 1, 'url' => '/'])->persist();
        ContentFactory::make(['site_id' => 2, 'main_site_content_id' => 1, 'url' => '/en/'])->persist();
        ContentFactory::make(['site_id' => 3, 'main_site_content_id' => 3, 'url' => '/en/about'])->persist();

        $result = $this->BcContents->getRelatedSiteLinks($id, $options);
        $this->assertEquals($expect, $result);
    }

    public static function getRelatedSiteLinksDataProvider()
    {
        return [
            [null, [], [['prefix' => '', 'name' => 'メインサイト', 'url' => '/index']]],
            [0, [], [['prefix' => '', 'name' => 'メインサイト', 'url' => '/index']]],
            [1, [], [['prefix' => '', 'name' => 'メインサイト', 'url' => '/'], ['prefix' => 'en', 'name' => '英語サイト', 'url' => '/en/']]],
            [1, ['excludeIds' => [1]], [['prefix' => 'en', 'name' => '英語サイト', 'url' => '/en/']]],
            [3, [], [['prefix' => 'en', 'name' => '英語サイト', 'url' => '/en/about']]],
        ];
    }

    /**
     * コンテンツ設定を Json 形式で取得する
     * getJsonItems
     */
    public function testGetJsonItems()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->loginAdmin($this->getRequest());
        App::uses('BcContentsComponent', 'Controller/Component');
        $BcContentsComponent = new BcContentsComponent(new ComponentCollection());
        $BcContentsComponent->setupAdmin();
        $View = new BcAppView();
        $View->set('contentsItems', $BcContentsComponent->getConfig('items'));
        $View->helpers = ['BcContents'];
        $View->loadHelpers();
        $View->BcContents->setup();
        $result = $View->BcContents->getJsonItems();
        // JSON形式が正しいかどうか
        $this->assertTrue(is_string($result) && is_array(json_decode($result, true)) && (json_last_error() == JSON_ERROR_NONE)? true : false);
    }

    /**
     * @param string $expect 期待値
     * @param string $no
     * @dataProvider getJsonItemsDataProvider
     */
    public function testgetJsonItemsEquals($expect, $no)
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->loginAdmin($this->getRequest());
        App::uses('BcContentsComponent', 'Controller/Component');
        $BcContentsComponent = new BcContentsComponent(new ComponentCollection());
        $BcContentsComponent->setupAdmin();
        $View = new BcAppView();
        $View->set('contentsItems', $BcContentsComponent->getConfig('items'));
        $View->helpers = ['BcContents'];
        $View->loadHelpers();
        $View->BcContents->setup();
        // 　getJsonItemsで取得した値がitemsの値と等しいかどうか
        $result = json_decode($View->BcContents->getJsonItems(), true);
        $result = $result[$no]['title'];
        $this->assertEquals($expect, $result);
    }

    public static function getJsonItemsDataProvider()
    {
        return [
            ['無所属コンテンツ', 'Default'],
            ['フォルダー', 'ContentFolder'],
            ['ブログ', 'BlogContent'],
        ];
    }

    /**
     * 親フォルダを取得する
     *
     * @param $expected
     * @param $id
     * @param $direct
     * @dataProvider getParentDataProvider
     */
    public function testGetParent($expected, $id, $direct)
    {
        if (is_string($id)) {
            $this->BcContents = new BcContentsHelper(new BcAdminAppView($this->getRequest($id)));
            $id = null;
        }
        $result = $this->BcContents->getParent($id, $direct);
        if ($direct) {
            if ($result) {
                $result = $result->id;
            }
        } else {
            if ($result) {
                $result = Hash::extract($result, '{n}.id');
            }
        }
        $this->assertEquals($expected, $result);
    }

    public static function getParentDataProvider()
    {
        return [
            [1, 4, true],            // ダイレクト ROOT直下
            [21, 22, true],            // ダイレクト フォルダ内
            [false, 1, true],        // ダイレクト ルートフォルダ
            [false, 1, true],        // ダイレクト 存在しないコンテンツ
            [[24], 27, false],    // パス ２階層配下
            [[24], 25, false],    // パス ３階層配下
            [[1, 6], 12, false],    // パス スマホ２階層配下
            [false, '/service/service2/test', true],    // パス 存在しないコンテンツ
            [[1, 6], '/service/service2', false] // パス URLで解決
        ];
    }

    /**
     * フォルダリストを取得する
     *
     * public function testGetContentFolderList() {
     * $this->markTestIncomplete('このメソッドは、モデルをラッピングしているメソッドのためスキップします。');
     * }
     */

    /**
     * エンティティIDからコンテンツの情報を取得
     * getContentByEntityId
     *
     * @param string $contentType コンテンツタイプ
     * ('Page','MailContent','BlogContent','ContentFolder')
     * @param int $id エンティティID
     * @param string $field 取得したい値
     *  'name','url','title'など　初期値：Null
     *  省略した場合配列を取得
     * @param string|bool $expect 期待値
     * @dataProvider getContentByEntityIdDataProvider
     */
    public function testgetContentByEntityId($expect, $id, $contentType, $field)
    {
        $result = $this->BcContents->getContentByEntityId($id, $contentType, $field);
        $this->assertEquals($expect, $result);
    }

    public static function getContentByEntityIdDataProvider()
    {
        return [
            // 存在するID（0~2）を指定した場合
            ['/news/', '31', 'BlogContent', 'url'],
            ['/contact/', '30', 'MailContent', 'url'],
            ['/index', '2', 'Page', 'url'],
            ['/service/', '4', 'ContentFolder', 'url'],
            ['/service/service1', '5', 'Page', 'url'],
            ['サービス２', '6', 'Page', 'title'],
            // 存在しないIDを指定した場合
            [false, '5', 'BlogContent', 'name'],
            //指定がおかしい場合
            [false, '5', 'Blog', 'url'],
        ];
    }

    /**
     * urlからコンテンツの情報を取得
     * Test getContentByUrl
     */
    public function testGetContentByUrl()
    {
        ContentFactory::make(['url' => '/test_no_1' ,'type' => 'ContentFolder', 'status' => true])->persist();
        ContentFactory::make(['url' => '/test_no_2' ,'type' => 'ContentFolder', 'status' => false])->persist();
        $result = $this->BcContents->getContentByUrl('/test_no_1', 'ContentFolder');
        $this->assertNotEmpty($result);
        $result = $this->BcContents->getContentByUrl('/test_no_2', 'ContentFolder');
        $this->assertFalse($result);
    }

    /**
     * IDがコンテンツ自身の親のIDかを判定する
     * @param $id
     * @param $parentId
     * @param $expects
     * @dataProvider isParentIdDataProvider
     */
    public function testIsParentId($id, $parentId, $expects)
    {
        $this->assertEquals($expects, $this->BcContents->isParentId($id, $parentId));
    }

    public static function isParentIdDataProvider()
    {
        return [
            [2, 1, false],
            [5, 1, true],
            [5, 2, false],
            [26, 24, true]
        ];
    }

    public function test__construct()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * 現在のページがコンテンツフォルダかどうか確認する
     * @param $url
     * @param $expects
     * @dataProvider isFolderDataProvider
     */
    public function testIsFolder($url, $expects)
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $this->BcContents->request = $this->_getRequest($url);
        $this->assertEquals($expects, $this->BcContents->isFolder());
    }

    public static function isFolderDataProvider()
    {
        return [
            ['/', false],    // index あり
            ['/about', false],
            ['/service/', false],    // index あり
            ['/service/index', false],
            ['/contact/', false],
            ['/service/sub_service/', true],    // index なし
            ['/service/hoge', false]    // 存在しない
        ];
    }

    /**
     * testIsEditable
     * @param int|null $id
     * @param bool $adminLogin
     * @param bool $result
     * @return void
     * @dataProvider isEditableDataProvider
     */
    public function testIsEditable($id, $adminLogin, $result)
    {
        if ($adminLogin) $this->loginAdmin($this->getRequest());
        $content = $id ? $this->BcContents->ContentsService->get($id) : [];
        $this->assertEquals($result, $this->BcContents->isEditable($content));
    }

    public static function isEditableDataProvider()
    {
        return [
            // データがない場合false
            [null, false, false],
            // site_rootがtrueの場合false
            [1, false, false],
            // site_rootがfalse且つアドミンでログインしてる場合
            [4, true, true],
        ];
    }

    /**
     * サイトIDからコンテンツIDを取得する
     * getSiteRootId
     *
     * @param int $siteId
     * @param string|bool $expect 期待値
     * @dataProvider getSiteRootIdDataProvider
     */
    public function testGetSiteRootId($siteId, $expect)
    {
        $result = $this->BcContents->getSiteRootId($siteId);
        $this->assertEquals($expect, $result);
    }

    public static function getSiteRootIdDataProvider()
    {
        return [
            // 存在するサイトID（0~2）を指定した場合
            [1, 1],
            // 存在しないサイトIDを指定した場合
            [4, false],
        ];
    }

    /**
     * 対象コンテンツが属するフォルダまでのフルパスを取得する
     * フォルダ名称部分にはフォルダ編集画面へのリンクを付与する
     * @param int $id コンテンツID
     * @param string $expected 期待値
     * @dataProvider getFolderLinkedUrlDataProvider
     */
    public function testGetFolderLinkedUrl($url, $expected)
    {
        $content = $this->getTableLocator()->get('BaserCore.Contents')->find()->contain(['Sites'])->where([
            'url' => $url
        ])->first();
        $this->assertEquals($expected, $this->BcContents->getFolderLinkedUrl($content));
    }

    public static function getFolderLinkedUrlDataProvider()
    {
        return [
            ['/', 'https://localhost/'],
            ['/about', 'https://localhost/'],
        ];
    }

    /**
     * フォルダ内の次のコンテンツへのリンクを取得する
     * @param string $url
     * @param string $title
     * @param array $options オプション（初期値 : array()）
     *    - `class` : CSSのクラス名（初期値 : 'next-link'）
     *    - `arrow` : 表示文字列（初期値 : ' ≫'）
     *    - `overFolder` : フォルダ外も含めるかどうか（初期値 : false）
     * @param string $expected
     *
     * @dataProvider getNextLinkDataProvider
     */
    public function testGetNextLink($url, $title, $options, $expected)
    {
        $this->BcContents->getView()->setRequest($this->getRequest($url));
        $result = $this->BcContents->getNextLink($title, $options);
        $this->assertEquals($expected, $result);
    }

    public static function getNextLinkDataProvider()
    {
        return [
            ['/company', '', ['overFolder' => false], false], // PC
            ['/company', '次のページへ', ['overFolder' => false], false], // PC
            ['/about', '', ['overFolder' => true], '<a href="/service/service1" class="next-link">サービス１ ≫</a>'], // PC
            ['/about', '次のページへ', ['overFolder' => true], '<a href="/service/service1" class="next-link">次のページへ</a>'], // PC
            ['/en/サイトID3の固定ページ2', '', ['overFolder' => false], '<a href="/en/サイトID3の固定ページ3" class="next-link">サイトID3の固定ページ3 ≫</a>'], // smartphone
            // ['/s/about', '', ['overFolder' => false], '<a href="/s/icons" class="next-link">アイコンの使い方 ≫</a>'], // smartphone
            // ['/s/about', '次のページへ', ['overFolder' => false], '<a href="/s/icons" class="next-link">次のページへ</a>'], // smartphone
            // ['/s/sitemap', '', ['overFolder' => true], '<a href="/s/contact/" class="next-link">お問い合わせ ≫</a>'], // smartphone
            // ['/s/sitemap', '次のページへ', ['overFolder' => true], '<a href="/s/contact/" class="next-link">次のページへ</a>'], // smartphone
        ];
    }
    /**
     * フォルダ内の次のコンテンツへのリンクを出力する
     *
     *    public function testNextLink($url, $title, $options, $expected) { }
     */
    /**
      * testNextLink
      *
      * @return void
      */
    public function testNextLink()
    {
        $this->BcContents->getView()->setRequest($this->getRequest('/about'));
        ob_start();
        $this->BcContents->nextLink('次のページへ', ['overFolder' => false]);
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/<a href="\/contact\/" class="next-link">/', $result);
    }

    /**
     * フォルダ内の前のコンテンツへのリンクを取得する
     * @param string $url
     * @param string $title
     * @param array $options オプション（初期値 : array()）
     *    - `class` : CSSのクラス名（初期値 : 'next-link'）
     *    - `arrow` : 表示文字列（初期値 : ' ≫'）
     *    - `overFolder` : フォルダ外も含めるかどうか（初期値 : false）
     * @param string $expected
     *
     * @dataProvider getPrevLinkDataProvider
     */
    public function testGetPrevLink($url, $title, $options, $expected)
    {
        $this->BcContents->getView()->setRequest($this->getRequest($url));
        $result = $this->BcContents->getPrevLink($title, $options);
        $this->assertEquals($expected, $result);
    }

    public static function getPrevLinkDataProvider()
    {
        return [
            ['/company', '', ['overFolder' => false], false], // PC
            ['/company', '前のページへ', ['overFolder' => false], false], // PC
            ['/about', '', ['overFolder' => true], '<a href="/news/" class="prev-link">≪ NEWS(※関連Fixture未完了)</a>'], // PC
            ['/about', '前のページへ', ['overFolder' => true], '<a href="/news/" class="prev-link">前のページへ</a>'], // PC
            ['/en/サイトID3の固定ページ2', '', ['overFolder' => false], '<a href="/en/サイトID3の固定ページ" class="prev-link">≪ サイトID3の固定ページ</a>'], // smartphone
            // ['/s/about', '', ['overFolder' => false], '<a href="/s/" class="prev-link">≪ トップページ</a>'], // smartphone
            // ['/s/about', '前のページへ', ['overFolder' => false], '<a href="/s/" class="prev-link">前のページへ</a>'], // smartphone
            // ['/s/sitemap', '', ['overFolder' => true], '<a href="/s/icons" class="prev-link">≪ アイコンの使い方</a>'], // smartphone
            // ['/s/sitemap', '前のページへ', ['overFolder' => true], '<a href="/s/icons" class="prev-link">前のページへ</a>'], // smartphone
        ];
    }

    /**
     * testPrevLink
     *
     * @return void
     */
    public function testPrevLink()
    {
        $this->BcContents->getView()->setRequest($this->getRequest('/about'));
        ob_start();
        $this->BcContents->prevLink('前のページへ', ['overFolder' => false]);
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/<a href="\/news\/" class="prev-link">/', $result);
    }

    /**
     * testGetPageByNextOrPrev
     *
     * @return void
     * @dataProvider getPageNeighborsDataProvider
     */
    public function testGetPageNeighbors($overFolder, $title)
    {
        $content = $this->BcContents->ContentsService->getIndex(['name' => 'about'])->first();
        $neighbors = $this->execPrivateMethod($this->BcContents, 'getPageNeighbors', [$content, $overFolder]);
        $this->assertEquals($neighbors['prev']['title'], $title['prev']);
        $this->assertEquals($neighbors['next']['title'], $title['next']);
    }

    public static function getPageNeighborsDataProvider()
    {
        return [
            [false, ['prev' => "NEWS(※関連Fixture未完了)", 'next' => "お問い合わせ(※関連Fixture未完了)"]],
            [true, ['prev' => "NEWS(※関連Fixture未完了)", 'next' => "サービス１"]],
        ];
    }

    /**
     * Test _getContent
     */
    public function test_getContent()
    {
        $content = $this->execPrivateMethod($this->BcContents, '_getContent', [['Contents.id' => 1]]);
        $this->assertEquals(1, $content->id);

        $plugin = $this->execPrivateMethod($this->BcContents, '_getContent', [['Contents.id' => 1], 'plugin']);
        $this->assertEquals('BaserCore', $plugin);
    }

    /**
     * test getCurrentContent
     */
    public function testGetCurrentContent()
    {
        // トップページ
        $content = $this->BcContents->getCurrentContent();
        $this->assertEquals('/index', $content->url);

        // サービスページ
        $this->BcContents->getView()->setRequest($this->getRequest('/service/index'));
        $content = $this->BcContents->getCurrentContent();
        $this->assertEquals('/service/', $content->url);

        // 管理画面の場合
        $request = $this->getRequest('/baser/admin');
        $this->loginAdmin($request);
        $this->BcContents->getView()->setRequest($request);
        $this->assertFalse($this->BcContents->getCurrentContent());
    }

    /**
     * test getCurrentSite
     */
    public function testGetCurrentSite()
    {
        // トップページ
        $entity = $this->BcContents->getCurrentSite();
        $this->assertEquals('baserCMS inc.', $entity->title);

        // サービスページ
        $this->BcContents->getView()->setRequest($this->getRequest('/en/'));
        $entity = $this->BcContents->getCurrentSite();
        $this->assertEquals('en', $entity->name);

        // 管理画面の場合
        $request = $this->getRequest('/baser/admin');
        $this->loginAdmin($request);
        $this->BcContents->getView()->setRequest($request);
        $this->assertFalse($this->BcContents->getCurrentSite());
    }

    public function test_getJsonItems()
    {
        //Define a config
        $config = [
          'items' => [
              'item1',
              'item2',
              'item3'
          ]];

        //Set the config
        $this->BcContents->setConfig($config);
        $rs = $this->BcContents->getJsonItems();
        $this->assertEquals('["item1","item2","item3"]', $rs);
    }

    public function test_getSiteRoot()
    {
        $siteRoot = $this->BcContents->getSiteRoot(1);
        $this->assertEquals(1, $siteRoot->id);

        //sub site
        $siteRoot = $this->BcContents->getSiteRoot(3);
        $this->assertEquals(24, $siteRoot->id);
        $this->assertEquals('サイトID3のフォルダ', $siteRoot->name);

        $siteRoot = $this->BcContents->getSiteRoot(99);
        $this->assertNull($siteRoot);
    }

    /**
     * test isFolder
     */
    public function test_isFolder()
    {
        //isAdminSystem true
        $this->getRequest('baser/admin');
        $rs = $this->BcContents->isFolder();
        $this->assertFalse($rs);

        //contentFolder not exist
        $content = ContentFactory::make(['type' => '',])->getEntity();
        $this->BcContents->getView()->setRequest($this->getRequest()->withAttribute('currentContent', $content));
        $rs = $this->BcContents->isFolder();
        $this->assertFalse($rs);

        //contentFolder exist
        $content['type'] = 'ContentFolder';
        $this->BcContents->getView()->setRequest($this->getRequest()->withAttribute('currentContent', $content));
        $rs = $this->BcContents->isFolder();
        $this->assertTrue($rs);
    }

}
