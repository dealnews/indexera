<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View\Admin;

use Dealnews\Indexera\View\BaseView;

/**
 * Renders the admin user management table.
 *
 * Data properties:
 *   - $users  array  All User objects.
 *
 * @package Dealnews\Indexera\View\Admin
 */
class UsersView extends BaseView {

    /**
     * All user accounts.
     *
     * @var array
     */
    public array $users = [];

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Users — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the user management table and add-user form.
     *
     * @return void
     */
    protected function generateBody(): void {
        $current_user_id = $this->current_user?->user_id ?? 0;
        ?>
        <div class="container" x-data="addUser()">
            <div class="row">
                <div class="col s12">
                    <h4 style="margin-top: 2rem;">Add user</h4>

                    <div x-show="error" x-cloak
                         class="card-panel red lighten-4 red-text text-darken-4"
                         x-text="error"></div>

                    <div class="card">
                        <div class="card-content">
                            <form @submit.prevent="save()">
                                <div class="row" style="margin-bottom: 0;">
                                    <div class="input-field col s12 m3">
                                        <input type="email" id="email"
                                               x-model="email" required>
                                        <label for="email">Email</label>
                                    </div>
                                    <div class="input-field col s12 m3">
                                        <input type="text" id="display_name"
                                               x-model="display_name"
                                               maxlength="100" required
                                               pattern="[a-z0-9_\-]+"
                                               title="Lowercase letters, numbers, hyphens, and underscores only">
                                        <label for="display_name">Username</label>
                                    </div>
                                    <div class="input-field col s12 m3">
                                        <input type="password" id="password"
                                               x-model="password">
                                        <label for="password">Password (optional)</label>
                                    </div>
                                    <div class="col s12 m3" style="margin-top: 1.5rem;">
                                        <button type="submit"
                                                class="btn waves-effect waves-light">
                                            <i class="material-icons left">person_add</i>Add
                                        </button>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 0;">
                                    <div class="col s12">
                                        <label>
                                            <input type="checkbox" x-model="is_admin">
                                            <span>Admin</span>
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <h4>Users</h4>
                    <table class="striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Admin</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->users as $user): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($user->display_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?= $user->is_admin ? 'Yes' : 'No' ?>
                                </td>
                                <td class="right-align">
                                    <?php if ($user->user_id !== $current_user_id): ?>
                                    <form method="post"
                                          action="/admin/users/<?= (int)$user->user_id ?>/toggle-admin"
                                          style="display: inline;">
                                        <?= $this->csrfField() ?>
                                        <button type="submit"
                                                class="btn-small waves-effect waves-light <?= $user->is_admin ? 'orange' : '' ?>"
                                                style="margin-right: 0.25rem;">
                                            <?= $user->is_admin ? 'Revoke admin' : 'Make admin' ?>
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="btn-small waves-effect waves-light red"
                                            @click="deleteUser(<?= (int)$user->user_id ?>, $event)">
                                        <i class="material-icons">delete</i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Outputs the Alpine.js component and closes the document.
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
                Alpine.data('addUser', () => ({
                    email:        '',
                    display_name: '',
                    password:     '',
                    is_admin:     false,
                    error:        '',

                    async deleteUser(userId, event) {
                        if (!confirm('Delete this user? This cannot be undone.')) {
                            return;
                        }
                        const row = event.currentTarget.closest('tr');
                        try {
                            await userResource.delete(userId);
                            row.remove();
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not delete user.';
                        }
                    },

                    async save() {
                        const payload = {
                            email:        this.email,
                            display_name: this.display_name,
                            is_admin:     this.is_admin,
                        };

                        if (this.password !== '') {
                            payload.password = this.password;
                        }

                        try {
                            await userResource.create(payload);
                            window.location.reload();
                        } catch (e) {
                            this.error = e.message ?? 'Could not create user.';
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
