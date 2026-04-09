<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View\Auth;

use Dealnews\Indexera\View\BaseView;

/**
 * Renders the login form.
 *
 * Data properties:
 *   - $error        string  Optional error message displayed above the form.
 *   - $next         string  Optional URL to redirect to after successful login.
 *   - $allow_registration bool    Whether new user registration is open.
 *   - $oauth_github       bool    Whether GitHub OAuth is configured.
 *   - $oauth_google       bool    Whether Google OAuth is configured.
 *   - $oauth_microsoft    bool    Whether Microsoft OAuth is configured.
 *
 * @package Dealnews\Indexera\View\Auth
 */
class LoginView extends BaseView {

    /**
     * Error message to display, or empty string for none.
     *
     * @var string
     */
    public string $error = '';

    /**
     * URL to redirect to after successful login, or empty string for default.
     *
     * @var string
     */
    public string $next = '';

    /**
     * Whether new user registration is open.
     *
     * @var bool
     */
    public bool $allow_registration = true;

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
        $this->document->title = 'Log in — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the login form.
     *
     * @return void
     */
    protected function generateBody(): void {
        $error = htmlspecialchars($this->error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $next  = htmlspecialchars($this->next, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12 m6 offset-m3">
                    <h4 class="center-align">Log in</h4>
                    <?php if ($error !== ''): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-content">
                            <form method="post" action="/login">
                                <?= $this->csrfField() ?>
                                <?php if ($next !== ''): ?>
                                <input type="hidden" name="next" value="<?= $next ?>">
                                <?php endif; ?>
                                <div class="input-field">
                                    <input type="email" id="email" name="email" required>
                                    <label for="email">Email</label>
                                </div>
                                <div class="input-field">
                                    <input type="password" id="password" name="password" required>
                                    <label for="password">Password</label>
                                </div>
                                <div class="center-align">
                                    <button class="btn waves-effect waves-light" type="submit">
                                        Log in
                                        <i class="material-icons right">login</i>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php if ($this->oauth_github || $this->oauth_google || $this->oauth_microsoft): ?>
                        <div class="card-action" style="display: flex; gap: 1rem;">
                            <?php if ($this->oauth_microsoft): ?>
                            <a href="/auth/microsoft">
                                <i class="material-icons left">business</i>Log in with Microsoft
                            </a>
                            <?php endif; ?>
                            <?php if ($this->oauth_github): ?>
                            <a href="/auth/github">
                                <i class="material-icons left">code</i>Log in with GitHub
                            </a>
                            <?php endif; ?>
                            <?php if ($this->oauth_google): ?>
                            <a href="/auth/google">
                                <i class="material-icons left">account_circle</i>Log in with Google
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($this->allow_registration): ?>
                    <p class="center-align">
                        No account? <a href="/register">Register</a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
