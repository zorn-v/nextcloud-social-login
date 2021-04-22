<?php

namespace OCA\SocialLogin\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version040500Date2021042120124242 extends SimpleMigrationStep {

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('sociallogin_publickeys')) {
            $table = $schema->createTable('sociallogin_publickeys');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('kid', 'string', [
                'notnull' => true,
                'unique' => true,
                'length' => 255,
            ]);
            $table->addColumn('issuer', 'string', [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('pem', 'text', [
                'notnull' => true,
            ]);
            $table->addColumn('last_updated', 'integer', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['kid', 'issuer'], 'sociallogin_kid_issuer_index');
        }
        return $schema;
    }
}
