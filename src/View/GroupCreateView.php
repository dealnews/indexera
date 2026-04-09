<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the group creation form.
 *
 * Data properties:
 *   - $error  string  Validation error message, or empty string.
 *
 * @package Dealnews\Indexera\View
 */
class GroupCreateView extends BaseView {

    /**
     * Validation error message, if any.
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
        $this->document->title = 'New Group — ' . $this->getSiteTitle();
    }

    /**
     * Outputs the create group form.
     *
     * @return void
     */
    protected function generateBody(): void {
        ?>
        <div class="container" style="margin-top: 2rem;">
            <div class="row">
                <div class="col s12 m8 offset-m2 l6 offset-l3">
                    <h4>Create a group</h4>

                    <?php if ($this->error !== ''): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <?= htmlspecialchars($this->error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-content">
                            <form method="POST" action="/groups/create">
                                <?= $this->csrfField() ?>

                                <div class="input-field">
                                    <input type="text" id="name" name="name"
                                           maxlength="255" required
                                           value="<?= htmlspecialchars(
                                               $_POST['name'] ?? '',
                                               ENT_QUOTES | ENT_SUBSTITUTE,
                                               'UTF-8'
                                           ) ?>">
                                    <label for="name">Group name</label>
                                </div>

                                <div class="input-field">
                                    <textarea id="description" name="description"
                                              class="materialize-textarea"><?= htmlspecialchars(
                                        $_POST['description'] ?? '',
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?></textarea>
                                    <label for="description">Description (optional)</label>
                                </div>

                                <div style="margin-top: 1rem;">
                                    <button type="submit"
                                            class="btn waves-effect waves-light">
                                        <i class="material-icons left">group_add</i>Create group
                                    </button>
                                    <a href="/groups"
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
