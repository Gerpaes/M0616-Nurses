<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261013142437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nurse (id INT AUTO_INCREMENT NOT NULL, internal_id VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, license_number VARCHAR(20) NOT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, shift VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_D27E6D43BFDFB4D8 (internal_id), UNIQUE INDEX UNIQ_D27E6D43EC7E7152 (license_number), UNIQUE INDEX UNIQ_D27E6D43F85E0677 (username), UNIQUE INDEX UNIQ_D27E6D43E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nurse');
    }
}
