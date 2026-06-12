<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Ajoute un slug unique sur product (URLs lisibles type /produit/savon-bio)
 * et backfill les lignes existantes à partir de leur nom.
 */
final class Version20260611071504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute product.slug (unique) et backfill depuis le nom';
    }

    public function up(Schema $schema): void
    {
        // 1. Colonne nullable le temps du backfill
        $this->addSql('ALTER TABLE product ADD slug VARCHAR(255) DEFAULT NULL');

        // 2. Backfill des slugs à partir des noms existants
        $slugger = new AsciiSlugger();
        $rows = $this->connection->fetchAllAssociative('SELECT id, name FROM product');
        foreach ($rows as $row) {
            $slug = $slugger->slug((string) $row['name'])->lower()->toString();
            $this->addSql('UPDATE product SET slug = :slug WHERE id = :id', [
                'slug' => $slug,
                'id' => $row['id'],
            ]);
        }

        // 3. Contrainte NOT NULL + index unique
        $this->addSql('ALTER TABLE product MODIFY slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D34A04AD989D9B62 ON product');
        $this->addSql('ALTER TABLE product DROP slug');
    }
}
