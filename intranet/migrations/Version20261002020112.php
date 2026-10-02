<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002020112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create photo gallery tables (photo_album, photo)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE photo (
          id INT AUTO_INCREMENT NOT NULL,
          album_id INT NOT NULL,
          uploaded_by_id INT NOT NULL,
          image_name VARCHAR(255) NOT NULL,
          image_size INT DEFAULT NULL,
          caption VARCHAR(255) DEFAULT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          INDEX IDX_14B784181137ABCF (album_id),
          INDEX IDX_14B78418A2B28FE8 (uploaded_by_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photo_album (
          id INT AUTO_INCREMENT NOT NULL,
          created_by_id INT NOT NULL,
          title VARCHAR(150) NOT NULL,
          description LONGTEXT DEFAULT NULL,
          event_date DATE DEFAULT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          INDEX IDX_83C969F4B03A8386 (created_by_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          photo
        ADD
          CONSTRAINT FK_14B784181137ABCF FOREIGN KEY (album_id) REFERENCES photo_album (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          photo
        ADD
          CONSTRAINT FK_14B78418A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE
          photo_album
        ADD
          CONSTRAINT FK_83C969F4B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784181137ABCF');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418A2B28FE8');
        $this->addSql('ALTER TABLE photo_album DROP FOREIGN KEY FK_83C969F4B03A8386');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE photo_album');
    }
}
