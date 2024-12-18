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
namespace BaserCore\Test\TestCase\Model\Behavior;

use ArrayObject;
use BaserCore\Test\Scenario\ContentFoldersScenario;
use BaserCore\Test\Scenario\ContentsScenario;
use BaserCore\Utility\BcUtil;
use Cake\Database\Connection;
use Cake\ORM\Entity;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Service\ContentsService;
use BaserCore\Model\Table\ContentFoldersTable;
use BaserCore\Model\Behavior\BcContentsBehavior;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;

/**
 * Class BcContentsBehaviorTest
 * @property ContentFoldersTable $ContentsFolder
 *
 */
class BcContentsBehaviorTest extends BcTestCase
{
    /**
     * ScenarioAwareTrait
     */
    use ScenarioAwareTrait;

    /**
     * @var ContentFoldersTable|BcContentsBehavior;
     */
    protected $table;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->table = $this->getTableLocator()->get('BaserCore.ContentFolders');
        $this->table->setPrimaryKey(['id']);
        $this->table->addBehavior('BaserCore.BcContents');
        $this->contentService = new ContentsService();
        $this->BcContentsBehavior = new BcContentsBehavior($this->table);
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
     * testInitialize
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertTrue($this->table->__isset('Contents'));
    }

    /**
     * testAfterMarshal
     *
     * エラーがセットされているかをテスト
     * @return void
     */
    public function testAfterMarshal()
    {
        $this->loadFixtureScenario(ContentFoldersScenario::class);
        $this->loadFixtureScenario(ContentsScenario::class);
        $contentFolder = $this->table->find()->first();
        $result = $this->table->dispatchEvent('Model.afterMarshal', ['entity' => $contentFolder, 'data' => new ArrayObject($contentFolder->toArray()), 'options' => new ArrayObject(['validate' => true])]);
        $contentFolder = $result->getData('entity');
        $this->assertEquals(['content' => ['_required' => "関連するコンテンツがありません"]], $contentFolder->getErrors());
        // プラグインとタイプが設定されてるかをテストする
        $contentFolder = $this->table->find()->contain("Contents")->first();
        $result = $this->table->dispatchEvent('Model.afterMarshal', ['entity' => $contentFolder, 'data' => new ArrayObject($contentFolder->toArray()), 'options' => new ArrayObject(['validate' => true])]);
        $contentFolder = $result->getData('entity');
        $this->assertEquals('BaserCore', $contentFolder->content->plugin);
        $this->assertEquals('ContentFolder', $contentFolder->content->type);
    }

    /**
     * Setup
     */
    public function testSetup()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * Before validate
     *
     * Content のバリデーションを実行
     * 本体のバリデーションも同時に実行する為、Contentのバリデーション判定は、 beforeSave にて確認
     */
    public function testBeforeValidate()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * Before save
     *
     * Content のバリデーション結果確認
     */
    public function testBeforeSave()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $data = new Entity([
                'folder_template' => 'テストBeforeSave',
                'content' => [
                    "name" => "", // validation error
                    "parent_id" => "1",
                    "title" => "新しい フォルダー",
                    "plugin" => 'BaserCore',
                    "type" => "ContentFolder",
                    "site_id" => "1",
                    "alias_id" => "",
                    "entity_id" => "",
                ]
        ]);
        $this->assertFalse($this->table->save($data));
        // TODO: イベント設定うまくいかないので調整する
        // $listener = function ($event, $entity, $options) {
        //     $options['validate'] = false;
        // };
        // $this->table->getEventManager()->on('Model.beforeSave', $listener);
        // $this->assertTrue($this->table->save($data));

    }

    /**
     * After save
     *
     * Content を保存する
     */
    public function testAfterSave()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $data = new Entity([
            'folder_template' => 'テストBeforeSave',
            'content' => [
                "parent_id" => "1",
                "title" => "新しい フォルダー",
                "plugin" => 'BaserCore',
                "type" => "ContentFolder",
                "site_id" => "1",
                "alias_id" => "",
                "entity_id" => "",
            ]
    ]);
        $this->assertTrue($this->table->save($data));
    }

    /**
     * Before delete
     *
     * afterDelete でのContents物理削除準備をする
     */
    public function testBeforeDelete()
    {
        $this->loadFixtureScenario(ContentFoldersScenario::class);
        $this->loadFixtureScenario(ContentsScenario::class);
        $event = $this->table->dispatchEvent('Model.beforeDelete', [
            'entity' => $this->table->get(10),
            'options' => new ArrayObject(),
        ]);
        $this->assertNotEmpty($event->getData('entity')->content);
    }
    /**
     * After delete
     *
     * 削除したデータに連携する Content を削除
     */
    public function testAfterDelete()
    {
        $this->loadFixtureScenario(ContentFoldersScenario::class);
        $this->loadFixtureScenario(ContentsScenario::class);
        $entity = $this->table->get(10);
        $entity->content = $this->contentService->getTrash(16);
        $this->table->dispatchEvent('Model.afterDelete', [
            'entity' => $entity,
            'options' => new ArrayObject(),
        ]);
        $this->assertTrue($this->table->Contents->find()->where(['entity_id' => 10])->all()->isEmpty());

    }

    /**
     * test onAlias
     */
    public function testOnAlias()
    {
        $this->BcContentsBehavior->onAlias();

        $conditions = $this->BcContentsBehavior->Contents->getConditions();

        $this->assertArrayHasKey('Contents.type', $conditions);
        $this->assertEquals('ContentFolder', $conditions['Contents.type']);
    }

    /**
     * test offAlias
     *
     */
    public function testOffAlias()
    {
        $this->BcContentsBehavior->offAlias();

        $conditions = $this->BcContentsBehavior->Contents->getConditions();

        $this->assertArrayHasKey('Contents.type', $conditions);
        $this->assertArrayHasKey('Contents.alias_id IS', $conditions);

        $this->assertNull($conditions['Contents.alias_id IS']);
        $this->assertEquals('ContentFolder', $conditions['Contents.type']);
    }

    /**
     * test getType
     */
    public function testGetType()
    {
        //prefix = ""
        $this->table->setTable('unittest_baser_contents');
        $rs = $this->BcContentsBehavior->getType();
        //戻り確認
        $this->assertEquals('UnittestBaserContent', $rs);

        //prefix = "unittest_"
        $dbConfig = BcUtil::getCurrentDbConfig();
        $dbConfig['prefix'] = 'unittest_';
        $connection = new Connection($dbConfig);
        $this->table->setConnection($connection);
        $this->table->setTable('unittest_baser_contents');

        $rs = $this->BcContentsBehavior->getType();
        //戻り確認
        $this->assertEquals('BaserContent', $rs);
    }

}
