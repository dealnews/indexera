<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the authenticated user's profile edit page.
 *
 * Lets the user update display_name and avatar_url via the API,
 * and provides a link to the change-password page.
 *
 * @package Dealnews\Indexera\View
 */
class ProfileView extends BaseView {

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Profile — Indexera';
    }

    /**
     * Outputs the profile form.
     *
     * @return void
     */
    protected function generateBody(): void {
        $user_id      = $this->current_user->user_id;
        $display_name = htmlspecialchars(
            json_encode($this->current_user->display_name),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $avatar_url = htmlspecialchars(
            json_encode($this->current_user->avatar_url ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        ?>
        <div class="container" x-data="profileManager(<?= $user_id ?>, <?= $display_name ?>, <?= $avatar_url ?>)">
            <div class="row">
                <div class="col s12 m8 offset-m2">
                    <h4 style="margin-top: 2rem;">Profile</h4>

                    <div x-show="success" x-cloak
                         class="card-panel green lighten-4 green-text text-darken-4"
                         x-text="success"></div>

                    <div x-show="error" x-cloak
                         class="card-panel red lighten-4 red-text text-darken-4"
                         x-text="error"></div>

                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Account details</span>
                            <form @submit.prevent="save()">
                                <div class="input-field">
                                    <input type="text" id="display_name"
                                           x-model="displayName"
                                           pattern="[a-z0-9_\-]+"
                                           title="Lowercase letters, numbers, hyphens, and underscores only">
                                    <label for="display_name" class="active">Username</label>
                                    <span class="helper-text">
                                        Lowercase letters, numbers, hyphens, and underscores only
                                    </span>
                                </div>
                                <div class="input-field">
                                    <input type="url" id="avatar_url"
                                           x-model="avatarUrl">
                                    <label for="avatar_url" class="active">Avatar URL</label>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <button type="submit"
                                            class="btn waves-effect waves-light"
                                            :disabled="saving">
                                        <i class="material-icons left">save</i>Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Password</span>
                            <p>
                                <a href="/profile/password"
                                   class="btn waves-effect waves-light">
                                    Change password
                                </a>
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Outputs the Alpine.js profile component script.
     *
     * @return void
     */
    protected function generateFooter(): void {
        ?>
        <script type="module">
            import { DataMapperClient } from 'https://cdn.jsdelivr.net/npm/@dealnews/data-mapper-client/+esm';

            const client       = new DataMapperClient({ baseUrl: window.location.origin });
            const userResource = client.resource('User');

            document.addEventListener('alpine:init', () => {
                Alpine.data('profileManager', (userId, initialDisplayName, initialAvatarUrl) => ({
                    userId:      userId,
                    displayName: initialDisplayName,
                    avatarUrl:   initialAvatarUrl,
                    saving:      false,
                    success:     '',
                    error:       '',

                    async save() {
                        this.saving  = true;
                        this.success = '';
                        this.error   = '';
                        try {
                            await userResource.update(this.userId, {
                                display_name: this.displayName,
                                avatar_url:   this.avatarUrl || null,
                            });
                            this.success = 'Profile saved.';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save profile.';
                        } finally {
                            this.saving = false;
                        }
                    },
                }));
            });
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
        <?php
        parent::generateFooter();
    }
}
