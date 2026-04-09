PRAGMA foreign_keys = ON;

CREATE TABLE indexera_users (
    user_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    email        TEXT    NOT NULL,
    password     TEXT    NULL,
    display_name TEXT    NOT NULL,
    avatar_url   TEXT    NULL,
    is_admin     INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TEXT    NULL     DEFAULT NULL,
    CONSTRAINT uq_users_email UNIQUE (email)
);

CREATE TRIGGER trg_users_updated_at
    AFTER UPDATE ON indexera_users
    FOR EACH ROW
    WHEN NEW.updated_at IS OLD.updated_at
BEGIN
    UPDATE indexera_users SET updated_at = CURRENT_TIMESTAMP WHERE user_id = NEW.user_id;
END;

CREATE TABLE indexera_user_identities (
    user_identity_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL,
    provider         TEXT    NOT NULL,
    provider_user_id TEXT    NOT NULL,
    created_at       TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_identities_provider UNIQUE (provider, provider_user_id),
    CONSTRAINT fk_user_identities_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE INDEX idx_user_identities_user_id ON indexera_user_identities (user_id);

CREATE TABLE indexera_groups (
    group_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT    NOT NULL,
    name        TEXT    NOT NULL,
    description TEXT    NULL,
    created_by  INTEGER NOT NULL,
    created_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TEXT    NULL     DEFAULT NULL,
    CONSTRAINT uq_groups_slug UNIQUE (slug),
    CONSTRAINT fk_groups_created_by FOREIGN KEY (created_by) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE TRIGGER trg_groups_updated_at
    AFTER UPDATE ON indexera_groups
    FOR EACH ROW
    WHEN NEW.updated_at IS OLD.updated_at
BEGIN
    UPDATE indexera_groups SET updated_at = CURRENT_TIMESTAMP WHERE group_id = NEW.group_id;
END;

CREATE TABLE indexera_group_members (
    group_member_id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id        INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    created_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_group_members UNIQUE (group_id, user_id),
    CONSTRAINT fk_group_members_group_id FOREIGN KEY (group_id) REFERENCES indexera_groups (group_id) ON DELETE CASCADE,
    CONSTRAINT fk_group_members_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE INDEX idx_group_members_group_id ON indexera_group_members (group_id);
CREATE INDEX idx_group_members_user_id ON indexera_group_members (user_id);

CREATE TABLE indexera_pages (
    page_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL,
    group_id    INTEGER NULL     DEFAULT NULL,
    slug        TEXT    NOT NULL,
    title       TEXT    NOT NULL,
    description TEXT    NULL,
    is_public   INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TEXT    NULL     DEFAULT NULL,
    CONSTRAINT uq_pages_user_slug  UNIQUE (user_id, slug),
    CONSTRAINT uq_pages_group_slug UNIQUE (group_id, slug),
    CONSTRAINT fk_pages_user_id  FOREIGN KEY (user_id)  REFERENCES indexera_users  (user_id)  ON DELETE CASCADE,
    CONSTRAINT fk_pages_group_id FOREIGN KEY (group_id) REFERENCES indexera_groups (group_id) ON DELETE SET NULL
);

CREATE INDEX idx_pages_user_id  ON indexera_pages (user_id);
CREATE INDEX idx_pages_group_id ON indexera_pages (group_id);

CREATE TRIGGER trg_pages_updated_at
    AFTER UPDATE ON indexera_pages
    FOR EACH ROW
    WHEN NEW.updated_at IS OLD.updated_at
BEGIN
    UPDATE indexera_pages SET updated_at = CURRENT_TIMESTAMP WHERE page_id = NEW.page_id;
END;

CREATE TABLE indexera_sections (
    section_id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id    INTEGER NOT NULL,
    title      TEXT    NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT    NULL     DEFAULT NULL,
    CONSTRAINT fk_sections_page_id FOREIGN KEY (page_id) REFERENCES indexera_pages (page_id) ON DELETE CASCADE
);

CREATE INDEX idx_sections_page_id ON indexera_sections (page_id, sort_order);

CREATE TRIGGER trg_sections_updated_at
    AFTER UPDATE ON indexera_sections
    FOR EACH ROW
    WHEN NEW.updated_at IS OLD.updated_at
BEGIN
    UPDATE indexera_sections SET updated_at = CURRENT_TIMESTAMP WHERE section_id = NEW.section_id;
END;

CREATE TABLE indexera_links (
    link_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    section_id INTEGER NOT NULL,
    label      TEXT    NOT NULL,
    url        TEXT    NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT    NULL     DEFAULT NULL,
    CONSTRAINT fk_links_section_id FOREIGN KEY (section_id) REFERENCES indexera_sections (section_id) ON DELETE CASCADE
);

CREATE INDEX idx_links_section_id ON indexera_links (section_id, sort_order);

CREATE TRIGGER trg_links_updated_at
    AFTER UPDATE ON indexera_links
    FOR EACH ROW
    WHEN NEW.updated_at IS OLD.updated_at
BEGIN
    UPDATE indexera_links SET updated_at = CURRENT_TIMESTAMP WHERE link_id = NEW.link_id;
END;

CREATE TABLE indexera_page_subscriptions (
    page_subscription_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id              INTEGER NOT NULL,
    page_id              INTEGER NOT NULL,
    created_at           TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_page_subscriptions UNIQUE (user_id, page_id),
    CONSTRAINT fk_page_subscriptions_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_page_subscriptions_page_id FOREIGN KEY (page_id) REFERENCES indexera_pages (page_id) ON DELETE CASCADE
);

CREATE INDEX idx_page_subscriptions_user_id ON indexera_page_subscriptions (user_id);
CREATE INDEX idx_page_subscriptions_page_id ON indexera_page_subscriptions (page_id);

CREATE TABLE indexera_sessions (
    session_id INTEGER PRIMARY KEY AUTOINCREMENT,
    token      TEXT NOT NULL,
    data       TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NULL     DEFAULT NULL,
    CONSTRAINT uq_sessions_token UNIQUE (token)
);

CREATE INDEX idx_sessions_created_at ON indexera_sessions (created_at);
CREATE INDEX idx_sessions_updated_at ON indexera_sessions (updated_at);

CREATE TRIGGER trg_sessions_updated_at
    AFTER UPDATE ON indexera_sessions
    FOR EACH ROW
    WHEN NEW.updated_at IS OLD.updated_at
BEGIN
    UPDATE indexera_sessions SET updated_at = CURRENT_TIMESTAMP WHERE session_id = NEW.session_id;
END;

CREATE TABLE indexera_page_editors (
    page_editor_id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id        INTEGER NOT NULL,
    user_id        INTEGER NOT NULL,
    created_at     TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_page_editors UNIQUE (page_id, user_id),
    CONSTRAINT fk_page_editors_page_id FOREIGN KEY (page_id) REFERENCES indexera_pages (page_id) ON DELETE CASCADE,
    CONSTRAINT fk_page_editors_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE INDEX idx_page_editors_page_id ON indexera_page_editors (page_id);
CREATE INDEX idx_page_editors_user_id ON indexera_page_editors (user_id);

CREATE TABLE indexera_settings (
    settings_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    site_title        TEXT    NOT NULL DEFAULT 'Indexera',
    nav_heading       TEXT    NOT NULL DEFAULT 'Indexera',
    public_pages      INTEGER NOT NULL DEFAULT 1,
    allow_registration INTEGER NOT NULL DEFAULT 1,
    nav_icon_url      TEXT    NULL     DEFAULT NULL
);

INSERT INTO indexera_settings (site_title, nav_heading, public_pages, allow_registration) VALUES ('Indexera', 'Indexera', 1, 1);
