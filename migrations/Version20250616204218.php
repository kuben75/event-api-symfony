<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616204218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event_setting (id SERIAL NOT NULL, event_id INT NOT NULL, setting_key VARCHAR(50) NOT NULL, setting_value TEXT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1A23113771F7E88B ON event_setting (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_setting ADD CONSTRAINT FK_1A23113771F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_setting DROP CONSTRAINT FK_1A23113771F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_setting
        SQL);
    }
}
