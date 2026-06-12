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
        $this->addSql('DROP INDEX unique_user_unvalidated_order ON `order`');
        $this->addSql('CREATE INDEX IDX_user_id ON `order` (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_user_id ON `order`');
        $this->addSql('CREATE UNIQUE INDEX unique_user_unvalidated_order ON `order` (user_id)');
    }
}
