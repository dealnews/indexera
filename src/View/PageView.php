<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\User;

/**
 * Renders a user's public link page.
 *
 * Data properties:
 *   - $page             Page      The page being viewed.
 *   - $page_owner       User      The user who owns the page.
 *   - $sections         array     Sections with their links.
 *   - $subscription_id  int|null  The current user's subscription ID, or null.
 *   - $is_editor        bool      Whether the current user is an editor of this page.
 *
 * @package Dealnews\Indexera\View
 */
class PageView extends BaseView {

    /**
     * The page being viewed.
     *
     * @var Page|null
     */
    public ?Page $page = null;

    /**
     * The user who owns the page.
     *
     * @var User|null
     */
    public ?User $page_owner = null;

    /**
     * The group this page belongs to, or null for personal pages.
     *
     * @var Group|null
     */
    public ?Group $group = null;

    /**
     * Whether this is a group page.
     *
     * @var bool
     */
    public bool $is_group_page = false;

    /**
     * Whether the current user is a member of the page's group.
     *
     * @var bool
     */
    public bool $is_member = false;

    /**
     * Sections with their links.
     *
     * Each entry is ['section' => Section, 'links' => Link[]].
     *
     * @var array
     */
    public array $sections = [];

    /**
     * The current user's subscription ID for this page, or null.
     *
     * @var int|null
     */
    public ?int $subscription_id = null;

    /**
     * Whether the current user is an editor of this page.
     *
     * @var bool
     */
    public bool $is_editor = false;

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $title = $this->page !== null
            ? htmlspecialchars($this->page->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : 'Page';

        $this->document->title = $title . ' — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the page content.
     *
     * @return void
     */
    protected function generateBody(): void {
        $title       = htmlspecialchars($this->page->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $description = $this->page->description !== null
            ? htmlspecialchars($this->page->description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';
        $owner_name  = $this->page_owner !== null
            ? htmlspecialchars($this->page_owner->display_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';
        $group_name  = $this->group !== null
            ? htmlspecialchars($this->group->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';
        $group_href  = $this->group !== null
            ? '/groups/' . rawurlencode($this->group->slug)
            : '';

        $show_subscribe    = $this->current_user !== null &&
                             $this->current_user->user_id !== $this->page->user_id &&
                             !$this->is_editor;
        $show_edit         = $this->current_user !== null &&
                             ($this->current_user->user_id === $this->page->user_id ||
                              $this->is_editor);
        $subscription_json = json_encode($this->subscription_id);
        $page_id           = $this->page->page_id;
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <div class="row valign-wrapper" style="margin-top: 2rem;">
                        <div class="col s12 m8">
                            <h3 style="margin: 0;"><?= $title ?></h3>
                            <?php if ($this->is_group_page && $group_name !== ''): ?>
                            <p class="grey-text" style="margin: 0;">
                                in <a href="<?= $group_href ?>"><?= $group_name ?></a>
                                &middot; by <?= $owner_name ?>
                            </p>
                            <?php else: ?>
                            <p class="grey-text" style="margin: 0;">by <?= $owner_name ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($show_subscribe): ?>
                        <div class="col s12 m4 right-align"
                             x-data="subscriptionManager(<?= $subscription_json ?>, <?= $page_id ?>)">
                            <button type="button"
                                    x-show="!subscriptionId"
                                    @click="subscribe()"
                                    class="btn waves-effect waves-light">
                                <i class="material-icons left">bookmark_add</i>Subscribe
                            </button>
                            <button type="button"
                                    x-show="subscriptionId"
                                    @click="unsubscribe()"
                                    class="btn waves-effect waves-light orange"
                                    x-cloak>
                                <i class="material-icons left">bookmark_remove</i>Unsubscribe
                            </button>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_edit): ?>
                        <div class="col s12 m4 right-align">
                            <a href="/pages/<?= $page_id ?>/edit"
                               class="btn waves-effect waves-light">
                                <i class="material-icons left">edit</i>Edit page
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php foreach ($this->sections as $entry): ?>
                <div class="col s12 m4">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">
                                <?= htmlspecialchars($entry['section']->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </span>
                            <?php if (!empty($entry['links'])): ?>
                            <ul class="collection">
                                <?php foreach ($entry['links'] as $link): ?>
                                <li class="collection-item">
                                    <a href="<?= htmlspecialchars($link->url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                       rel="noopener noreferrer"
                                       target="_blank">
                                        <?= htmlspecialchars($link->label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($description !== ''): ?>
            <div class="row">
                <div class="col s12">
                    <p class="grey-text center-align"><?= $description ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Outputs the subscribe/unsubscribe Alpine.js component and closes the document.
     *
     * @return void
     */
    protected function generateFooter(): void {
        if ($this->current_user !== null &&
            $this->page !== null &&
            $this->current_user->user_id !== $this->page->user_id)
        {
            ?>
            <script type="module">
                import { DataMapperClient } from 'https://cdn.jsdelivr.net/npm/@dealnews/data-mapper-client/+esm';

                const client               = new DataMapperClient({ baseUrl: window.location.origin });
                const subscriptionResource = client.resource('PageSubscription');

                document.addEventListener('alpine:init', () => {
                    Alpine.data('subscriptionManager', (initialSubscriptionId, pageId) => ({
                        subscriptionId: initialSubscriptionId,

                        async subscribe() {
                            try {
                                const created       = await subscriptionResource.create({
                                    page_id: pageId,
                                });
                                this.subscriptionId = created.page_subscription_id;
                            } catch (e) {
                                M.toast({ html: e.message ?? 'Could not subscribe.' });
                            }
                        },

                        async unsubscribe() {
                            try {
                                await subscriptionResource.delete(this.subscriptionId);
                                this.subscriptionId = null;
                            } catch (e) {
                                M.toast({ html: e.message ?? 'Could not unsubscribe.' });
                            }
                        },
                    }));
                });
            </script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
            <?php
        }
        parent::generateFooter();
    }
}
