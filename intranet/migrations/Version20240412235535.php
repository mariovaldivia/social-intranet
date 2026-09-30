<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240412235535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD user_id INT NOT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7A76ED395 ON event (user_id)');
        $this->addSql('ALTER TABLE post ADD event_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A8A6C8D71F7E88B ON post (event_id)');
        $this->addSql('ALTER TABLE profile ADD created_by_id INT DEFAULT NULL, ADD email VARCHAR(100) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8157AA0FB03A8386 ON profile (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY FK_8157AA0FB03A8386');
        $this->addSql('DROP INDEX IDX_8157AA0FB03A8386 ON profile');
        $this->addSql('ALTER TABLE profile DROP created_by_id, DROP email, DROP created_at');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D71F7E88B');
        $this->addSql('DROP INDEX UNIQ_5A8A6C8D71F7E88B ON post');
        $this->addSql('ALTER TABLE post DROP event_id');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A76ED395');
        $this->addSql('DROP INDEX IDX_3BAE0AA7A76ED395 ON event');
        $this->addSql('ALTER TABLE event DROP user_id, DROP created_at');
    }
}
