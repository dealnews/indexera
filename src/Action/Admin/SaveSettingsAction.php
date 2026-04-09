<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Action\Admin;

use Dealnews\Indexera\Repository;
use DealNews\DataMapper\Repository as BaseRepository;
use PageMill\HTTP\Response;
use PageMill\MVC\ActionAbstract;

/**
 * Persists the site title and nav heading settings.
 *
 * Redirects to /admin/settings on completion.
 *
 * @package Dealnews\Indexera\Action\Admin
 */
class SaveSettingsAction extends ActionAbstract {

    /**
     * New site title value.
     *
     * @var string
     */
    public string $site_title = '';

    /**
     * New nav heading value.
     *
     * @var string
     */
    public string $nav_heading = '';

    /**
     * Whether guests may view pages. Checkbox sends "1" when checked,
     * nothing when unchecked — so we default to "0" and treat "1" as true.
     *
     * @var string
     */
    public string $public_pages = '0';

    /**
     * Whether new user registration is open. Checkbox sends "1" when checked,
     * nothing when unchecked — so we default to "0" and treat "1" as true.
     *
     * @var string
     */
    public string $allow_registration = '0';

    /**
     * Optional URL for a custom navigation bar icon.
     *
     * @var string
     */
    public string $nav_icon_url = '';

    /**
     * Repository instance. Injected for testing; production code creates its own.
     *
     * @var BaseRepository|null
     */
    protected ?BaseRepository $repository = null;

    /**
     * Saves the settings and redirects.
     *
     * @param array $data Unused.
     *
     * @return null
     */
    public function doAction(array $data = []): mixed {
        $repository = $this->repository ?? Repository::init();
        $settings   = $repository->get('Settings', 1);

        if ($settings === null) {
            $settings = $repository->new('Settings');
        }

        if ($this->site_title === '') {
            $this->doRedirect('/admin/settings?error=site_title');
            return null;
        }

        if ($this->nav_heading === '') {
            $this->doRedirect('/admin/settings?error=nav_heading');
            return null;
        }

        $settings->site_title         = $this->site_title;
        $settings->nav_heading        = $this->nav_heading;
        $settings->public_pages       = $this->public_pages === '1';
        $settings->allow_registration = $this->allow_registration === '1';
        $settings->nav_icon_url       = $this->nav_icon_url !== '' ? $this->nav_icon_url : null;

        $repository->save('Settings', $settings);

        $this->doRedirect('/admin/settings');

        return null;
    }

    /**
     * Redirects the browser. Extracted to allow test overrides.
     *
     * @param string $url The destination URL.
     *
     * @return void
     */
    protected function doRedirect(string $url): void {
        Response::init()->redirect($url);
    }
}
