<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the authenticated user's dashboard with inline page management.
 *
 * Data properties:
 *   - $pages  array  The current user's owned and subscribed pages,
 *                    sorted by title, as plain arrays.
 *
 * @package Dealnews\Indexera\View
 */
class DashboardView extends BaseView {

    /**
     * The current user's pages.
     *
     * @var array
     */
    public array $pages = [];

    /**
     * Groups the current user belongs to.
     *
     * @var array
     */
    public array $groups = [];

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Dashboard — Indexera';
    }

    /**
     * Outputs the dashboard with Alpine.js-powered page management.
     *
     * @return void
     */
    protected function generateBody(): void {
        $pages_json  = htmlspecialchars(
            json_encode($this->pages),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $groups_json = htmlspecialchars(
            json_encode($this->groups),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        ?>
        <div class="container" x-data="pagesManager(<?= $pages_json ?>, <?= $groups_json ?>)">
            <div class="row">
                <div class="col s12">
                    <div class="row valign-wrapper" style="margin-top: 2rem;">
                        <div class="col s6">
                            <h4>Your Pages</h4>
                        </div>
                        <div class="col s6 right-align">
                            <button type="button"
                                    class="btn waves-effect waves-light"
                                    @click="startCreate()"
                                    x-show="!newPage">
                                <i class="material-icons left">add</i>Add page
                            </button>
                        </div>
                    </div>

                    <div x-show="error" x-cloak
                         class="card-panel red lighten-4 red-text text-darken-4"
                         x-text="error"></div>

                    <div x-show="newPage" x-cloak class="card">
                        <div class="card-content">
                            <span class="card-title">New page</span>
                            <form @submit.prevent="saveNew()">
                                <div class="input-field">
                                    <input type="text" id="new_title"
                                           x-model="newPage.title"
                                           @input="autoSlug(newPage)">
                                    <label for="new_title">Title</label>
                                </div>
                                <div class="input-field">
                                    <input type="text" id="new_slug" x-model="newPage.slug">
                                    <label for="new_slug">Slug</label>
                                </div>
                                <div class="input-field">
                                    <textarea id="new_description"
                                              class="materialize-textarea"
                                              x-model="newPage.description"></textarea>
                                    <label for="new_description">Description</label>
                                </div>
                                <template x-if="groups.length > 0">
                                    <div class="input-field">
                                        <select id="new_group_id" x-model="newPage.group_id">
                                            <option value="0">Personal (no group)</option>
                                            <template x-for="g in groups" :key="g.group_id">
                                                <option :value="g.group_id"
                                                        x-text="g.name"></option>
                                            </template>
                                        </select>
                                        <label for="new_group_id">Group</label>
                                    </div>
                                </template>
                                <label>
                                    <input type="checkbox" x-model="newPage.is_public">
                                    <span x-text="newPage.group_id > 0
                                        ? 'Public (visible to everyone)'
                                        : 'Public'"></span>
                                </label>
                                <div style="margin-top: 1rem;">
                                    <button type="submit"
                                            class="btn waves-effect waves-light">
                                        <i class="material-icons left">save</i>Save
                                    </button>
                                    <button type="button"
                                            class="btn-flat waves-effect"
                                            @click="cancelCreate()"
                                            style="margin-left: 0.5rem;">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <p x-show="pages.length === 0 && !newPage" x-cloak class="grey-text">
                        You have no pages yet.
                    </p>

                    <ul class="collection">
                        <template x-for="page in pages" :key="page.page_id">
                            <li class="collection-item">

                                <!-- Owned page — view mode -->
                                <div x-show="page.is_owned && editingId !== page.page_id">
                                    <div class="row valign-wrapper" style="margin: 0;">
                                        <div class="col s6 m8">
                                            <a :href="'/' + (page.group_slug || page.owner_display_name) + '/' + page.slug"
                                               class="title"
                                               x-text="page.title"></a>
                                            <span x-show="page.group_name"
                                                  class="new badge teal"
                                                  :data-badge-caption="page.group_name"></span>
                                            <span x-show="!page.is_public && !page.group_slug"
                                                  class="new badge grey"
                                                  data-badge-caption="private"></span>
                                            <span x-show="!page.is_public && page.group_slug"
                                                  class="new badge grey"
                                                  data-badge-caption="group only"></span>
                                        </div>
                                        <div class="col s6 m4 right-align page-list-btn">
                                            <a :href="'/pages/' + page.page_id + '/edit'"
                                               class="btn-small waves-effect waves-light tooltipped"
                                               data-tooltip="Manage sections &amp; links"
                                               style="margin-right: 0.25rem;">
                                                <i class="material-icons">tune</i>
                                            </a>
                                            <button type="button"
                                                    class="btn-small waves-effect waves-light tooltipped"
                                                    data-tooltip="Edit"
                                                    @click="startEdit(page)"
                                                    style="margin-right: 0.25rem;">
                                                <i class="material-icons">edit</i>
                                            </button>
                                            <button type="button"
                                                    class="btn-small waves-effect waves-light red tooltipped"
                                                    data-tooltip="Delete"
                                                    @click="deletePage(page)">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Owned page — edit mode -->
                                <div x-show="(page.is_owned || page.is_editor) && editingId === page.page_id" x-cloak>
                                    <form @submit.prevent="saveEdit(page)">
                                        <div class="input-field">
                                            <input type="text"
                                                   :id="'edit_title_' + page.page_id"
                                                   x-model="editData.title">
                                            <label :for="'edit_title_' + page.page_id"
                                                   class="active">Title</label>
                                        </div>
                                        <div class="input-field">
                                            <input type="text"
                                                   :id="'edit_slug_' + page.page_id"
                                                   x-model="editData.slug">
                                            <label :for="'edit_slug_' + page.page_id"
                                                   class="active">Slug</label>
                                        </div>
                                        <div class="input-field">
                                            <textarea :id="'edit_desc_' + page.page_id"
                                                      class="materialize-textarea"
                                                      x-model="editData.description"></textarea>
                                            <label :for="'edit_desc_' + page.page_id"
                                                   class="active">Description</label>
                                        </div>
                                        <label>
                                            <input type="checkbox" x-model="editData.is_public">
                                            <span>Public</span>
                                        </label>
                                        <div style="margin-top: 1rem;">
                                            <button type="submit"
                                                    class="btn waves-effect waves-light">
                                                <i class="material-icons left">save</i>Save
                                            </button>
                                            <button type="button"
                                                    class="btn-flat waves-effect"
                                                    @click="cancelEdit()"
                                                    style="margin-left: 0.5rem;">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Editor page — view mode -->
                                <div x-show="page.is_editor && editingId !== page.page_id">
                                    <div class="row valign-wrapper" style="margin: 0;">
                                        <div class="col s6 m8">
                                            <a :href="'/' + (page.group_slug || page.owner_display_name) + '/' + page.slug"
                                               class="title"
                                               x-text="page.title"></a>
                                            <span class="grey-text"
                                                  x-text="' by ' + page.owner_display_name"></span>
                                            <span x-show="!page.is_public"
                                                  class="new badge grey"
                                                  data-badge-caption="private"></span>
                                            <span class="new badge teal"
                                                  data-badge-caption="editor"></span>
                                        </div>
                                        <div class="col s6 m4 right-align page-list-btn">
                                            <a :href="'/pages/' + page.page_id + '/edit'"
                                               class="btn-small waves-effect waves-light tooltipped"
                                               data-tooltip="Manage sections &amp; links"
                                               style="margin-right: 0.25rem;">
                                                <i class="material-icons">tune</i>
                                            </a>
                                            <button type="button"
                                                    class="btn-small waves-effect waves-light tooltipped"
                                                    data-tooltip="Edit"
                                                    @click="startEdit(page)">
                                                <i class="material-icons">edit</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subscribed page -->
                                <div x-show="!page.is_owned && !page.is_editor && !page.is_group_member">
                                    <div class="row valign-wrapper" style="margin: 0;">
                                        <div class="col s6 m8">
                                            <a :href="'/' + (page.group_slug || page.owner_display_name) + '/' + page.slug"
                                               class="title"
                                               x-text="page.title"></a>
                                            <span class="grey-text"
                                                  x-text="' by ' + page.owner_display_name"></span>
                                            <span class="new badge blue lighten-1 white-text"
                                                  data-badge-caption="subscribed"></span>
                                        </div>
                                        <div class="col s6 m4 right-align page-list-btn">
                                            <button type="button"
                                                    class="btn-small waves-effect waves-light orange tooltipped"
                                                    data-tooltip="Unsubscribe"
                                                    @click="unsubscribe(page)">
                                                <i class="material-icons">bookmark_remove</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Group member page (not owned or editor) -->
                                <div x-show="page.is_group_member && !page.is_owned && !page.is_editor">
                                    <div class="row valign-wrapper" style="margin: 0;">
                                        <div class="col s6 m8">
                                            <a :href="'/' + page.group_slug + '/' + page.slug"
                                               class="title"
                                               x-text="page.title"></a>
                                            <span class="grey-text"
                                                  x-text="' by ' + page.owner_display_name"></span>
                                            <span class="new badge teal"
                                                  :data-badge-caption="page.group_name"></span>
                                            <span x-show="!page.is_public"
                                                  class="new badge grey"
                                                  data-badge-caption="group only"></span>
                                        </div>
                                        <div class="col s6 m4 right-align page-list-btn">
                                            <a :href="'/pages/' + page.page_id + '/edit'"
                                               class="btn-small waves-effect waves-light tooltipped"
                                               data-tooltip="Manage sections &amp; links"
                                               style="margin-right: 0.25rem;">
                                                <i class="material-icons">tune</i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Outputs the Alpine.js and data-mapper-client scripts then closes the document.
     *
     * @return void
     */
    protected function generateFooter(): void {
        ?>
        <script type="module">
            import { DataMapperClient } from 'https://cdn.jsdelivr.net/npm/@dealnews/data-mapper-client/+esm';

            const client               = new DataMapperClient({ baseUrl: window.location.origin });
            const pageResource         = client.resource('Page');
            const subscriptionResource = client.resource('PageSubscription');

            document.addEventListener('alpine:init', () => {
                Alpine.data('pagesManager', (initialPages, initialGroups) => ({
                    pages:     initialPages,
                    groups:    initialGroups,
                    newPage:   null,
                    editingId: null,
                    editData:  {},
                    error:     '',

                    autoSlug(page) {
                        page.slug = page.title
                            .toLowerCase()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    },

                    startCreate() {
                        this.newPage   = {
                            title:       '',
                            slug:        '',
                            description: '',
                            is_public:   true,
                            group_id:    0,
                        };
                        this.editingId = null;
                        this.error     = '';
                        this.$nextTick(() => {
                            M.FormSelect.init(document.querySelectorAll('select'));
                        });
                    },

                    cancelCreate() {
                        this.newPage = null;
                    },

                    async saveNew() {
                        try {
                            const payload = {
                                title:       this.newPage.title,
                                slug:        this.newPage.slug,
                                description: this.newPage.description,
                                is_public:   this.newPage.is_public,
                            };
                            if (parseInt(this.newPage.group_id) > 0) {
                                payload.group_id = parseInt(this.newPage.group_id);
                            }
                            const created = await pageResource.create(payload);
                            const group   = this.groups.find(
                                g => g.group_id === (created.group_id || 0)
                            );
                            created.is_owned           = true;
                            created.is_editor          = false;
                            created.is_group_member    = false;
                            created.subscription_id    = null;
                            created.owner_display_name = created.owner_display_name ?? '';
                            created.group_slug         = group ? group.slug : null;
                            created.group_name         = group ? group.name : null;
                            this.pages.push(created);
                            this.pages.sort((a, b) => a.title.localeCompare(b.title));
                            this.newPage = null;
                            this.error   = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save page.';
                        }
                    },

                    startEdit(page) {
                        this.editingId = page.page_id;
                        this.editData  = {
                            title:       page.title,
                            slug:        page.slug,
                            description: page.description ?? '',
                            is_public:   page.is_public,
                        };
                        this.newPage = null;
                        this.error   = '';
                        this.$nextTick(() => M.updateTextFields());
                    },

                    cancelEdit() {
                        this.editingId = null;
                        this.editData  = {};
                    },

                    async saveEdit(page) {
                        try {
                            const updated = await pageResource.update(page.page_id, this.editData);
                            Object.assign(page, updated);
                            this.pages.sort((a, b) => a.title.localeCompare(b.title));
                            this.editingId = null;
                            this.error     = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save page.';
                        }
                    },

                    async deletePage(page) {
                        if (!confirm(`Delete "${page.title}"?`)) {
                            return;
                        }
                        try {
                            await pageResource.delete(page.page_id);
                            this.pages = this.pages.filter(p => p.page_id !== page.page_id);
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not delete page.';
                        }
                    },

                    async unsubscribe(page) {
                        if (!confirm(`Unsubscribe from "${page.title}"?`)) {
                            return;
                        }
                        try {
                            await subscriptionResource.delete(page.subscription_id);
                            this.pages = this.pages.filter(p => p.page_id !== page.page_id);
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not unsubscribe.';
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
