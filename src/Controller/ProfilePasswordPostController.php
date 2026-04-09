<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Action\Profile\ChangePasswordAction;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\ProfilePasswordView;

/**
 * Handles the change-password POST form submission.
 *
 * @package Dealnews\Indexera\Controller
 */
class ProfilePasswordPostController extends BaseController {

    /**
     * Requires authentication.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * Returns the request actions for this controller.
     *
     * @return array
     */
    protected function getRequestActions(): array {
        return [ChangePasswordAction::class];
    }

    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * Returns the password form responder (re-renders on error).
     *
     * @return HtmlResponder
     */
    protected function getResponder(): HtmlResponder {
        return new HtmlResponder(ProfilePasswordView::class);
    }

    /**
     * Injects the current user's ID into inputs before actions run.
     *
     * @return void
     */
    protected function buildModels(): void {
        $this->inputs['user_id'] = $this->current_user->user_id ?? 0;
        parent::buildModels();
    }
}
