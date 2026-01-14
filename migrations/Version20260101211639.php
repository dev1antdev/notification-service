<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101211639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create project tables';
    }

    public function up(Schema $schema): void
    {
        # Create idempotency_keys table
        $this->addSql('
            CREATE TABLE idempotency_keys (
                key varchar(128) PRIMARY KEY,
                scope varchar(64) NOT NULL,
                response jsonb NOT NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                expires_at timestamptz NULL
            );
        ');
        $this->addSql('CREATE INDEX idx_idempotency_keys_scope ON idempotency_keys(scope);');
        $this->addSql('CREATE INDEX idx_idempotency_keys_expires_at ON idempotency_keys(expires_at)');

        # Create outbox_events table
        $this->addSql('
            CREATE TABLE outbox_events (
                id bigserial PRIMARY KEY,
                event_id varchar(64) NOT NULL UNIQUE,
                event_type varchar(128) NOT NULL,
                payload jsonb NOT NULL,
                occurred_at timestamptz NOT NULL,
                available_at timestamptz NOT NULL DEFAULT now(),
                attempts int NOT NULL DEFAULT 0,
                locked_at timestamptz NULL,
                lock_token varchar(64) NULL,
                last_error text NULL,
                published_at timestamptz NULL
            )
        ');
        $this->addSql('CREATE INDEX idx_outbox_events_available ON outbox_events(published_at, available_at)');
        $this->addSql('CREATE INDEX idx_outbox_locked_at ON outbox_events(locked_at)');

        # Notifications table
        $this->addSql('
            CREATE TABLE notifications (
                id varchar(64) PRIMARY KEY,
                correlation_id varchar(64) NOT NULL,
                idempotency_key varchar(128) NULL,
                recipient jsonb NOT NULL,
                channels jsonb NOT NULL,
                content jsonb NOT NULL,
                schedule jsonb NULL,
                tags jsonb NULL,
                status varchar(32) NOT NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL
            )
        ');
        $this->addSql('CREATE INDEX idx_notifications_created_at ON notifications(created_at)');
        $this->addSql('CREATE INDEX idx_notifications_status ON notifications(status)');
        $this->addSql('CREATE INDEX idx_notifications_idempotency_key ON notifications(idempotency_key)');

        # Deliveries tables
        $this->addSql('
            CREATE TABLE deliveries (
                id varchar(64) PRIMARY KEY,
                correlation_id varchar(64) NOT NULL,
                notification_id varchar(64) NOT NULL,
                channel varchar(64) NOT NULL,
                provider varchar(64) NOT NULL,
                address_type varchar(64) NOT NULL,
                address jsonb NOT NULL,
                content_type varchar(64) NOT NULL,
                content jsonb NOT NULL,
                status varchar(32) NOT NULL,
                attempt_count int NOT NULL DEFAULT 0,
                next_retry_at timestamptz NULL,
                dead_lettered_at timestamptz NULL,
                provider_message_id varchar(128) NULL,
                last_error jsonb NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL,
                version int NOT NULL DEFAULT 0,
                CONSTRAINT fk_deliveries_notifications FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE RESTRICT ON UPDATE NO ACTION
            )
        ');
        $this->addSql('CREATE INDEX idx_deliveries_status ON deliveries(status)');
        $this->addSql('CREATE INDEX idx_deliveries_next_retry ON deliveries(next_retry_at)');
        $this->addSql('CREATE INDEX idx_deliveries_provider ON deliveries(provider)');
        $this->addSql('
            CREATE TABLE delivery_attempts (
                id varchar(64) PRIMARY KEY,
                delivery_id varchar(128) NOT NULL,
                attempt_number int NOT NULL,
                started_at timestamptz NOT NULL,
                finished_at timestamptz NULL,
                status varchar(16) NOT NULL,
                provider_message_id varchar(128) NULL,
                error jsonb NULL,
                CONSTRAINT fk_delivery_attempts_deliveries FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX uniq_deliveries_attempts_number ON delivery_attempts(delivery_id, attempt_number)');

        # Create templates tables
        $this->addSql('
            CREATE TABLE templates (
                id bigserial PRIMARY KEY,
                template_key varchar(128) NOT NULL UNIQUE,
                created_at timestamptz NOT NULL DEFAULT NOW()
            )
        ');

        $this->addSql('
            CREATE TABLE template_versions (
                id bigserial PRIMARY KEY,
                template_id bigint NOT NULL REFERENCES templates(id) ON DELETE CASCADE,
                version int NOT NULL,
                locale varchar(16) NOT NULL,
                status varchar(16) NOT NULL,
                subject text NULL,
                text text NULL,
                html text NULL,
                push_title text NULL,
                push_body text NULL,
                push_data jsonb NULL,
                created_at timestamptz NOT NULL DEFAULT now()
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX uniq_template_version_locale ON template_versions(template_id, version, locale)');
        $this->addSql('CREATE INDEX idx_template_versions_status ON template_versions(status)');
    }

    public function down(Schema $schema): void
    {
    }
}
