<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) baserCMS User Community <https://basercms.net/community/>
 *
 * @copyright     Copyright (c) baserCMS User Community
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */

namespace BaserCore\View\Helper;

use BaserCore\Service\UserManageService;
use Cake\View\Helper;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;

/**
 * Class BcUserManageHelper
 * @package BaserCore\View\Helper
 */
class BcUserManageHelper extends Helper
{

    /**
     * User Manage Service
     * @var UserManageService
     */
    public $UserManage;

    /**
     * initialize
     * @param array $config
     * @checked
     * @noTodo
     * @unitTest
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->UserManage = new UserManageService();
    }

    /**
     * ログインユーザー自身の更新かどうか
     * @param int $id
     * @return false
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isSelfUpdate(int $id)
    {
        return $this->UserManage->isSelfUpdate($id);
    }

    /**
     * 更新ができるかどうか
     * @param int $id
     * @return false
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isEditable(int $id)
    {
        return $this->UserManage->isEditable($id);
    }

    /**
     * 削除ができるかどうか
     * @param int $id
     * @return false
     * @checked
     * @noTodo
     * @unitTest
     */
    public function isDeletable(int $id)
    {
        return $this->UserManage->isDeletable($id);
    }

    /**
     * ユーザーグループ選択用のリスト
     * @return array
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getUserGroupList()
    {
        return $this->UserManage->getUserGroupList();
    }

}
