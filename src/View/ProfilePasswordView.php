<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the change-password form.
 *
 * Data properties:
 *   - $error  string  Validation or auth error message, or empty string.
 *
 * @package Dealnews\Indexera\View
 */
class ProfilePasswordView extends BaseView {

    /**
     * Error message from the previous POST attempt, if any.
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
        $this->document->title = 'Change Password — Indexera';
    }

    /**
     * Outputs the change-password form.
     *
     * @return void
     */
    protected function generateBody(): void {
        $error = htmlspecialchars($this->error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12 m6 offset-m3">
                    <h4 style="margin-top: 2rem;">Change password</h4>

                    <?php if ($error !== ''): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-content">
                            <form method="POST" action="/profile/password">
                                <?= $this->csrfField() ?>
                                <div class="input-field">
                                    <input type="password" id="current_password"
                                           name="current_password" required>
                                    <label for="current_password">Current password</label>
                                </div>
                                <div class="input-field">
                                    <input type="password" id="new_password"
                                           name="new_password" required>
                                    <label for="new_password">New password</label>
                                </div>
                                <div class="input-field">
                                    <input type="password" id="confirm_password"
                                           name="confirm_password" required>
                                    <label for="confirm_password">Confirm new password</label>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <button type="submit"
                                            class="btn waves-effect waves-light">
                                        <i class="material-icons left">lock</i>Update password
                                    </button>
                                    <a href="/profile"
                                       class="btn-flat waves-effect"
                                       style="margin-left: 0.5rem;">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
