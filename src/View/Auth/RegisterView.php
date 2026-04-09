<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View\Auth;

use Dealnews\Indexera\View\BaseView;

/**
 * Renders the registration form.
 *
 * Data properties:
 *   - $error        string  Optional error message displayed above the form.
 *   - $oauth_github     bool    Whether GitHub OAuth is configured.
 *   - $oauth_google     bool    Whether Google OAuth is configured.
 *   - $oauth_microsoft  bool    Whether Microsoft OAuth is configured.
 *
 * @package Dealnews\Indexera\View\Auth
 */
class RegisterView extends BaseView {

    /**
     * Error message to display, or empty string for none.
     *
     * @var string
     */
    public string $error = '';

    /**
     * Whether Microsoft OAuth is configured.
     *
     * @var bool
     */
    public bool $oauth_microsoft = false;

    /**
     * Whether GitHub OAuth is configured.
     *
     * @var bool
     */
    public bool $oauth_github = false;

    /**
     * Whether Google OAuth is configured.
     *
     * @var bool
     */
    public bool $oauth_google = false;

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Register — Indexera';
    }

    /**
     * Outputs the registration form.
     *
     * @return void
     */
    protected function generateBody(): void {
        $error = htmlspecialchars($this->error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12 m6 offset-m3">
                    <h4 class="center-align">Create an account</h4>
                    <?php if ($error !== ''): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-content">
                            <form method="post" action="/register">
                                <?= $this->csrfField() ?>
                                <div class="input-field">
                                    <input type="email" id="email" name="email" required>
                                    <label for="email">Email</label>
                                </div>
                                <div class="input-field">
                                    <input type="text" id="display_name" name="display_name"
                                           maxlength="100" required
                                           pattern="[a-z0-9_\-]+"
                                           title="Lowercase letters, numbers, hyphens, and underscores only">
                                    <label for="display_name">Username</label>
                                    <span class="helper-text">
                                        Lowercase letters, numbers, hyphens, and underscores only
                                    </span>
                                </div>
                                <div class="input-field">
                                    <input type="password" id="password" name="password" required>
                                    <label for="password">Password</label>
                                </div>
                                <div class="input-field">
                                    <input type="password" id="password_confirm"
                                           name="password_confirm" required>
                                    <label for="password_confirm">Confirm password</label>
                                </div>
                                <div class="center-align">
                                    <button class="btn waves-effect waves-light" type="submit">
                                        Create account
                                        <i class="material-icons right">person_add</i>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php if ($this->oauth_github || $this->oauth_google || $this->oauth_microsoft): ?>
                        <div class="card-action" style="display: flex; gap: 1rem;">
                            <?php if ($this->oauth_microsoft): ?>
                            <a href="/auth/microsoft">
                                <i class="material-icons left">business</i>Register with Microsoft
                            </a>
                            <?php endif; ?>
                            <?php if ($this->oauth_github): ?>
                            <a href="/auth/github">
                                <i class="material-icons left">code</i>Register with GitHub
                            </a>
                            <?php endif; ?>
                            <?php if ($this->oauth_google): ?>
                            <a href="/auth/google">
                                <i class="material-icons left">account_circle</i>Register with Google
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="center-align">
                        Already have an account? <a href="/login">Log in</a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}
