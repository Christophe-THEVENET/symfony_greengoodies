<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260612072210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_user_id ON `order`');
        $this->addSql('ALTER TABLE `order` ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, ADD paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD shipping_label VARCHAR(100) DEFAULT NULL, ADD shipping_line1 VARCHAR(255) DEFAULT NULL, ADD shipping_line2 VARCHAR(255) DEFAULT NULL, ADD shipping_postal_code VARCHAR(20) DEFAULT NULL, ADD shipping_city VARCHAR(100) DEFAULT NULL, ADD shipping_country VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP stripe_payment_intent_id, DROP paid_at, DROP shipping_label, DROP shipping_line1, DROP shipping_line2, DROP shipping_postal_code, DROP shipping_city, DROP shipping_country');
        $this->addSql('CREATE INDEX IDX_user_id ON `order` (user_id)');
    }
}
