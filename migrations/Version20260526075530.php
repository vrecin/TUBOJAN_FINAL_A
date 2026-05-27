<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526075530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item ADD subtotal NUMERIC(10, 2) NOT NULL, CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD user_id INT NOT NULL, ADD subtotal NUMERIC(10, 2) NOT NULL, ADD tax NUMERIC(10, 2) NOT NULL, ADD shipping NUMERIC(10, 2) NOT NULL, ADD total NUMERIC(10, 2) NOT NULL, ADD shipping_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA76ED395 ON orders (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item DROP subtotal, CHANGE price price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE `orders` DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('DROP INDEX IDX_E52FFDEEA76ED395 ON `orders`');
        $this->addSql('ALTER TABLE `orders` DROP user_id, DROP subtotal, DROP tax, DROP shipping, DROP total, DROP shipping_address');
    }
}
