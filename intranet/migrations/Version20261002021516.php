<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002021516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link timeline posts to photo albums and uploaded photos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo ADD post_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE
          photo
        ADD
          CONSTRAINT FK_14B784184B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE
        SET
          NULL');
        $this->addSql('CREATE INDEX IDX_14B784184B89032C ON photo (post_id)');
        $this->addSql('ALTER TABLE post ADD album_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE
          post
        ADD
          CONSTRAINT FK_5A8A6C8D1137ABCF FOREIGN KEY (album_id) REFERENCES photo_album (id)');
        $this->addSql('CREATE INDEX IDX_5A8A6C8D1137ABCF ON post (album_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D1137ABCF');
        $this->addSql('DROP INDEX IDX_5A8A6C8D1137ABCF ON post');
        $this->addSql('ALTER TABLE post DROP album_id');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784184B89032C');
        $this->addSql('DROP INDEX IDX_14B784184B89032C ON photo');
        $this->addSql('ALTER TABLE photo DROP post_id');
    }
}
