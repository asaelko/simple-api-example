<?php

declare(strict_types=1);

namespace <namespace>;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration <version>
 *
 * Description goes here
 */
class Version<version> extends AbstractMigration
{
    /**
     * What will be done when migration is rolling up
     *
     * @param Schema $schema
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
<up>
    }

    /**
     * What will be done when migration is rolling back
     *
     * @param Schema $schema
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
<down>
    }
}
