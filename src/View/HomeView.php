<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the public home page for guests.
 *
 * @package Dealnews\Indexera\View
 */
class HomeView extends BaseView {

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = $this->getSiteTitle() . ' — Share your links';
    }

    /**
     * Outputs the welcome content.
     *
     * @return void
     */
    protected function generateBody(): void {
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12 center-align" style="padding-top: 4rem;">
                    <i class="material-icons" style="font-size: 5rem;">link</i>
                    <h3>Share your links</h3>
                    <p class="flow-text">
                        Indexera lets you create pages of links to share with the world.
                    </p>
                    <?php if ($this->settings?->allow_registration ?? true): ?>
                    <a href="/register"
                       class="btn-large waves-effect waves-light">
                        Create an account
                        <i class="material-icons right">person_add</i>
                    </a>
                    <?php endif; ?>
                    <a href="/login"
                       class="btn-large waves-effect waves-light grey lighten-1 black-text"
                       style="margin-left: 1rem;">
                        Log in
                        <i class="material-icons right">login</i>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
