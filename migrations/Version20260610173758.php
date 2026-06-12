<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Corrige l'index unique sur user_id qui empêchait d'avoir
 * plusieurs commandes par utilisateur.
 */
final class Version20260610173758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace l\'index UNIQUE sur user_id par un index simple dans la table order';
    }

    public function up(Schema $schema): void
    {
        // Idempotent : en prod l'index unique a déjà été retiré par une autre
        // migration. On ne supprime/crée que ce qui est réellement nécessaire.
        $indexes = array_map(
            'strtolower',
            array_keys($this->connection->createSchemaManager()->listTableIndexes('order'))
        );

        if (in_array('unique_user_unvalidated_order', $indexes, true)) {
            $this->addSql('DROP INDEX unique_user_unvalidated_order ON `order`');
        }
        if (!in_array('idx_user_id', $indexes, true)) {
            $this->addSql('CREATE INDEX IDX_user_id ON `order` (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_user_id ON `order`');
        $this->addSql('CREATE UNIQUE INDEX unique_user_unvalidated_order ON `order` (user_id)');
    }
}
