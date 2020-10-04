<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200928173309 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_request ADD owner_id INT NOT NULL, ADD closed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE verification_request ADD CONSTRAINT FK_20FDDF4E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE verification_request ADD CONSTRAINT FK_20FDDF4EE1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_20FDDF4E7E3C61F9 ON verification_request (owner_id)');
        $this->addSql('CREATE INDEX IDX_20FDDF4EE1FA7797 ON verification_request (closed_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_request DROP FOREIGN KEY FK_20FDDF4E7E3C61F9');
        $this->addSql('ALTER TABLE verification_request DROP FOREIGN KEY FK_20FDDF4EE1FA7797');
        $this->addSql('DROP INDEX IDX_20FDDF4E7E3C61F9 ON verification_request');
        $this->addSql('DROP INDEX IDX_20FDDF4EE1FA7797 ON verification_request');
        $this->addSql('ALTER TABLE verification_request DROP owner_id, DROP closed_by_id');
    }
}
