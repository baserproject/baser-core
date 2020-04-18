<?php
/**
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright (c) baserCMS Users Community <http://basercms.net/community/>
 *
 * @copyright     Copyright (c) baserCMS Users Community
 * @link          http://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

Configure::config('baser', new PhpConfig());
Configure::load('BaserCore.setting', 'baser');
