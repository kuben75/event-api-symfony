<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617122343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE registration (id SERIAL NOT NULL, attendee_id INT NOT NULL, event_id INT NOT NULL, registration_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_62A8A7A7BCFD782A ON registration (attendee_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_62A8A7A771F7E88B ON registration (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A7BCFD782A FOREIGN KEY (attendee_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A771F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE registration DROP CONSTRAINT FK_62A8A7A7BCFD782A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE registration DROP CONSTRAINT FK_62A8A7A771F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE registration
        SQL);
    }
}
