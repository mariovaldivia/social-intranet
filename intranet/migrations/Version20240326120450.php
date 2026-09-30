<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240326120450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, management_id INT NOT NULL, name VARCHAR(100) NOT NULL, INDEX IDX_CD1DE18A80D51D0E (management_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE management (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, position VARCHAR(255) DEFAULT NULL, birthdate DATE DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, last_name VARCHAR(100) NOT NULL, identification VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18A80D51D0E FOREIGN KEY (management_id) REFERENCES management (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18A80D51D0E');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE management');
        $this->addSql('DROP TABLE profile');
    }
}
