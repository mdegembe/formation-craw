<?php

declare(strict_types=1);

use Craw\Core\SchemaHelper;

return static function (SchemaHelper $schema): void {
    $id = $schema->primaryKey();
    $fk = $schema->foreignKeyType();
    $dt = $schema->datetime();
    $date = $schema->date();
    $bool = $schema->boolean();
    $text = $schema->text();
    $json = $schema->json();

    // ---------------------------------------------------------------- settings
    $schema->createTable('settings', "
        id {$id},
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value {$text} NULL,
        setting_type VARCHAR(16) NOT NULL DEFAULT 'string',
        updated_at {$dt} NULL
    ");

    // ------------------------------------------------------------ admin_users
    $schema->createTable('admin_users', "
        id {$id},
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        display_name VARCHAR(120) NOT NULL,
        role {$schema->enum('role', ['admin', 'gestionnaire'], 'gestionnaire')},
        is_active {$bool} NOT NULL DEFAULT 1,
        failed_attempts INTEGER NOT NULL DEFAULT 0,
        locked_until {$dt} NULL,
        last_login_at {$dt} NULL,
        created_at {$dt} NOT NULL
    ");

    // --------------------------------------------------------------- training
    $schema->createTable('training', "
        id {$id},
        slug VARCHAR(190) NOT NULL UNIQUE,
        title VARCHAR(190) NOT NULL,
        subject VARCHAR(190) NULL,
        subject_pending {$bool} NOT NULL DEFAULT 1,
        summary {$text} NULL,
        description {$text} NULL,
        practical_info {$text} NULL,
        starts_on {$date} NOT NULL,
        ends_on {$date} NOT NULL,
        location_name VARCHAR(190) NOT NULL,
        location_address VARCHAR(255) NOT NULL,
        location_latitude VARCHAR(20) NULL,
        location_longitude VARCHAR(20) NULL,
        price_cents INTEGER NOT NULL DEFAULT 0,
        currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
        capacity INTEGER NOT NULL DEFAULT 40,
        registration_deadline {$date} NULL,
        status {$schema->enum('status', ['draft', 'open', 'closed', 'archived'], 'open')},
        meta_title VARCHAR(190) NULL,
        meta_description VARCHAR(255) NULL,
        created_at {$dt} NOT NULL,
        updated_at {$dt} NULL
    ");

    // ---------------------------------------------------------- training_days
    $schema->createTable('training_days', "
        id {$id},
        training_id {$fk} NOT NULL,
        day_date {$date} NOT NULL,
        slot {$schema->enum('slot', ['am', 'pm', 'full'], 'am')},
        label VARCHAR(190) NULL,
        topic VARCHAR(190) NULL,
        speaker VARCHAR(190) NULL,
        starts_at VARCHAR(5) NULL,
        ends_at VARCHAR(5) NULL,
        position INTEGER NOT NULL DEFAULT 0,
        counts_for_attendance {$bool} NOT NULL DEFAULT 1,
        FOREIGN KEY (training_id) REFERENCES training(id) ON DELETE CASCADE
    ");
    $schema->createIndex('idx_training_days_training', 'training_days', 'training_id, position');

    // ---------------------------------------------------------- registrations
    $schema->createTable('registrations', "
        id {$id},
        training_id {$fk} NOT NULL,
        reference VARCHAR(32) NOT NULL UNIQUE,
        token VARCHAR(64) NOT NULL UNIQUE,
        civility VARCHAR(30) NULL,
        last_name VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(30) NULL,
        birth_date {$date} NULL,
        organisation VARCHAR(150) NULL,
        job_title VARCHAR(150) NULL,
        street VARCHAR(190) NULL,
        postal_code VARCHAR(20) NULL,
        city VARCHAR(120) NULL,
        country VARCHAR(80) NULL DEFAULT 'Belgique',
        vat_number VARCHAR(20) NULL,
        needs_invoice {$bool} NOT NULL DEFAULT 0,
        diet {$schema->enum('diet', ['standard', 'vegetarien', 'vegan', 'sans_porc', 'halal', 'casher', 'autre'], 'standard')},
        diet_comment VARCHAR(500) NULL,
        has_allergies {$bool} NOT NULL DEFAULT 0,
        allergies_comment VARCHAR(500) NULL,
        logistics_comment VARCHAR(500) NULL,
        status {$schema->enum('status', ['draft', 'pending_payment', 'confirmed', 'waitlist', 'cancelled'], 'draft')},
        amount_due_cents INTEGER NOT NULL DEFAULT 0,
        structured_communication VARCHAR(30) NULL,
        consent_gdpr_at {$dt} NULL,
        consent_terms_at {$dt} NULL,
        consent_health_at {$dt} NULL,
        confirmed_at {$dt} NULL,
        cancelled_at {$dt} NULL,
        ip_hash VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        created_at {$dt} NOT NULL,
        updated_at {$dt} NULL,
        FOREIGN KEY (training_id) REFERENCES training(id) ON DELETE CASCADE
    ");
    $schema->createIndex('idx_registrations_email', 'registrations', 'training_id, email');
    $schema->createIndex('idx_registrations_status', 'registrations', 'status');
    $schema->createIndex('idx_registrations_comm', 'registrations', 'structured_communication');

    // --------------------------------------------------------------- payments
    $schema->createTable('payments', "
        id {$id},
        registration_id {$fk} NOT NULL,
        status {$schema->enum('status', ['unpaid', 'reminded', 'partial', 'paid', 'refunded', 'waived', 'cancelled'], 'unpaid')},
        method {$schema->enum('method', ['transfer', 'onsite', 'exempt'], 'transfer')},
        amount_cents INTEGER NOT NULL DEFAULT 0,
        received_cents INTEGER NULL,
        value_date {$date} NULL,
        due_date {$date} NULL,
        note VARCHAR(500) NULL,
        reminders_sent INTEGER NOT NULL DEFAULT 0,
        last_reminder_at {$dt} NULL,
        confirmed_by {$fk} NULL,
        confirmed_at {$dt} NULL,
        created_at {$dt} NOT NULL,
        updated_at {$dt} NULL,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
        FOREIGN KEY (confirmed_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ");
    $schema->createIndex('idx_payments_registration', 'payments', 'registration_id', true);
    $schema->createIndex('idx_payments_status', 'payments', 'status');

    // --------------------------------------------------------------- absences
    // On ne stocke QUE les exceptions : tout participant est presume present.
    $schema->createTable('absences', "
        id {$id},
        registration_id {$fk} NOT NULL,
        training_day_id {$fk} NOT NULL,
        reason VARCHAR(255) NULL,
        is_justified {$bool} NOT NULL DEFAULT 0,
        recorded_by {$fk} NULL,
        recorded_at {$dt} NOT NULL,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
        FOREIGN KEY (training_day_id) REFERENCES training_days(id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ");
    $schema->createIndex('idx_absences_unique', 'absences', 'registration_id, training_day_id', true);
    $schema->createIndex('idx_absences_day', 'absences', 'training_day_id');

    // ------------------------------------------------------ diploma_templates
    $schema->createTable('diploma_templates', "
        id {$id},
        name VARCHAR(150) NOT NULL,
        mode {$schema->enum('mode', ['background', 'html'], 'background')},
        background_path VARCHAR(255) NULL,
        html_body {$schema->longText()} NULL,
        field_layout {$json} NULL,
        orientation {$schema->enum('orientation', ['landscape', 'portrait'], 'landscape')},
        is_active {$bool} NOT NULL DEFAULT 0,
        uploaded_by {$fk} NULL,
        created_at {$dt} NOT NULL,
        FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ");

    // --------------------------------------------------------------- diplomas
    $schema->createTable('diplomas', "
        id {$id},
        registration_id {$fk} NOT NULL,
        template_id {$fk} NULL,
        number VARCHAR(40) NOT NULL UNIQUE,
        version INTEGER NOT NULL DEFAULT 1,
        attendance_rate VARCHAR(10) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        verification_code VARCHAR(40) NOT NULL UNIQUE,
        generated_by {$fk} NULL,
        generated_at {$dt} NOT NULL,
        sent_at {$dt} NULL,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
        FOREIGN KEY (template_id) REFERENCES diploma_templates(id) ON DELETE SET NULL,
        FOREIGN KEY (generated_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ");
    $schema->createIndex('idx_diplomas_registration', 'diplomas', 'registration_id');

    // ------------------------------------------------------------------- news
    $schema->createTable('news', "
        id {$id},
        slug VARCHAR(190) NOT NULL UNIQUE,
        title VARCHAR(190) NOT NULL,
        excerpt VARCHAR(500) NULL,
        body {$schema->longText()} NULL,
        cover_path VARCHAR(255) NULL,
        cover_alt VARCHAR(190) NULL,
        status {$schema->enum('status', ['draft', 'published'], 'draft')},
        published_at {$dt} NULL,
        author_id {$fk} NULL,
        meta_title VARCHAR(190) NULL,
        meta_description VARCHAR(255) NULL,
        created_at {$dt} NOT NULL,
        updated_at {$dt} NULL,
        FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE SET NULL
    ");
    $schema->createIndex('idx_news_published', 'news', 'status, published_at');

    // ------------------------------------------------------------ email_queue
    $schema->createTable('email_queue', "
        id {$id},
        registration_id {$fk} NULL,
        to_email VARCHAR(190) NOT NULL,
        to_name VARCHAR(190) NULL,
        template VARCHAR(80) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        payload {$json} NULL,
        attachments {$json} NULL,
        status {$schema->enum('status', ['pending', 'sending', 'sent', 'failed'], 'pending')},
        attempts INTEGER NOT NULL DEFAULT 0,
        last_error VARCHAR(500) NULL,
        scheduled_at {$dt} NOT NULL,
        sent_at {$dt} NULL,
        created_at {$dt} NOT NULL,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
    ");
    $schema->createIndex('idx_email_queue_pending', 'email_queue', 'status, scheduled_at');

    // -------------------------------------------------------------- email_log
    $schema->createTable('email_log', "
        id {$id},
        registration_id {$fk} NULL,
        to_email VARCHAR(190) NOT NULL,
        template VARCHAR(80) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error VARCHAR(500) NULL,
        sent_at {$dt} NOT NULL,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE SET NULL
    ");
    $schema->createIndex('idx_email_log_registration', 'email_log', 'registration_id');

    // -------------------------------------------------------------- audit_log
    $schema->createTable('audit_log', "
        id {$id},
        admin_user_id {$fk} NULL,
        action VARCHAR(80) NOT NULL,
        entity VARCHAR(80) NOT NULL,
        entity_id {$fk} NULL,
        details {$json} NULL,
        ip_hash VARCHAR(64) NULL,
        created_at {$dt} NOT NULL,
        FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
    ");
    $schema->createIndex('idx_audit_entity', 'audit_log', 'entity, entity_id');

    // ------------------------------------------------------- contact_messages
    $schema->createTable('contact_messages', "
        id {$id},
        name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL,
        subject VARCHAR(190) NOT NULL,
        message {$text} NOT NULL,
        ip_hash VARCHAR(64) NULL,
        handled_at {$dt} NULL,
        created_at {$dt} NOT NULL
    ");

    // ----------------------------------------------------------- rate_limits
    $schema->createTable('rate_limits', "
        id {$id},
        bucket VARCHAR(64) NOT NULL,
        expires_at {$dt} NOT NULL,
        created_at {$dt} NOT NULL
    ");
    $schema->createIndex('idx_rate_limits_bucket', 'rate_limits', 'bucket, created_at');
};
