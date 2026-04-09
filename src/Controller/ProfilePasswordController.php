<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\ProfilePasswordView;

/**
 * Renders the change-password form.
 *
 * @package Dealnews\Indexera\Controller
 */
class ProfilePasswordController extends BaseController {

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
     * Returns the password form responder.
     *
     * @return HtmlResponder
     */
    protected function getResponder(): HtmlResponder {
        return new HtmlResponder(ProfilePasswordView::class);
    }
}
