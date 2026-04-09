<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Action\Admin;

use Dealnews\Indexera\Action\Admin\SaveSettingsAction;
use Dealnews\Indexera\Data\Settings;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Subclass that suppresses the redirect so tests don't call exit.
 */
class TestableSaveSettingsAction extends SaveSettingsAction {
    public string $redirected_to = '';

    protected function doRedirect(string $url): void {
        $this->redirected_to = $url;
    }
}

/**
 * Tests for SaveSettingsAction.
 *
 * @package Dealnews\Indexera\Tests\Action\Admin
 */
#[AllowMockObjectsWithoutExpectations]
class SaveSettingsActionTest extends TestCase {

    /**
     * Builds a SaveSettingsAction with a mocked repository injected.
     *
     * @return array{TestableSaveSettingsAction, \PHPUnit\Framework\MockObject\MockObject}
     */
    protected function makeAction(): array {
        $repo   = $this->makeRepositoryMock();
        $action = new TestableSaveSettingsAction([]);
        $this->setProperty($action, 'repository', $repo);
        return [$action, $repo];
    }

    /**
     * Builds a default Settings object for use in stubs.
     *
     * @return Settings
     */
    protected function makeSettings(): Settings {
        $settings               = new Settings();
        $settings->settings_id  = 1;
        $settings->site_title   = 'Old Title';
        $settings->nav_heading  = 'Old Heading';
        $settings->public_pages = true;
        return $settings;
    }

    public function test_empty_site_title_redirects_with_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title  = '';
        $action->nav_heading = 'Indexera';

        $repo->method('get')->willReturn($this->makeSettings());

        $action->doAction();

        $this->assertSame('/admin/settings?error=site_title', $action->redirected_to);
    }

    public function test_empty_nav_heading_redirects_with_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title  = 'My Site';
        $action->nav_heading = '';

        $repo->method('get')->willReturn($this->makeSettings());

        $action->doAction();

        $this->assertSame('/admin/settings?error=nav_heading', $action->redirected_to);
    }

    public function test_saves_all_fields_and_redirects(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title         = 'My Site';
        $action->nav_heading        = 'My Nav';
        $action->public_pages       = '1';
        $action->allow_registration = '0';
        $action->nav_icon_url       = 'https://example.com/icon.png';

        $settings = $this->makeSettings();
        $repo->method('get')->willReturn($settings);
        $repo->method('save')->willReturn($settings);

        $action->doAction();

        $this->assertSame('/admin/settings', $action->redirected_to);
        $this->assertSame('My Site', $settings->site_title);
        $this->assertSame('My Nav', $settings->nav_heading);
        $this->assertTrue($settings->public_pages);
        $this->assertFalse($settings->allow_registration);
        $this->assertSame('https://example.com/icon.png', $settings->nav_icon_url);
    }

    public function test_public_pages_checkbox_unchecked_saves_false(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title   = 'My Site';
        $action->nav_heading  = 'My Nav';
        $action->public_pages = '0';

        $settings = $this->makeSettings();
        $repo->method('get')->willReturn($settings);
        $repo->method('save')->willReturn($settings);

        $action->doAction();

        $this->assertFalse($settings->public_pages);
    }

    public function test_allow_registration_checkbox_checked_saves_true(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title         = 'My Site';
        $action->nav_heading        = 'My Nav';
        $action->allow_registration = '1';

        $settings = $this->makeSettings();
        $repo->method('get')->willReturn($settings);
        $repo->method('save')->willReturn($settings);

        $action->doAction();

        $this->assertTrue($settings->allow_registration);
    }

    public function test_empty_nav_icon_url_saves_null(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title   = 'My Site';
        $action->nav_heading  = 'My Nav';
        $action->nav_icon_url = '';

        $settings = $this->makeSettings();
        $repo->method('get')->willReturn($settings);
        $repo->method('save')->willReturn($settings);

        $action->doAction();

        $this->assertNull($settings->nav_icon_url);
    }

    public function test_settings_not_in_db_uses_new_object(): void {
        [$action, $repo] = $this->makeAction();
        $action->site_title  = 'My Site';
        $action->nav_heading = 'My Nav';

        $new_settings = new Settings();

        $repo->method('get')->willReturn(null);
        $repo->method('new')->willReturn($new_settings);
        $repo->method('save')->willReturn($new_settings);

        $action->doAction();

        $this->assertSame('/admin/settings', $action->redirected_to);
        $this->assertSame('My Site', $new_settings->site_title);
    }
}
