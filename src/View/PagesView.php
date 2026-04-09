<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the public page directory.
 *
 * Data properties:
 *   - $pages         array  Public pages with owner_name, as plain arrays.
 *   - $current_page  int    The current page number (1-based).
 *   - $has_prev      bool   Whether a previous page exists.
 *   - $has_next      bool   Whether a next page exists.
 *
 * @package Dealnews\Indexera\View
 */
class PagesView extends BaseView {

    /**
     * Public pages for the current page of results.
     *
     * Each entry is a plain array with page fields plus owner_name.
     *
     * @var array
     */
    public array $pages = [];

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
        $this->document->title = 'Page Directory — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the page directory listing.
     *
     * @return void
     */
    protected function generateBody(): void {
        ?>
        <div class="container" style="margin-top: 2rem;">
            <div class="row">
                <div class="col s12">
                    <h4>Page Directory</h4>
                </div>
            </div>

            <?php if (empty($this->pages)): ?>
            <div class="row">
                <div class="col s12">
                    <p class="grey-text">No public pages yet.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($this->pages as $page): ?>
                <?php
                    $title       = htmlspecialchars($page['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $owner       = htmlspecialchars($page['owner_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $description = $page['description'] !== null
                        ? htmlspecialchars($page['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        : '';
                    $url_prefix  = !empty($page['group_slug'])
                        ? $page['group_slug']
                        : $page['owner_name'];
                    $href        = '/' . rawurlencode($url_prefix) . '/' . rawurlencode($page['slug']);
                    $date        = $page['created_at'] !== ''
                        ? date('M j, Y', strtotime($page['created_at']))
                        : '';
                ?>
                <div class="col s12 m6 l4">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">
                                <a href="<?= $href ?>"><?= $title ?></a>
                            </span>
                            <?php if ($description !== ''): ?>
                            <p class="grey-text"><?= $description ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-action">
                            <span class="grey-text text-darken-1">
                                by <?= $owner ?>
                            </span>
                            <?php if ($date !== ''): ?>
                            <span class="grey-text right"><?= $date ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($this->has_prev || $this->has_next): ?>
            <div class="row">
                <div class="col s12" style="display: flex; justify-content: space-between; margin-bottom: 2rem;">
                    <?php if ($this->has_prev): ?>
                    <a href="/pages?page=<?= $this->current_page - 1 ?>"
                       class="btn waves-effect waves-light">
                        <i class="material-icons left">chevron_left</i>Previous
                    </a>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>

                    <?php if ($this->has_next): ?>
                    <a href="/pages?page=<?= $this->current_page + 1 ?>"
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
