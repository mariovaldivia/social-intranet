<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002011925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (
          id INT AUTO_INCREMENT NOT NULL,
          legal_name VARCHAR(255) NOT NULL,
          trade_name VARCHAR(255) DEFAULT NULL,
          tax_id VARCHAR(30) NOT NULL,
          industry VARCHAR(255) DEFAULT NULL,
          email VARCHAR(180) DEFAULT NULL,
          phone VARCHAR(50) DEFAULT NULL,
          website VARCHAR(255) DEFAULT NULL,
          address VARCHAR(255) DEFAULT NULL,
          city VARCHAR(100) DEFAULT NULL,
          region VARCHAR(100) DEFAULT NULL,
          country VARCHAR(2) DEFAULT NULL,
          postal_code VARCHAR(20) DEFAULT NULL,
          active TINYINT(1) NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          UNIQUE INDEX UNIQ_4FBF094FB2A824D8 (tax_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE company');
    }
}
