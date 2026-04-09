<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View\Admin;

use Dealnews\Indexera\View\BaseView;

/**
 * Renders the admin settings form.
 *
 * Uses $this->settings (inherited from BaseView) for current values.
 *
 * Data properties:
 *   - $error  string  Optional validation error message.
 *
 * @package Dealnews\Indexera\View\Admin
 */
class SettingsView extends BaseView {

    /**
     * Validation error message, or empty string for none.
     *
     * @var string
     */
    public string $error = '';

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Settings — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the settings form.
     *
     * @return void
     */
    protected function generateBody(): void {
        $site_title   = htmlspecialchars(
            $this->settings?->site_title ?? 'Indexera',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $nav_heading  = htmlspecialchars(
            $this->settings?->nav_heading ?? 'Indexera',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $public_pages       = $this->settings?->public_pages ?? true;
        $allow_registration = $this->settings?->allow_registration ?? true;
        $nav_icon_url       = htmlspecialchars(
            $this->settings?->nav_icon_url ?? '',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $error = htmlspecialchars($this->error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12 m6 offset-m3">
                    <h4 style="margin-top: 2rem;">Settings</h4>
                    <?php if ($error !== ''): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-content">
                            <form method="post" action="/admin/settings">
                                <?= $this->csrfField() ?>
                                <div class="input-field">
                                    <input type="text"
                                           id="site_title"
                                           name="site_title"
                                           value="<?= $site_title ?>"
                                           required>
                                    <label for="site_title" class="active">Site title</label>
                                </div>
                                <div class="input-field">
                                    <input type="text"
                                           id="nav_heading"
                                           name="nav_heading"
                                           value="<?= $nav_heading ?>"
                                           required>
                                    <label for="nav_heading" class="active">Nav heading</label>
                                </div>
                                <div class="input-field">
                                    <input type="url"
                                           id="nav_icon_url"
                                           name="nav_icon_url"
                                           value="<?= $nav_icon_url ?>">
                                    <label for="nav_icon_url" class="<?= $nav_icon_url !== '' ? 'active' : '' ?>">
                                        Nav icon URL
                                    </label>
                                    <span class="helper-text">
                                        Leave blank to use the default icon.
                                        For non-SVG images, use a square image at least 40&times;40px
                                        (80&times;80px recommended for high-DPI screens).
                                    </span>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <label>
                                        <input type="checkbox"
                                               name="public_pages"
                                               value="1"
                                               <?= $public_pages ? 'checked' : '' ?>>
                                        <span>Allow guests to view pages</span>
                                    </label>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <label>
                                        <input type="checkbox"
                                               name="allow_registration"
                                               value="1"
                                               <?= $allow_registration ? 'checked' : '' ?>>
                                        <span>Allow new user registration</span>
                                    </label>
                                </div>
                                <button type="submit"
                                        class="btn waves-effect waves-light">
                                    <i class="material-icons left">save</i>Save
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
