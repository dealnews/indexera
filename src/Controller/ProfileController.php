<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\ProfileView;

/**
 * Renders the authenticated user's profile edit page.
 *
 * @package Dealnews\Indexera\Controller
 */
class ProfileController extends BaseController {

    /**
     * Requires authentication.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * Returns the profile HTML responder.
     *
     * @return HtmlResponder
     */
    protected function getResponder(): HtmlResponder {
        return new HtmlResponder(ProfileView::class);
    }
}
