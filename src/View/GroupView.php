<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

use Dealnews\Indexera\Data\Group;

/**
 * Renders the group home page with its list of pages.
 *
 * Data properties:
 *   - $group      Group  The group being viewed.
 *   - $is_member  bool   Whether the current user is a group member.
 *   - $pages      array  Pages (public for guests, all for members).
 *
 * @package Dealnews\Indexera\View
 */
class GroupView extends BaseView {

    /**
     * The group being viewed.
     *
     * @var Group|null
     */
    public ?Group $group = null;

    /**
     * Whether the current user is a group member.
     *
     * @var bool
     */
    public bool $is_member = false;

    /**
     * Pages in this group visible to the current viewer.
     *
     * @var array
     */
    public array $pages = [];

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $title = $this->group !== null
            ? htmlspecialchars($this->group->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : 'Group';

        $this->document->title = $title . ' — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the group home page.
     *
     * @return void
     */
    protected function generateBody(): void {
        $group_name  = htmlspecialchars($this->group->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $description = $this->group->description !== null
            ? htmlspecialchars($this->group->description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';
        $group_slug  = htmlspecialchars($this->group->slug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <div class="row valign-wrapper" style="margin-top: 2rem;">
                        <div class="col s12 m8">
                            <h3 style="margin: 0;"><?= $group_name ?></h3>
                            <?php if ($description !== ''): ?>
                            <p class="grey-text" style="margin: 0.25rem 0 0;"><?= $description ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($this->is_member): ?>
                        <div class="col s12 m4 right-align">
                            <a href="/groups/<?= $group_slug ?>/manage"
                               class="btn waves-effect waves-light">
                                <i class="material-icons left">group</i>Manage members
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php if (empty($this->pages)): ?>
                <div class="col s12">
                    <p class="grey-text">
                        <?= $this->is_member
                            ? 'No pages yet. Create a page and assign it to this group from your dashboard.'
                            : 'No public pages yet.' ?>
                    </p>
                </div>
                <?php else: ?>
                <?php foreach ($this->pages as $page): ?>
                <?php
                    $title       = htmlspecialchars($page['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $owner       = htmlspecialchars($page['owner_display_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $href        = '/' . $group_slug . '/' . rawurlencode($page['slug']);
                    $is_public   = (bool)$page['is_public'];
                ?>
                <div class="col s12 m6 l4">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">
                                <a href="<?= $href ?>"><?= $title ?></a>
                                <?php if (!$is_public): ?>
                                <span class="new badge grey right"
                                      data-badge-caption="group only"></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="card-action grey-text text-darken-1">
                            by <?= $owner ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
