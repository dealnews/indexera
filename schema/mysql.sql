CREATE TABLE `indexera_users` (
    `user_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`        VARCHAR(255)    NOT NULL,
    `password`     VARCHAR(255)    NULL,
    `display_name` VARCHAR(100)    NOT NULL,
    `avatar_url`   VARCHAR(2048)   NULL,
    `is_admin`     TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_user_identities` (
    `user_identity_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          BIGINT UNSIGNED NOT NULL,
    `provider`         VARCHAR(50)     NOT NULL,
    `provider_user_id` VARCHAR(255)    NOT NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_identity_id`),
    UNIQUE KEY `uq_user_identities_provider` (`provider`, `provider_user_id`),
    KEY `idx_user_identities_user_id` (`user_id`),
    CONSTRAINT `fk_user_identities_user_id` FOREIGN KEY (`user_id`) REFERENCES `indexera_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_groups` (
    `group_id`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(100)    NOT NULL,
    `name`        VARCHAR(255)    NOT NULL,
    `description` TEXT            NULL,
    `created_by`  BIGINT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`group_id`),
    UNIQUE KEY `uq_groups_slug` (`slug`),
    CONSTRAINT `fk_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `indexera_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_group_members` (
    `group_member_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id`        BIGINT UNSIGNED NOT NULL,
    `user_id`         BIGINT UNSIGNED NOT NULL,
    `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`group_member_id`),
    UNIQUE KEY `uq_group_members` (`group_id`, `user_id`),
    KEY `idx_group_members_group_id` (`group_id`),
    KEY `idx_group_members_user_id` (`user_id`),
    CONSTRAINT `fk_group_members_group_id` FOREIGN KEY (`group_id`) REFERENCES `indexera_groups` (`group_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_group_members_user_id` FOREIGN KEY (`user_id`) REFERENCES `indexera_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_pages` (
    `page_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `group_id`    BIGINT UNSIGNED NULL     DEFAULT NULL,
    `slug`        VARCHAR(100)    NOT NULL,
    `title`       VARCHAR(255)    NOT NULL,
    `description` TEXT            NULL,
    `is_public`   TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`page_id`),
    UNIQUE KEY `uq_pages_user_slug`  (`user_id`, `slug`),
    UNIQUE KEY `uq_pages_group_slug` (`group_id`, `slug`),
    KEY `idx_pages_user_id`  (`user_id`),
    KEY `idx_pages_group_id` (`group_id`),
    CONSTRAINT `fk_pages_user_id`  FOREIGN KEY (`user_id`)  REFERENCES `indexera_users`  (`user_id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_pages_group_id` FOREIGN KEY (`group_id`) REFERENCES `indexera_groups` (`group_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_sections` (
    `section_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id`    BIGINT UNSIGNED NOT NULL,
    `title`      VARCHAR(255)    NOT NULL,
    `sort_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`section_id`),
    KEY `idx_sections_page_id` (`page_id`, `sort_order`),
    CONSTRAINT `fk_sections_page_id` FOREIGN KEY (`page_id`) REFERENCES `indexera_pages` (`page_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_links` (
    `link_id`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_id` BIGINT UNSIGNED NOT NULL,
    `label`      VARCHAR(255)    NOT NULL,
    `url`        VARCHAR(2048)   NOT NULL,
    `sort_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`link_id`),
    KEY `idx_links_section_id` (`section_id`, `sort_order`),
    CONSTRAINT `fk_links_section_id` FOREIGN KEY (`section_id`) REFERENCES `indexera_sections` (`section_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_page_subscriptions` (
    `page_subscription_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`              BIGINT UNSIGNED NOT NULL,
    `page_id`              BIGINT UNSIGNED NOT NULL,
    `created_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`page_subscription_id`),
    UNIQUE KEY `uq_page_subscriptions` (`user_id`, `page_id`),
    KEY `idx_page_subscriptions_user_id` (`user_id`),
    KEY `idx_page_subscriptions_page_id` (`page_id`),
    CONSTRAINT `fk_page_subscriptions_user_id` FOREIGN KEY (`user_id`) REFERENCES `indexera_users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_page_subscriptions_page_id` FOREIGN KEY (`page_id`) REFERENCES `indexera_pages` (`page_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_sessions` (
    `session_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token`      VARCHAR(255)    NOT NULL,
    `data`       MEDIUMTEXT      NULL,
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`),
    UNIQUE KEY `uq_sessions_token` (`token`),
    KEY `idx_sessions_created_at` (`created_at`),
    KEY `idx_sessions_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_page_editors` (
    `page_editor_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id`        BIGINT UNSIGNED NOT NULL,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`page_editor_id`),
    UNIQUE KEY `uq_page_editors` (`page_id`, `user_id`),
    KEY `idx_page_editors_page_id` (`page_id`),
    KEY `idx_page_editors_user_id` (`user_id`),
    CONSTRAINT `fk_page_editors_page_id` FOREIGN KEY (`page_id`) REFERENCES `indexera_pages` (`page_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_page_editors_user_id` FOREIGN KEY (`user_id`) REFERENCES `indexera_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `indexera_settings` (
    `settings_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `site_title`         VARCHAR(255)    NOT NULL DEFAULT 'Indexera',
    `nav_heading`        VARCHAR(255)    NOT NULL DEFAULT 'Indexera',
    `public_pages`       TINYINT(1)      NOT NULL DEFAULT 1,
    `allow_registration` TINYINT(1)      NOT NULL DEFAULT 1,
    `nav_icon_url`       VARCHAR(2048)   NULL     DEFAULT NULL,
    PRIMARY KEY (`settings_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `indexera_settings` (`site_title`, `nav_heading`, `public_pages`, `allow_registration`) VALUES ('Indexera', 'Indexera', 1, 1);
