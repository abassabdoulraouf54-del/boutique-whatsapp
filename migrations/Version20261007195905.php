<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007195905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boutique ADD nom VARCHAR(255) NOT NULL, ADD slug VARCHAR(255) NOT NULL, ADD logo VARCHAR(255) DEFAULT NULL, ADD couleur_principale VARCHAR(20) DEFAULT NULL, ADD numero_whatsapp VARCHAR(20) NOT NULL, ADD frais_livraison INT DEFAULT NULL');
        $this->addSql('ALTER TABLE categorie ADD nom VARCHAR(255) NOT NULL, ADD boutique_id INT NOT NULL');
        $this->addSql('ALTER TABLE categorie ADD CONSTRAINT FK_497DD634AB677BE6 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
        $this->addSql('CREATE INDEX IDX_497DD634AB677BE6 ON categorie (boutique_id)');
        $this->addSql('ALTER TABLE commande ADD nom_client VARCHAR(255) NOT NULL, ADD telephone VARCHAR(20) NOT NULL, ADD adresse LONGTEXT NOT NULL, ADD statut VARCHAR(20) NOT NULL, ADD total INT NOT NULL, ADD created_at DATETIME NOT NULL, ADD boutique_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DAB677BE6 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DAB677BE6 ON commande (boutique_id)');
        $this->addSql('ALTER TABLE ligne_commande ADD quantite INT NOT NULL, ADD prix_unitaire INT NOT NULL, ADD commande_id INT NOT NULL, ADD produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_3170B74B82EA2E54 ON ligne_commande (commande_id)');
        $this->addSql('CREATE INDEX IDX_3170B74BF347EFB ON ligne_commande (produit_id)');
        $this->addSql('ALTER TABLE produit ADD nom VARCHAR(255) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD prix INT NOT NULL, ADD image VARCHAR(255) DEFAULT NULL, ADD stock INT NOT NULL, ADD actif TINYINT NOT NULL, ADD categorie_id INT NOT NULL, ADD boutique_id INT NOT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27AB677BE6 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)');
        $this->addSql('CREATE INDEX IDX_29A5EC27AB677BE6 ON produit (boutique_id)');
        $this->addSql('ALTER TABLE user ADD boutique_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649AB677BE6 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649AB677BE6 ON user (boutique_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boutique DROP nom, DROP slug, DROP logo, DROP couleur_principale, DROP numero_whatsapp, DROP frais_livraison');
        $this->addSql('ALTER TABLE categorie DROP FOREIGN KEY FK_497DD634AB677BE6');
        $this->addSql('DROP INDEX IDX_497DD634AB677BE6 ON categorie');
        $this->addSql('ALTER TABLE categorie DROP nom, DROP boutique_id');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DAB677BE6');
        $this->addSql('DROP INDEX IDX_6EEAA67DAB677BE6 ON commande');
        $this->addSql('ALTER TABLE commande DROP nom_client, DROP telephone, DROP adresse, DROP statut, DROP total, DROP created_at, DROP boutique_id');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BF347EFB');
        $this->addSql('DROP INDEX IDX_3170B74B82EA2E54 ON ligne_commande');
        $this->addSql('DROP INDEX IDX_3170B74BF347EFB ON ligne_commande');
        $this->addSql('ALTER TABLE ligne_commande DROP quantite, DROP prix_unitaire, DROP commande_id, DROP produit_id');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27AB677BE6');
        $this->addSql('DROP INDEX IDX_29A5EC27BCF5E72D ON produit');
        $this->addSql('DROP INDEX IDX_29A5EC27AB677BE6 ON produit');
        $this->addSql('ALTER TABLE produit DROP nom, DROP description, DROP prix, DROP image, DROP stock, DROP actif, DROP categorie_id, DROP boutique_id');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649AB677BE6');
        $this->addSql('DROP INDEX IDX_8D93D649AB677BE6 ON user');
        $this->addSql('ALTER TABLE user DROP boutique_id');
    }
}
