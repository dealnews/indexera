<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Model;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\Settings;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Model\PagesModel;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for PagesModel.
 *
 * Covers the public-pages gate, pagination logic, and owner/group
 * display name hydration with N+1 cache behaviour.
 *
 * @package Dealnews\Indexera\Tests\Model
 */
#[AllowMockObjectsWithoutExpectations]
class PagesModelTest extends TestCase {

    /**
     * Builds a PagesModel with a mock repository injected.
     *
     * @param int $current_user_id
     * @param int $page
     *
     * @return array{PagesModel, MockObject}
     */
    protected function makeModel(
        int $current_user_id = 0,
        int $page = 1
    ): array {
        $repo  = $this->makeRepositoryMock();
        $model = new PagesModel([
            'current_user_id' => $current_user_id,
            'page'            => $page,
        ]);
        $this->setProperty($model, 'repository', $repo);
        return [$model, $repo];
    }

    /**
     * Builds a Settings object.
     *
     * @param bool $public_pages
     *
     * @return Settings
     */
    protected function makeSettings(bool $public_pages = true): Settings {
        $settings               = new Settings();
        $settings->public_pages = $public_pages;
        return $settings;
    }

    /**
     * Builds an array of Page objects for pagination tests.
     *
     * @param int $count
     * @param int $user_id
     *
     * @return array<int, Page>
     */
    protected function makePages(int $count, int $user_id = 1): array {
        $pages = [];
        for ($i = 1; $i <= $count; $i++) {
            $page            = new Page();
            $page->page_id   = $i;
            $page->user_id   = $user_id;
            $page->group_id  = 0;
            $page->title     = "Page $i";
            $page->is_public = true;
            $pages[$i]       = $page;
        }
        return $pages;
    }

    public function test_login_required_when_public_pages_off_and_guest(): void {
        [$model, $repo] = $this->makeModel(0);

        $repo->method('get')->willReturn($this->makeSettings(false));

        $result = $model->getData();

        $this->assertTrue($result['login_required']);
    }

    public function test_logged_in_user_bypasses_public_pages_gate(): void {
        [$model, $repo] = $this->makeModel(1);

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) {
                 if ($name === 'Settings') {
                     return $this->makeSettings(false);
                 }
                 // User lookups return a basic user.
                 $u               = new User();
                 $u->user_id      = $id;
                 $u->display_name = 'alice';
                 return $u;
             });
        $repo->method('find')->willReturn([]);

        $result = $model->getData();

        $this->assertFalse($result['login_required']);
    }

    public function test_pages_list_is_returned_with_owner_name(): void {
        [$model, $repo] = $this->makeModel(0);

        $owner               = new User();
        $owner->user_id      = 1;
        $owner->display_name = 'alice';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($owner) {
                 if ($name === 'Settings') {
                     return null;  // no settings row → gate skipped
                 }
                 return $owner;
             });
        $repo->method('find')->willReturn($this->makePages(3));

        $result = $model->getData();

        $this->assertFalse($result['login_required']);
        $this->assertCount(3, $result['pages']);
        $this->assertSame('alice', $result['pages'][0]['owner_name']);
    }

    public function test_has_next_true_when_extra_page_detected(): void {
        // PER_PAGE = 24; returning 25 triggers has_next = true and only 24 are returned.
        [$model, $repo] = $this->makeModel(0);

        $owner               = new User();
        $owner->user_id      = 1;
        $owner->display_name = 'alice';

        $repo->method('get')
             ->willReturnCallback(function (string $name) use ($owner) {
                 if ($name === 'Settings') {
                     return null;
                 }
                 return $owner;
             });
        $repo->method('find')->willReturn($this->makePages(25));

        $result = $model->getData();

        $this->assertTrue($result['has_next']);
        $this->assertCount(24, $result['pages']);
    }

    public function test_has_next_false_when_fewer_than_per_page(): void {
        [$model, $repo] = $this->makeModel(0);

        $owner               = new User();
        $owner->user_id      = 1;
        $owner->display_name = 'alice';

        $repo->method('get')
             ->willReturnCallback(function (string $name) use ($owner) {
                 if ($name === 'Settings') {
                     return null;
                 }
                 return $owner;
             });
        $repo->method('find')->willReturn($this->makePages(5));

        $result = $model->getData();

        $this->assertFalse($result['has_next']);
        $this->assertCount(5, $result['pages']);
    }

    public function test_has_prev_true_on_second_page(): void {
        [$model, $repo] = $this->makeModel(0, 2);

        $repo->method('get')->willReturn(null);
        $repo->method('find')->willReturn([]);

        $result = $model->getData();

        $this->assertTrue($result['has_prev']);
        $this->assertSame(2, $result['current_page']);
    }

    public function test_group_slug_populated_for_group_page(): void {
        [$model, $repo] = $this->makeModel(0);

        $page            = new Page();
        $page->page_id   = 1;
        $page->user_id   = 1;
        $page->group_id  = 5;
        $page->title     = 'Group Page';
        $page->is_public = true;

        $group       = new Group();
        $group->slug = 'mygroup';

        $owner               = new User();
        $owner->user_id      = 1;
        $owner->display_name = 'alice';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($owner, $group) {
                 if ($name === 'Settings') {
                     return null;
                 }
                 if ($name === 'Group') {
                     return $group;
                 }
                 return $owner;
             });
        $repo->method('find')->willReturn([1 => $page]);

        $result = $model->getData();

        $this->assertSame('mygroup', $result['pages'][0]['group_slug']);
    }
}
