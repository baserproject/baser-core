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

declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;

/**
 * Class UserGroup
 * @package App\Model\Entity
 * @property int $id
 * @property string|null $name
 * @property string|null $title
 * @property string|null $auth_prefix
 * @property bool|null $use_admin_globalmenu
 * @property string|null $default_favorites
 * @property bool|null $use_move_contents
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \App\Model\Entity\User[] $users
 */
class UserGroup extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'name' => true,
        'title' => true,
        'auth_prefix' => true,
        'use_admin_globalmenu' => true,
        'default_favorites' => true,
        'use_move_contents' => true,
        'modified' => true,
        'created' => true,
        'users' => true,
    ];
}
