<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\NotFoundView;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles all unmatched routes with a 404 response.
 *
 * @package Dealnews\Indexera\Controller
 */
class NotFoundController extends BaseController {

    /**
     * Sets the 404 status code then runs the standard pipeline.
     *
     * @return void
     */
    public function handleRequest(): void {
        http_response_code(404);
        parent::handleRequest();
    }

    /**
     * No models needed for the 404 page.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * Returns the HTML responder for the 404 page.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(NotFoundView::class);
    }
}
