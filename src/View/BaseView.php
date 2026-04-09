<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

use Dealnews\Indexera\Data\Settings;
use Dealnews\Indexera\Data\User;
use PageMill\MVC\Template\HTMLAbstract;

/**
 * Base HTML view for all Indexera pages.
 *
 * Loads Materialize CSS/JS and renders the shared navbar and footer.
 * Subclasses implement prepareDocument() and generateBody() only.
 *
 * Data properties:
 *   - $current_user  User|null  The authenticated user, or null for guests.
 *
 * @package Dealnews\Indexera\View
 */
abstract class BaseView extends HTMLAbstract {

    /**
     * The authenticated user, or null for guests.
     *
     * @var User|null
     */
    public ?User $current_user = null;

    /**
     * Application-wide settings.
     *
     * @var Settings|null
     */
    public ?Settings $settings = null;

    /**
     * Per-session CSRF token for inclusion in POST forms.
     *
     * @var string
     */
    public string $csrf_token = '';

    /**
     * Returns the configured site title, falling back to the default.
     *
     * @return string
     */
    protected function getSiteTitle(): string {
        return $this->settings?->site_title ?? 'Indexera';
    }

    /**
     * Outputs a hidden CSRF token input for inclusion in POST forms.
     *
     * @return string
     */
    protected function csrfField(): string {
        $token = htmlspecialchars($this->csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Outputs the HTML head, Materialize CSS, and navbar.
     *
     * @return void
     */
    protected function generateHeader(): void {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <style>[x-cloak] { display: none !important; }</style>
    <?php $this->document->generateHead(); ?>
</head>
<body>
<nav class="blue darken-4">
    <div class="nav-wrapper container">
        <a href="/" class="brand-logo">
            <?php
                $icon_src = $this->settings?->nav_icon_url ?? '';
                $icon_src = $icon_src !== ''
                    ? htmlspecialchars($icon_src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    : '/icon-white.svg';
            ?>
            <img src="<?= $icon_src ?>" alt="" width="40" height="40" style="vertical-align: middle; margin-right: 8px;">
            <?= htmlspecialchars($this->settings?->nav_heading ?? 'Indexera', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </a>
        <a href="#" data-target="mobile-sidenav" class="sidenav-trigger">
            <i class="material-icons">menu</i>
        </a>
        <ul class="right hide-on-med-and-down">
            <li><a href="/pages">Directory</a></li>
            <li><a href="/groups">Groups</a></li>
            <?php if ($this->current_user !== null): ?>
            <?php if ($this->current_user->is_admin): ?>
            <li><a href="/admin/users">Users</a></li>
            <li><a href="/admin/settings">Settings</a></li>
            <?php endif; ?>
            <li>
                <a href="/dashboard">
                    <?= htmlspecialchars($this->current_user->display_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </a>
            </li>
            <li><a href="/profile">Profile</a></li>
            <li><a href="/logout">Log out</a></li>
            <?php else: ?>
            <li><a href="/login">Log in</a></li>
            <?php if ($this->settings?->allow_registration ?? true): ?>
            <li><a href="/register">Register</a></li>
            <?php endif; ?>
            <?php endif; // current_user ?>
        </ul>
    </div>
</nav>

<ul class="sidenav" id="mobile-sidenav">
    <li><a href="/pages">Directory</a></li>
    <?php if ($this->current_user !== null): ?>
    <?php if ($this->current_user->is_admin): ?>
    <li><a href="/admin/users">Users</a></li>
    <li><a href="/admin/settings">Settings</a></li>
    <li><div class="divider"></div></li>
    <?php endif; ?>
    <li>
        <a href="/dashboard">
            <?= htmlspecialchars($this->current_user->display_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </a>
    </li>
    <li><a href="/profile">Profile</a></li>
    <li><a href="/logout">Log out</a></li>
    <?php else: ?>
    <li><a href="/login">Log in</a></li>
    <?php if ($this->settings?->allow_registration ?? true): ?>
    <li><a href="/register">Register</a></li>
    <?php endif; ?>
    <?php endif; // current_user ?>
</ul>
        <?php
    }

    /**
     * Outputs the Materialize JS and closing body and html tags.
     *
     * @return void
     */
    protected function generateFooter(): void {
        ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        M.Sidenav.init(document.querySelectorAll('.sidenav'));
    });
</script>
</body>
</html>
        <?php
    }
}
