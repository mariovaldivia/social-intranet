<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005002853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create site_activity table and its assigned users';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site_activity (
          id INT AUTO_INCREMENT NOT NULL,
          site_id INT NOT NULL,
          created_by_id INT DEFAULT NULL,
          date DATE NOT NULL,
          type VARCHAR(30) NOT NULL,
          description LONGTEXT NOT NULL,
          status VARCHAR(20) NOT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          INDEX idx_site_activity_date (date),
          INDEX IDX_A039B680F6BD1646 (site_id),
          INDEX IDX_A039B680B03A8386 (created_by_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_activity_user (
          site_activity_id INT NOT NULL,
          user_id INT NOT NULL,
          INDEX IDX_DCF465706AEE5F26 (site_activity_id),
          INDEX IDX_DCF46570A76ED395 (user_id),
          PRIMARY KEY(site_activity_id, user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          site_activity
        ADD
          CONSTRAINT FK_A039B680F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          site_activity
        ADD
          CONSTRAINT FK_A039B680B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE
        SET
          NULL');
        $this->addSql('ALTER TABLE
          site_activity_user
        ADD
          CONSTRAINT FK_DCF465706AEE5F26 FOREIGN KEY (site_activity_id) REFERENCES site_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          site_activity_user
        ADD
          CONSTRAINT FK_DCF46570A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site_activity DROP FOREIGN KEY FK_A039B680F6BD1646');
        $this->addSql('ALTER TABLE site_activity DROP FOREIGN KEY FK_A039B680B03A8386');
        $this->addSql('ALTER TABLE site_activity_user DROP FOREIGN KEY FK_DCF465706AEE5F26');
        $this->addSql('ALTER TABLE site_activity_user DROP FOREIGN KEY FK_DCF46570A76ED395');
        $this->addSql('DROP TABLE site_activity');
        $this->addSql('DROP TABLE site_activity_user');
    }
}
