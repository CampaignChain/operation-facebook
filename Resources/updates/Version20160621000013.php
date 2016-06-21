<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160621000013 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaignchain_operation_facebook_status (id INT AUTO_INCREMENT NOT NULL, operation_id INT DEFAULT NULL, message LONGTEXT NOT NULL, postId VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, facebookLocation_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, privacy VARCHAR(100) DEFAULT NULL, INDEX IDX_40D5E8E044AC3583 (operation_id), INDEX IDX_40D5E8E0EC426D9F (facebookLocation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaignchain_operation_facebook_status ADD CONSTRAINT FK_40D5E8E044AC3583 FOREIGN KEY (operation_id) REFERENCES campaignchain_operation (id)');
        $this->addSql('ALTER TABLE campaignchain_operation_facebook_status ADD CONSTRAINT FK_40D5E8E0EC426D9F FOREIGN KEY (facebookLocation_id) REFERENCES campaignchain_location_facebook (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_operation_facebook_status DROP FOREIGN KEY FK_40D5E8E0EC426D9F');
        $this->addSql('DROP TABLE campaignchain_operation_facebook_status');
    }
}
