<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005000711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create site table (company offices, branches, work sites)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site (
          id INT AUTO_INCREMENT NOT NULL,
          company_id INT NOT NULL,
          name VARCHAR(150) NOT NULL,
          type VARCHAR(20) NOT NULL,
          code VARCHAR(30) DEFAULT NULL,
          address VARCHAR(255) DEFAULT NULL,
          city VARCHAR(100) DEFAULT NULL,
          region VARCHAR(100) DEFAULT NULL,
          country VARCHAR(2) DEFAULT NULL,
          phone VARCHAR(50) DEFAULT NULL,
          email VARCHAR(180) DEFAULT NULL,
          active TINYINT(1) NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          UNIQUE INDEX uniq_site_company_code (company_id, code),
          INDEX IDX_694309E4979B1AD6 (company_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          site
        ADD
          CONSTRAINT FK_694309E4979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4979B1AD6');
        $this->addSql('DROP TABLE site');
    }
}
