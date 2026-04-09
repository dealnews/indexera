<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Model;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\PageEditor;
use Dealnews\Indexera\Data\PageSubscription;
use Dealnews\Indexera\Data\Settings;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Model\PageViewModel;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for PageViewModel.
 *
 * Covers group-page vs user-page resolution, visibility rules,
 * membership checks, and subscription status.
 *
 * @package Dealnews\Indexera\Tests\Model
 */
#[AllowMockObjectsWithoutExpectations]
class PageViewModelTest extends TestCase {

    /**
     * Builds a PageViewModel with a mock repository injected.
     *
     * @param string $username
     * @param string $slug
     * @param int    $current_user_id
     *
     * @return array{PageViewModel, MockObject}
     */
    protected function makeModel(
        string $username = 'alice',
        string $slug = 'my-page',
        int $current_user_id = 0
    ): array {
        $repo  = $this->makeRepositoryMock();
        $model = new PageViewModel([
            'username'        => $username,
            'slug'            => $slug,
            'current_user_id' => $current_user_id,
        ]);

        $this->setProperty($model, 'repository', $repo);

        return [$model, $repo];
    }

    /**
     * Builds a Settings object with public_pages enabled.
     *
     * @return Settings
     */
    protected function makePublicSettings(): Settings {
        $settings               = new Settings();
        $settings->public_pages = true;
        return $settings;
    }

    // --- Public-pages-disabled gate ---

    public function test_login_required_when_public_pages_off_and_guest(): void {
        [$model, $repo] = $this->makeModel('alice', 'my-page', 0);

        $settings               = new Settings();
        $settings->public_pages = false;

        $repo->method('get')->willReturn($settings);

        $result = $model->getData();

        $this->assertTrue($result['login_required']);
        $this->assertSame('/alice/my-page', $result['next_url']);
    }

    // --- Group page resolution ---

    public function test_resolves_group_page_when_slug_matches_group(): void {
        [$model, $repo] = $this->makeModel('mygroup', 'project-links', 1);

        $group           = new Group();
        $group->group_id = 7;
        $group->slug     = 'mygroup';

        $page            = new Page();
        $page->page_id   = 20;
        $page->user_id   = 2;
        $page->group_id  = 7;
        $page->is_public = true;

        $page_owner          = new User();
        $page_owner->user_id = 2;

        $member                  = new GroupMember();
        $member->group_member_id = 1;

        $repo->method('get')
             ->willReturnMap([
                 ['Settings', 1, $this->makePublicSettings()],
                 ['User',     2, $page_owner],
             ]);

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($group, $page, $member) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 if ($name === 'Page') {
                     return [20 => $page];
                 }
                 if ($name === 'GroupMember') {
                     return [1 => $member];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertTrue($result['is_group_page']);
        $this->assertSame($group, $result['group']);
    }

    public function test_group_page_not_found_when_slug_missing(): void {
        [$model, $repo] = $this->makeModel('mygroup', 'no-such-page', 1);

        $group           = new Group();
        $group->group_id = 7;
        $group->slug     = 'mygroup';

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($group) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 return [];   // page not found
             });

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_group_only_page_requires_login_for_guest(): void {
        [$model, $repo] = $this->makeModel('mygroup', 'secret', 0);

        $group           = new Group();
        $group->group_id = 7;
        $group->slug     = 'mygroup';

        $page            = new Page();
        $page->page_id   = 20;
        $page->user_id   = 2;
        $page->group_id  = 7;
        $page->is_public = false;

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($group, $page) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 if ($name === 'Page') {
                     return [20 => $page];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertTrue($result['login_required']);
    }

    public function test_group_only_page_returns_not_found_for_logged_in_non_member(): void {
        [$model, $repo] = $this->makeModel('mygroup', 'secret', 5);

        $group           = new Group();
        $group->group_id = 7;
        $group->slug     = 'mygroup';

        $page            = new Page();
        $page->page_id   = 20;
        $page->user_id   = 2;
        $page->group_id  = 7;
        $page->is_public = false;

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($group, $page) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 if ($name === 'Page') {
                     return [20 => $page];
                 }
                 return [];   // not a member
             });

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    // --- User page resolution ---

    public function test_falls_back_to_user_lookup_when_no_group_matches(): void {
        [$model, $repo] = $this->makeModel('alice', 'my-links', 0);

        $owner           = new User();
        $owner->user_id  = 3;
        $owner->display_name = 'alice';

        $page            = new Page();
        $page->page_id   = 15;
        $page->user_id   = 3;
        $page->group_id  = 0;
        $page->is_public = true;

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($owner, $page) {
                 if ($name === 'Group') {
                     return [];  // no group with this slug
                 }
                 if ($name === 'User') {
                     return [3 => $owner];
                 }
                 if ($name === 'Page') {
                     return [15 => $page];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertFalse($result['is_group_page']);
        $this->assertSame($owner, $result['page_owner']);
    }

    public function test_user_page_not_found_when_user_missing(): void {
        [$model, $repo] = $this->makeModel('nobody', 'page', 0);

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')->willReturn([]);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_private_user_page_not_visible_to_guest(): void {
        [$model, $repo] = $this->makeModel('alice', 'private', 0);

        $owner           = new User();
        $owner->user_id  = 3;

        $page            = new Page();
        $page->page_id   = 15;
        $page->user_id   = 3;
        $page->group_id  = 0;
        $page->is_public = false;

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($owner, $page) {
                 if ($name === 'Group') {
                     return [];
                 }
                 if ($name === 'User') {
                     return [3 => $owner];
                 }
                 if ($name === 'Page') {
                     return [15 => $page];
                 }
                 return [];  // no editor record
             });

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_private_user_page_visible_to_editor(): void {
        [$model, $repo] = $this->makeModel('alice', 'private', 5);

        $owner           = new User();
        $owner->user_id  = 3;

        $page            = new Page();
        $page->page_id   = 15;
        $page->user_id   = 3;
        $page->group_id  = 0;
        $page->is_public = false;

        $editor_record                   = new PageEditor();
        $editor_record->page_editor_id   = 1;

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($owner, $page, $editor_record) {
                 if ($name === 'Group') {
                     return [];
                 }
                 if ($name === 'User') {
                     return [3 => $owner];
                 }
                 if ($name === 'Page') {
                     return [15 => $page];
                 }
                 if ($name === 'PageEditor') {
                     return [1 => $editor_record];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertTrue($result['is_editor']);
    }

    public function test_subscription_id_populated_when_subscribed(): void {
        [$model, $repo] = $this->makeModel('alice', 'my-links', 5);

        $owner           = new User();
        $owner->user_id  = 3;

        $page            = new Page();
        $page->page_id   = 15;
        $page->user_id   = 3;
        $page->group_id  = 0;
        $page->is_public = true;

        $sub                           = new PageSubscription();
        $sub->page_subscription_id     = 99;

        $repo->method('get')->willReturn($this->makePublicSettings());
        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($owner, $page, $sub) {
                 if ($name === 'Group') {
                     return [];
                 }
                 if ($name === 'User') {
                     return [3 => $owner];
                 }
                 if ($name === 'Page') {
                     return [15 => $page];
                 }
                 if ($name === 'PageSubscription') {
                     return [99 => $sub];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertSame(99, $result['subscription_id']);
    }
}
