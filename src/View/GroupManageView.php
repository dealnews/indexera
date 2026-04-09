<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

use Dealnews\Indexera\Data\Group;

/**
 * Renders the group member management page.
 *
 * Data properties:
 *   - $group    Group  The group being managed.
 *   - $members  array  Current members: [{group_member_id, user_id, display_name, email}, ...].
 *
 * @package Dealnews\Indexera\View
 */
class GroupManageView extends BaseView {

    /**
     * The group being managed.
     *
     * @var Group|null
     */
    public ?Group $group = null;

    /**
     * Current group members.
     *
     * @var array
     */
    public array $members = [];

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $name = $this->group !== null
            ? htmlspecialchars($this->group->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : 'Group';

        $this->document->title = 'Manage: ' . $name . ' — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the member management UI.
     *
     * @return void
     */
    protected function generateBody(): void {
        $group_name   = htmlspecialchars($this->group->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $group_slug   = htmlspecialchars($this->group->slug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $members_json = htmlspecialchars(
            json_encode($this->members),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $group_id     = $this->group->group_id;
        ?>
        <div class="container"
             x-data="groupMembers(<?= $members_json ?>, <?= $group_id ?>)">
            <div class="row">
                <div class="col s12">
                    <div style="margin-top: 2rem;">
                        <h4 style="margin-bottom: 0;"><?= $group_name ?></h4>
                        <a href="/groups/<?= $group_slug ?>"
                           class="btn-flat btn-small waves-effect"
                           style="padding-left: 0; margin-left: -0.5rem;">
                            <i class="material-icons left">arrow_back</i>Back to group
                        </a>
                    </div>

                    <div x-show="error" x-cloak
                         class="card-panel red lighten-4 red-text text-darken-4"
                         x-text="error"></div>

                    <h5>Members</h5>

                    <div class="card">
                        <div class="card-content">
                            <table class="striped" x-show="members.length > 0" x-cloak>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="member in members"
                                              :key="member.group_member_id">
                                        <tr>
                                            <td x-text="member.display_name"></td>
                                            <td x-text="member.email"></td>
                                            <td class="right-align">
                                                <button type="button"
                                                        class="btn-small waves-effect waves-light red"
                                                        @click="removeMember(member)">
                                                    <i class="material-icons">person_remove</i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <p x-show="members.length === 0" x-cloak
                               class="grey-text" style="margin: 0;">
                                No members yet.
                            </p>
                        </div>
                        <div class="card-action">
                            <form @submit.prevent="addMember()"
                                  style="display: flex; gap: 1rem; align-items: flex-end;">
                                <div class="input-field"
                                     style="margin: 0; flex: 1;">
                                    <input type="email" id="member_email"
                                           x-model="newEmail" required>
                                    <label for="member_email">Add member by email</label>
                                </div>
                                <button type="submit"
                                        class="btn waves-effect waves-light"
                                        style="margin-bottom: 0.75rem;">
                                    <i class="material-icons left">person_add</i>Add
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Outputs Alpine.js member management scripts and closes the document.
     *
     * @return void
     */
    protected function generateFooter(): void {
        ?>
        <script type="module">
            import { DataMapperClient } from 'https://cdn.jsdelivr.net/npm/@dealnews/data-mapper-client/+esm';

            const client         = new DataMapperClient({ baseUrl: window.location.origin });
            const memberResource = client.resource('GroupMember');

            document.addEventListener('alpine:init', () => {
                Alpine.data('groupMembers', (initialMembers, groupId) => ({
                    members:  initialMembers,
                    groupId:  groupId,
                    newEmail: '',
                    error:    '',

                    async addMember() {
                        try {
                            const created = await memberResource.create({
                                group_id: this.groupId,
                                email:    this.newEmail,
                            });
                            this.members.push(created);
                            this.newEmail = '';
                            this.error    = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not add member.';
                        }
                    },

                    async removeMember(member) {
                        if (!confirm(`Remove ${member.display_name} from this group?`)) {
                            return;
                        }
                        try {
                            await memberResource.delete(member.group_member_id);
                            this.members = this.members.filter(
                                m => m.group_member_id !== member.group_member_id
                            );
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not remove member.';
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
