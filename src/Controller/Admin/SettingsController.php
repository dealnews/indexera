<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Admin;

use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\Admin\SettingsView;
use PageMill\MVC\ResponderAbstract;

/**
 * Renders the admin settings form.
 *
 * Settings are already loaded into $this->data['settings'] by
 * BaseController, so no additional model is needed.
 *
 * @package Dealnews\Indexera\Controller\Admin
 */
class SettingsController extends BaseAdminController {

    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        $error_map = [
            'site_title'  => 'Site title is required.',
            'nav_heading' => 'Nav heading is required.',
        ];
        $error_key              = (string)($_GET['error'] ?? '');
        $this->data['error']    = $error_map[$error_key] ?? '';
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(SettingsView::class);
    }
}
