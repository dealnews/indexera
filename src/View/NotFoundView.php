<?php

declare(strict_types=1);

namespace Dealnews\Indexera\View;

/**
 * Renders the 404 not found page.
 *
 * @package Dealnews\Indexera\View
 */
class NotFoundView extends BaseView {

    /**
     * Sets the page title.
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Page not found — Indexera';
    }

    /**
     * Outputs the 404 content.
     *
     * @return void
     */
    protected function generateBody(): void {
        ?>
        <div class="container">
            <div class="row">
                <div class="col s12 center-align" style="padding-top: 4rem;">
                    <i class="material-icons" style="font-size: 5rem;">search_off</i>
                    <h3>Page not found</h3>
                    <p class="flow-text">The page you are looking for does not exist.</p>
                    <a href="/" class="btn waves-effect waves-light">
                        <i class="material-icons left">home</i>Go home
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
