<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Admin;

use Dealnews\Indexera\Action\Admin\SaveSettingsAction;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Admin\SettingsView;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles the admin settings form submission.
 *
 * @package Dealnews\Indexera\Controller\Admin
 */
class SettingsPostController extends BaseAdminController {

    /**
     * Filters settings fields from the POST body. Checkbox fields that are
     * unchecked are absent from POST entirely, so they are defaulted to '0'
     * after standard filtering.
     *
     * @param array $filters
     *
     * @return void
     */
    protected function filterInput(array $filters): void {
        parent::filterInput($filters);
        $this->inputs['public_pages']       = $this->inputs['public_pages'] ?? '0';
        $this->inputs['allow_registration'] = $this->inputs['allow_registration'] ?? '0';
    }

    /**
     * Filters settings fields from the POST body.
     *
     * @return array
     */
    protected function getFilters(): array {
        return [
            INPUT_POST => [
                'site_title'         => FILTER_DEFAULT,
                'nav_heading'        => FILTER_DEFAULT,
                'public_pages'       => FILTER_DEFAULT,
                'allow_registration' => FILTER_DEFAULT,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getRequestActions(): array {
        return [SaveSettingsAction::class];
    }

    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(SettingsView::class);
    }
}
