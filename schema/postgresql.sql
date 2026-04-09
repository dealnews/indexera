CREATE TABLE indexera_users (
    user_id      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email        VARCHAR(255)  NOT NULL,
    password     VARCHAR(255)  NULL,
    display_name VARCHAR(100)  NOT NULL,
    avatar_url   VARCHAR(2048) NULL,
    is_admin     BOOLEAN       NOT NULL DEFAULT FALSE,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NULL     DEFAULT NULL,
    CONSTRAINT uq_users_email UNIQUE (email)
);

CREATE TABLE indexera_user_identities (
    user_identity_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id          BIGINT       NOT NULL,
    provider         VARCHAR(50)  NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_identities_provider UNIQUE (provider, provider_user_id),
    CONSTRAINT fk_user_identities_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE INDEX idx_user_identities_user_id ON indexera_user_identities (user_id);

CREATE TABLE indexera_groups (
    group_id    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    slug        VARCHAR(100) NOT NULL,
    name        VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    created_by  BIGINT       NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NULL     DEFAULT NULL,
    CONSTRAINT uq_groups_slug UNIQUE (slug),
    CONSTRAINT fk_groups_created_by FOREIGN KEY (created_by) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE TABLE indexera_group_members (
    group_member_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    group_id        BIGINT    NOT NULL,
    user_id         BIGINT    NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_group_members UNIQUE (group_id, user_id),
    CONSTRAINT fk_group_members_group_id FOREIGN KEY (group_id) REFERENCES indexera_groups (group_id) ON DELETE CASCADE,
    CONSTRAINT fk_group_members_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE INDEX idx_group_members_group_id ON indexera_group_members (group_id);
CREATE INDEX idx_group_members_user_id ON indexera_group_members (user_id);

CREATE TABLE indexera_pages (
    page_id     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     BIGINT       NOT NULL,
    group_id    BIGINT       NULL     DEFAULT NULL,
    slug        VARCHAR(100) NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    is_public   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NULL     DEFAULT NULL,
    CONSTRAINT uq_pages_user_slug  UNIQUE (user_id, slug),
    CONSTRAINT uq_pages_group_slug UNIQUE (group_id, slug),
    CONSTRAINT fk_pages_user_id  FOREIGN KEY (user_id)  REFERENCES indexera_users  (user_id)  ON DELETE CASCADE,
    CONSTRAINT fk_pages_group_id FOREIGN KEY (group_id) REFERENCES indexera_groups (group_id) ON DELETE SET NULL
);

CREATE INDEX idx_pages_user_id  ON indexera_pages (user_id);
CREATE INDEX idx_pages_group_id ON indexera_pages (group_id);

CREATE TABLE indexera_sections (
    section_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    page_id    BIGINT       NOT NULL,
    title      VARCHAR(255) NOT NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NULL     DEFAULT NULL,
    CONSTRAINT fk_sections_page_id FOREIGN KEY (page_id) REFERENCES indexera_pages (page_id) ON DELETE CASCADE
);

CREATE INDEX idx_sections_page_id ON indexera_sections (page_id, sort_order);

CREATE TABLE indexera_links (
    link_id    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    section_id BIGINT        NOT NULL,
    label      VARCHAR(255)  NOT NULL,
    url        VARCHAR(2048) NOT NULL,
    sort_order INT           NOT NULL DEFAULT 0,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NULL     DEFAULT NULL,
    CONSTRAINT fk_links_section_id FOREIGN KEY (section_id) REFERENCES indexera_sections (section_id) ON DELETE CASCADE
);

CREATE INDEX idx_links_section_id ON indexera_links (section_id, sort_order);

CREATE TABLE indexera_page_subscriptions (
    page_subscription_id BIGINT    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id              BIGINT    NOT NULL,
    page_id              BIGINT    NOT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_page_subscriptions UNIQUE (user_id, page_id),
    CONSTRAINT fk_page_subscriptions_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_page_subscriptions_page_id FOREIGN KEY (page_id) REFERENCES indexera_pages (page_id) ON DELETE CASCADE
);

CREATE INDEX idx_page_subscriptions_user_id ON indexera_page_subscriptions (user_id);
CREATE INDEX idx_page_subscriptions_page_id ON indexera_page_subscriptions (page_id);

CREATE TABLE indexera_sessions (
    session_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    data       TEXT         NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NULL     DEFAULT NULL,
    CONSTRAINT uq_sessions_token UNIQUE (token)
);

CREATE INDEX idx_sessions_created_at ON indexera_sessions (created_at);
CREATE INDEX idx_sessions_updated_at ON indexera_sessions (updated_at);

-- Shared trigger function for updated_at columns
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON indexera_users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_groups_updated_at
    BEFORE UPDATE ON indexera_groups
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_pages_updated_at
    BEFORE UPDATE ON indexera_pages
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_sections_updated_at
    BEFORE UPDATE ON indexera_sections
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_links_updated_at
    BEFORE UPDATE ON indexera_links
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_sessions_updated_at
    BEFORE UPDATE ON indexera_sessions
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE indexera_page_editors (
    page_editor_id BIGINT    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    page_id        BIGINT    NOT NULL,
    user_id        BIGINT    NOT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_page_editors UNIQUE (page_id, user_id),
    CONSTRAINT fk_page_editors_page_id FOREIGN KEY (page_id) REFERENCES indexera_pages (page_id) ON DELETE CASCADE,
    CONSTRAINT fk_page_editors_user_id FOREIGN KEY (user_id) REFERENCES indexera_users (user_id) ON DELETE CASCADE
);

CREATE INDEX idx_page_editors_page_id ON indexera_page_editors (page_id);
CREATE INDEX idx_page_editors_user_id ON indexera_page_editors (user_id);

CREATE TABLE indexera_settings (
    settings_id       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    site_title        VARCHAR(255) NOT NULL DEFAULT 'Indexera',
    nav_heading       VARCHAR(255) NOT NULL DEFAULT 'Indexera',
    public_pages      BOOLEAN       NOT NULL DEFAULT TRUE,
    allow_registration BOOLEAN      NOT NULL DEFAULT TRUE,
    nav_icon_url      VARCHAR(2048) NULL     DEFAULT NULL
);

INSERT INTO indexera_settings (site_title, nav_heading, public_pages, allow_registration) VALUES ('Indexera', 'Indexera', TRUE, TRUE);
