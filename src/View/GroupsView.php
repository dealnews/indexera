<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the public group directory.
 *
 * Data properties:
 *   - $groups        array  Groups with member_count, as plain arrays.
 *   - $current_page  int    The current page number (1-based).
 *   - $has_prev      bool   Whether a previous page exists.
 *   - $has_next      bool   Whether a next page exists.
 *
 * @package Dealnews\Indexera\View
 */
class GroupsView extends BaseView {

    /**
     * Groups for the current page of results.
     *
     * @var array
     */
    public array $groups = [];

    /**
     * The current page number (1-based).
     *
     * @var int
     */
    public int $current_page = 1;

    /**
     * Whether a previous page of results exists.
     *
     * @var bool
     */
    public bool $has_prev = false;

    /**
     * Whether a next page of results exists.
     *
     * @var bool
     */
    public bool $has_next = false;

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Groups — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the group directory listing.
     *
     * @return void
     */
    protected function generateBody(): void {
        ?>
        <div class="container" style="margin-top: 2rem;">
            <div class="row valign-wrapper">
                <div class="col s6">
                    <h4>Groups</h4>
                </div>
                <?php if ($this->current_user !== null): ?>
                <div class="col s6 right-align">
                    <a href="/groups/create"
                       class="btn waves-effect waves-light">
                        <i class="material-icons left">group_add</i>New group
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($this->groups)): ?>
            <div class="row">
                <div class="col s12">
                    <p class="grey-text">No groups yet.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($this->groups as $group): ?>
                <?php
                    $name         = htmlspecialchars($group['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $description  = $group['description'] !== null
                        ? htmlspecialchars($group['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        : '';
                    $href         = '/groups/' . rawurlencode($group['slug']);
                    $member_count = (int)$group['member_count'];
                ?>
                <div class="col s12 m6 l4">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">
                                <a href="<?= $href ?>"><?= $name ?></a>
                            </span>
                            <?php if ($description !== ''): ?>
                            <p class="grey-text"><?= $description ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-action grey-text text-darken-1">
                            <?= $member_count ?>
                            <?= $member_count === 1 ? 'member' : 'members' ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($this->has_prev || $this->has_next): ?>
            <div class="row">
                <div class="col s12"
                     style="display: flex; justify-content: space-between; margin-bottom: 2rem;">
                    <?php if ($this->has_prev): ?>
                    <a href="/groups?page=<?= $this->current_page - 1 ?>"
                       class="btn waves-effect waves-light">
                        <i class="material-icons left">chevron_left</i>Previous
                    </a>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>

                    <?php if ($this->has_next): ?>
                    <a href="/groups?page=<?= $this->current_page + 1 ?>"
                       class="btn waves-effect waves-light">
                        Next<i class="material-icons right">chevron_right</i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
