<?php
declare(strict_types=1);

use BaserCore\Database\Migration\BcMigration;

class AddStatusToUsers extends BcMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('status', 'boolean', [
            'default' => true,
            'null' => true,
        ]);
        $table->update();
    }
}
