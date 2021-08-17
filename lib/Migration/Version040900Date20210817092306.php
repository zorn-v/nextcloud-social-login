<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version040900Date20210817092306 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('sociallogin_tokens')) {
            $table = $schema->createTable('sociallogin_tokens');
            $table->addColumn('uid', 'string', [
                'notnull' => true,
            ]);
            $table->addColumn('accessToken', 'string', [
                'notnull' => true,
            ]);
            $table->addColumn('refreshToken', 'string', [
                'notnull' => true,
            ]);
            $table->addColumn('expiresAt', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('providerType', 'string', [
                'notnull' => true,
            ]);
            $table->addColumn('providerId', 'string', [
                'notnull' => true,
            ]);
            $table->addUniqueIndex(['uid'], 'sociallogin_tokens_id');
            $table->addUniqueConstraint(['uid', 'providerId']);
        }
        return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
