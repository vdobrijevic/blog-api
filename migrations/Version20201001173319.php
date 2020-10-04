<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201001173319 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_request DROP FOREIGN KEY FK_20FDDF4EE1FA7797');
        $this->addSql('DROP INDEX IDX_20FDDF4EE1FA7797 ON verification_request');
        $this->addSql('ALTER TABLE verification_request DROP closed_by_id, DROP closed');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_request ADD closed_by_id INT DEFAULT NULL, ADD closed DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE verification_request ADD CONSTRAINT FK_20FDDF4EE1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_20FDDF4EE1FA7797 ON verification_request (closed_by_id)');
    }
}
