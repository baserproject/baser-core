<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.19
 * @license       https://basercms.net/license/index.html MIT License
 */

/**
 * 5.0.19 アップデーター
 *
 * 書き込み権限チェック
 */
$notWritablePath = [];
if(!is_writable(ROOT . DS . 'config')) {
    $notWritablePath[] = ROOT . DS . 'config';
}
if($notWritablePath) {
    return [
        'updateMessage' => "アップデートを実行する前に次のファイルみ書き込み権限を与えてください<br>" . implode('<br>', $notWritablePath)
    ];
} else {
    return [];
}
