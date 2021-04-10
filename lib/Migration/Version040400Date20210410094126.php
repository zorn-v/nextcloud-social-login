<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version040400Date20210410094126 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('sociallogin_connect')) {
            $table = $schema->createTable('sociallogin_connect');
            $table->addColumn('uid', 'string', [
                    'notnull' => true,
            ]);
            $table->addColumn('identifier', 'string', [
                    'notnull' => true,
                    'length' => 190,
            ]);
            $table->addUniqueIndex(['identifier'], 'sociallogin_connect_id');
        }
        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }
}
