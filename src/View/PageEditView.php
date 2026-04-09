<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

use Dealnews\Indexera\Data\Page;

/**
 * Renders the section and link editor for a page.
 *
 * Data properties:
 *   - $page      Page   The page being edited.
 *   - $sections  array  Sections with their links as plain arrays.
 *   - $editors   array  Editor records with user info.
 *   - $is_owner  bool   Whether the current user is the page owner.
 *
 * @package Dealnews\Indexera\View
 */
class PageEditView extends BaseView {

    /**
     * The page being edited.
     *
     * @var Page|null
     */
    public ?Page $page = null;

    /**
     * Sections with their links as plain arrays.
     *
     * @var array
     */
    public array $sections = [];

    /**
     * Editor records: [{page_editor_id, user_id, display_name, email}, ...].
     *
     * @var array
     */
    public array $editors = [];

    /**
     * Whether the current user is the page owner (vs. an editor).
     *
     * @var bool
     */
    public bool $is_owner = false;

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $title = $this->page !== null
            ? htmlspecialchars($this->page->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : 'Edit page';

        $this->document->title = 'Edit: ' . $title . ' — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the page editor.
     *
     * @return void
     */
    protected function generateBody(): void {
        $page_title    = htmlspecialchars($this->page->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sections_json = htmlspecialchars(
            json_encode($this->sections),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $editors_json  = htmlspecialchars(
            json_encode($this->editors),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $page_id       = $this->page->page_id;
        ?>
        <div class="container" x-data="pageEditor(<?= $sections_json ?>, <?= $page_id ?>)">
            <div class="row">
                <div class="col s12">
                    <div style="margin-top: 2rem;">
                        <h4 style="margin-bottom: 0;"><?= $page_title ?></h4>
                        <a href="/dashboard" class="btn-flat btn-small waves-effect"
                           style="padding-left: 0; margin-left: -0.5rem;">
                            <i class="material-icons left">arrow_back</i>Dashboard
                        </a>
                    </div>

                    <div x-show="error" x-cloak
                         class="card-panel red lighten-4 red-text text-darken-4"
                         x-text="error"></div>

                    <div class="row valign-wrapper">
                        <div class="col s6">
                            <h5>Sections</h5>
                        </div>
                        <div class="col s6 right-align">
                            <button type="button"
                                    class="btn waves-effect waves-light"
                                    @click="startCreateSection()"
                                    x-show="!newSection">
                                <i class="material-icons left">add</i>Add section
                            </button>
                        </div>
                    </div>

                    <div x-show="newSection" x-cloak class="card">
                        <div class="card-content">
                            <span class="card-title">New section</span>
                            <form @submit.prevent="saveNewSection()">
                                <div class="input-field">
                                    <input type="text" id="new_section_title"
                                           x-model="newSection.title">
                                    <label for="new_section_title">Title</label>
                                </div>
                                <button type="submit"
                                        class="btn waves-effect waves-light">
                                    <i class="material-icons left">save</i>Save
                                </button>
                                <button type="button"
                                        class="btn-flat waves-effect"
                                        @click="cancelCreateSection()"
                                        style="margin-left: 0.5rem;">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>

                    <p x-show="sections.length === 0 && !newSection" x-cloak class="grey-text">
                        No sections yet.
                    </p>

                    <ul x-sort="onSectionSort($item, $position)" style="padding: 0;">
                        <template x-for="section in sections" :key="section.section_id">
                            <li x-sort:item="section.section_id"
                                style="list-style: none; margin-bottom: 1rem;">
                                <div class="card">
                                    <div class="card-content">

                                        <div x-show="editingSectionId !== section.section_id">
                                            <div class="row valign-wrapper" style="margin: 0;">
                                                <div class="col s1">
                                                    <i class="material-icons grey-text"
                                                       style="cursor: grab;">drag_indicator</i>
                                                </div>
                                                <div class="col s7">
                                                    <span class="card-title"
                                                          style="margin: 0;"
                                                          x-text="section.title"></span>
                                                </div>
                                                <div class="col s4 right-align">
                                                    <button type="button"
                                                            class="btn-small waves-effect waves-light"
                                                            @click="startEditSection(section)"
                                                            style="margin-right: 0.25rem;">
                                                        <i class="material-icons">edit</i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn-small waves-effect waves-light red"
                                                            @click="deleteSection(section)">
                                                        <i class="material-icons">delete</i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div x-show="editingSectionId === section.section_id"
                                             x-cloak>
                                            <form @submit.prevent="saveEditSection(section)">
                                                <div class="input-field">
                                                    <input type="text"
                                                           :id="'sec_title_' + section.section_id"
                                                           x-model="editSectionData.title">
                                                    <label :for="'sec_title_' + section.section_id"
                                                           class="active">Title</label>
                                                </div>
                                                <button type="submit"
                                                        class="btn waves-effect waves-light">
                                                    <i class="material-icons left">save</i>Save
                                                </button>
                                                <button type="button"
                                                        class="btn-flat waves-effect"
                                                        @click="cancelEditSection()"
                                                        style="margin-left: 0.5rem;">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>

                                        <ul x-sort="(linkId, pos) => onLinkSort(section, linkId, pos)"
                                            class="collection" style="margin-top: 1rem;">
                                            <template x-for="link in section.links"
                                                      :key="link.link_id">
                                                <li x-sort:item="link.link_id"
                                                    class="collection-item">

                                                    <div x-show="editingLinkId !== link.link_id">
                                                        <div class="row valign-wrapper"
                                                             style="margin: 0;">
                                                            <div class="col s1">
                                                                <i class="material-icons grey-text"
                                                                   style="cursor: grab;">
                                                                    drag_indicator
                                                                </i>
                                                            </div>
                                                            <div class="col s7">
                                                                <span x-text="link.label"></span>
                                                            </div>
                                                            <div class="col s4 right-align">
                                                                <button type="button"
                                                                        class="btn-small waves-effect waves-light"
                                                                        @click="startEditLink(link)"
                                                                        style="margin-right: 0.25rem;">
                                                                    <i class="material-icons">edit</i>
                                                                </button>
                                                                <button type="button"
                                                                        class="btn-small waves-effect waves-light red"
                                                                        @click="deleteLink(section, link)">
                                                                    <i class="material-icons">delete</i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div x-show="editingLinkId === link.link_id"
                                                         x-cloak>
                                                        <form @submit.prevent="saveEditLink(link)">
                                                            <div class="input-field">
                                                                <input type="text"
                                                                       :id="'link_label_' + link.link_id"
                                                                       x-model="editLinkData.label">
                                                                <label :for="'link_label_' + link.link_id"
                                                                       class="active">Label</label>
                                                            </div>
                                                            <div class="input-field">
                                                                <input type="url"
                                                                       :id="'link_url_' + link.link_id"
                                                                       x-model="editLinkData.url">
                                                                <label :for="'link_url_' + link.link_id"
                                                                       class="active">URL</label>
                                                            </div>
                                                            <button type="submit"
                                                                    class="btn waves-effect waves-light">
                                                                <i class="material-icons left">save</i>Save
                                                            </button>
                                                            <button type="button"
                                                                    class="btn-flat waves-effect"
                                                                    @click="cancelEditLink()"
                                                                    style="margin-left: 0.5rem;">
                                                                Cancel
                                                            </button>
                                                        </form>
                                                    </div>

                                                </li>
                                            </template>
                                        </ul>

                                        <div x-show="newLinkSectionId === section.section_id"
                                             x-cloak style="margin-top: 1rem;">
                                            <form @submit.prevent="saveNewLink(section)">
                                                <div class="input-field">
                                                    <input type="text"
                                                           :id="'new_link_label_' + section.section_id"
                                                           x-model="newLink.label">
                                                    <label :for="'new_link_label_' + section.section_id">
                                                        Label
                                                    </label>
                                                </div>
                                                <div class="input-field">
                                                    <input type="url"
                                                           :id="'new_link_url_' + section.section_id"
                                                           x-model="newLink.url">
                                                    <label :for="'new_link_url_' + section.section_id">
                                                        URL
                                                    </label>
                                                </div>
                                                <button type="submit"
                                                        class="btn waves-effect waves-light">
                                                    <i class="material-icons left">save</i>Save
                                                </button>
                                                <button type="button"
                                                        class="btn-flat waves-effect"
                                                        @click="cancelCreateLink()"
                                                        style="margin-left: 0.5rem;">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>

                                        <div style="margin-top: 1rem;">
                                            <button type="button"
                                                    class="btn-small waves-effect waves-light"
                                                    x-show="newLinkSectionId !== section.section_id"
                                                    @click="startCreateLink(section)">
                                                <i class="material-icons left">add</i>Add link
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>

                </div>
            </div>

            <div class="row" x-data="pageEditors(<?= $editors_json ?>, <?= $page_id ?>)">
                <div class="col s12">
                    <h5>Editors</h5>

                    <div x-show="error" x-cloak
                         class="card-panel red lighten-4 red-text text-darken-4"
                         x-text="error"></div>

                    <div class="card">
                        <div class="card-content">
                            <table class="striped" x-show="editors.length > 0" x-cloak>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="editor in editors" :key="editor.page_editor_id">
                                        <tr>
                                            <td x-text="editor.display_name"></td>
                                            <td x-text="editor.email"></td>
                                            <td class="right-align">
                                                <button type="button"
                                                        class="btn-small waves-effect waves-light red"
                                                        @click="removeEditor(editor)">
                                                    <i class="material-icons">person_remove</i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <p x-show="editors.length === 0" x-cloak class="grey-text"
                               style="margin: 0;">
                                No editors yet.
                            </p>
                        </div>
                        <div class="card-action">
                            <form @submit.prevent="addEditor()" style="display: flex; gap: 1rem; align-items: flex-end;">
                                <div class="input-field" style="margin: 0; flex: 1;">
                                    <input type="email" id="editor_email"
                                           x-model="newEmail" required>
                                    <label for="editor_email">Add editor by email</label>
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
     * Outputs scripts and closes the document.
     *
     * @return void
     */
    protected function generateFooter(): void {
        ?>
        <script type="module">
            import { DataMapperClient } from 'https://cdn.jsdelivr.net/npm/@dealnews/data-mapper-client/+esm';

            const client          = new DataMapperClient({ baseUrl: window.location.origin });
            const sectionResource = client.resource('Section');
            const linkResource    = client.resource('Link');
            const editorResource  = client.resource('PageEditor');

            document.addEventListener('alpine:init', () => {
                Alpine.data('pageEditor', (initialSections, pageId) => ({
                    sections:         initialSections,
                    pageId:           pageId,
                    error:            '',

                    newSection:       null,
                    editingSectionId: null,
                    editSectionData:  {},

                    newLink:          null,
                    newLinkSectionId: null,
                    editingLinkId:    null,
                    editLinkData:     {},

                    // Section CRUD

                    startCreateSection() {
                        this.newSection       = { title: '' };
                        this.editingSectionId = null;
                        this.error            = '';
                    },

                    cancelCreateSection() {
                        this.newSection = null;
                    },

                    async saveNewSection() {
                        try {
                            const created    = await sectionResource.create({
                                page_id:    this.pageId,
                                title:      this.newSection.title,
                                sort_order: this.sections.length + 1,
                            });
                            created.links   = [];
                            this.sections.push(created);
                            this.newSection = null;
                            this.error      = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save section.';
                        }
                    },

                    startEditSection(section) {
                        this.editingSectionId = section.section_id;
                        this.editSectionData  = { title: section.title };
                        this.newSection       = null;
                        this.error            = '';
                        this.$nextTick(() => M.updateTextFields());
                    },

                    cancelEditSection() {
                        this.editingSectionId = null;
                        this.editSectionData  = {};
                    },

                    async saveEditSection(section) {
                        try {
                            const updated = await sectionResource.update(
                                section.section_id,
                                { title: this.editSectionData.title }
                            );
                            section.title         = updated.title;
                            this.editingSectionId = null;
                            this.editSectionData  = {};
                            this.error            = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save section.';
                        }
                    },

                    async deleteSection(section) {
                        if (!confirm(`Delete section "${section.title}" and all its links?`)) {
                            return;
                        }
                        try {
                            await sectionResource.delete(section.section_id);
                            this.sections = this.sections.filter(
                                s => s.section_id !== section.section_id
                            );
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not delete section.';
                        }
                    },

                    // Section sort

                    async onSectionSort(sectionId, newPosition) {
                        const idx = this.sections.findIndex(s => s.section_id == sectionId);
                        if (idx === -1) return;
                        const [moved] = this.sections.splice(idx, 1);
                        this.sections.splice(newPosition, 0, moved);
                        try {
                            await Promise.all(
                                this.sections.map((s, i) =>
                                    sectionResource.update(s.section_id, { sort_order: i + 1 })
                                )
                            );
                        } catch (e) {
                            this.error = e.message ?? 'Could not update section order.';
                        }
                    },

                    // Link CRUD

                    startCreateLink(section) {
                        this.newLinkSectionId = section.section_id;
                        this.newLink          = { label: '', url: '' };
                        this.editingLinkId    = null;
                        this.error            = '';
                    },

                    cancelCreateLink() {
                        this.newLinkSectionId = null;
                        this.newLink          = null;
                    },

                    async saveNewLink(section) {
                        try {
                            const created = await linkResource.create({
                                section_id:  section.section_id,
                                label:      this.newLink.label,
                                url:        this.newLink.url,
                                sort_order:  section.links.length + 1,
                            });
                            section.links.push(created);
                            this.newLinkSectionId = null;
                            this.newLink          = null;
                            this.error            = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save link.';
                        }
                    },

                    startEditLink(link) {
                        this.editingLinkId = link.link_id;
                        this.editLinkData  = {
                            label: link.label,
                            url:   link.url,
                        };
                        this.newLinkSectionId = null;
                        this.error            = '';
                        this.$nextTick(() => M.updateTextFields());
                    },

                    cancelEditLink() {
                        this.editingLinkId = null;
                        this.editLinkData  = {};
                    },

                    async saveEditLink(link) {
                        try {
                            const updated = await linkResource.update(link.link_id, {
                                label: this.editLinkData.label,
                                url:   this.editLinkData.url,
                            });
                            Object.assign(link, updated);
                            this.editingLinkId = null;
                            this.editLinkData  = {};
                            this.error         = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not save link.';
                        }
                    },

                    async deleteLink(section, link) {
                        if (!confirm(`Delete link "${link.label}"?`)) {
                            return;
                        }
                        try {
                            await linkResource.delete(link.link_id);
                            section.links = section.links.filter(
                                l => l.link_id !== link.link_id
                            );
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not delete link.';
                        }
                    },

                    // Link sort

                    async onLinkSort(section, linkId, newPosition) {
                        const idx = section.links.findIndex(l => l.link_id == linkId);
                        if (idx === -1) return;
                        const [moved] = section.links.splice(idx, 1);
                        section.links.splice(newPosition, 0, moved);
                        try {
                            await Promise.all(
                                section.links.map((l, i) =>
                                    linkResource.update(l.link_id, { sort_order: i + 1 })
                                )
                            );
                        } catch (e) {
                            this.error = e.message ?? 'Could not update link order.';
                        }
                    },
                }));

                Alpine.data('pageEditors', (initialEditors, pageId) => ({
                    editors:  initialEditors,
                    pageId:   pageId,
                    newEmail: '',
                    error:    '',

                    async addEditor() {
                        try {
                            const created = await editorResource.create({
                                page_id: this.pageId,
                                email:   this.newEmail,
                            });
                            this.editors.push(created);
                            this.newEmail = '';
                            this.error    = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not add editor.';
                        }
                    },

                    async removeEditor(editor) {
                        if (!confirm(`Remove ${editor.display_name} as an editor?`)) {
                            return;
                        }
                        try {
                            await editorResource.delete(editor.page_editor_id);
                            this.editors = this.editors.filter(
                                e => e.page_editor_id !== editor.page_editor_id
                            );
                            this.error = '';
                        } catch (e) {
                            this.error = e.message ?? 'Could not remove editor.';
                        }
                    },
                }));
            });
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3/dist/cdn.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
        <?php
        parent::generateFooter();
    }
}
