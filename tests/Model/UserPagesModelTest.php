<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Model;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\PageEditor;
use Dealnews\Indexera\Data\PageSubscription;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Model\UserPagesModel;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for UserPagesModel.
 *
 * Covers all four page sources (owned, editor, subscribed, group),
 * deduplication, filtering, sorting, and the groups list.
 *
 * @package Dealnews\Indexera\Tests\Model
 */
#[AllowMockObjectsWithoutExpectations]
class UserPagesModelTest extends TestCase {

    /**
     * Builds a UserPagesModel with a mock repository injected.
     *
     * @param int $user_id
     *
     * @return array{UserPagesModel, MockObject}
     */
    protected function makeModel(int $user_id = 1): array {
        $repo  = $this->makeRepositoryMock();
        $model = new UserPagesModel(['user_id' => $user_id]);
        $this->setProperty($model, 'repository', $repo);
        return [$model, $repo];
    }

    /**
     * Builds a basic personal Page owned by the given user.
     *
     * @param int    $page_id
     * @param int    $user_id
     * @param string $title
     * @param bool   $is_public
     *
     * @return Page
     */
    protected function makePage(
        int $page_id,
        int $user_id,
        string $title = 'A Page',
        bool $is_public = true
    ): Page {
        $page            = new Page();
        $page->page_id   = $page_id;
        $page->user_id   = $user_id;
        $page->group_id  = 0;
        $page->title     = $title;
        $page->is_public = $is_public;
        return $page;
    }

    /**
     * Configures the repository mock to return empty for all four
     * find() sources (PageEditor, PageSubscription, GroupMember, Page).
     *
     * @param MockObject $repo
     * @param array      $overrides  Map of object name → return value.
     * @param User|null  $owner      User to return for get('User', 1).
     *
     * @return void
     */
    protected function stubEmptySources(
        MockObject $repo,
        array $overrides = [],
        ?User $owner = null
    ): void {
        if ($owner === null) {
            $owner               = new User();
            $owner->user_id      = 1;
            $owner->display_name = 'alice';
        }

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($owner) {
                 if ($name === 'User' && $id === 1) {
                     return $owner;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($overrides) {
                 return $overrides[$name] ?? [];
             });
    }

    // --- Guard ---

    public function test_zero_user_id_returns_empty(): void {
        [$model] = $this->makeModel(0);

        $result = $model->getData();

        $this->assertSame([], $result['pages']);
        $this->assertSame([], $result['groups']);
    }

    // --- Owned pages ---

    public function test_owned_personal_page_is_included(): void {
        [$model, $repo] = $this->makeModel(1);

        $page = $this->makePage(1, 1, 'My Links');

        $this->stubEmptySources($repo, ['Page' => [1 => $page]]);

        $result = $model->getData();

        $this->assertCount(1, $result['pages']);
        $this->assertTrue($result['pages'][0]['is_owned']);
        $this->assertFalse($result['pages'][0]['is_editor']);
    }

    public function test_group_page_excluded_from_personal_owned_list(): void {
        [$model, $repo] = $this->makeModel(1);

        $group_page          = $this->makePage(2, 1);
        $group_page->group_id = 7;  // belongs to a group — must be excluded here

        $this->stubEmptySources($repo, ['Page' => [2 => $group_page]]);

        $result = $model->getData();

        // The group page is skipped in the owned loop (shown via group path instead).
        $this->assertCount(0, $result['pages']);
    }

    // --- Editor pages ---

    public function test_editor_page_is_included(): void {
        [$model, $repo] = $this->makeModel(1);

        $editor_record                  = new PageEditor();
        $editor_record->page_editor_id  = 1;
        $editor_record->page_id         = 20;
        $editor_record->user_id         = 1;

        $edited_page = $this->makePage(20, 99, 'Friend Page');

        $page_owner               = new User();
        $page_owner->user_id      = 99;
        $page_owner->display_name = 'bob';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($edited_page, $page_owner) {
                 if ($name === 'User' && $id === 1) {
                     $u               = new User();
                     $u->user_id      = 1;
                     $u->display_name = 'alice';
                     return $u;
                 }
                 if ($name === 'Page' && $id === 20) {
                     return $edited_page;
                 }
                 if ($name === 'User' && $id === 99) {
                     return $page_owner;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($editor_record) {
                 if ($name === 'PageEditor') {
                     return [1 => $editor_record];
                 }
                 // Owned pages: empty — so no dedup issues.
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(1, $result['pages']);
        $this->assertTrue($result['pages'][0]['is_editor']);
        $this->assertSame('bob', $result['pages'][0]['owner_display_name']);
    }

    public function test_editor_page_not_duplicated_when_already_owned(): void {
        [$model, $repo] = $this->makeModel(1);

        // User owns page 1 AND has an editor record for page 1 (unusual but possible).
        $page = $this->makePage(1, 1, 'My Page');

        $editor_record                 = new PageEditor();
        $editor_record->page_editor_id = 1;
        $editor_record->page_id        = 1;
        $editor_record->user_id        = 1;

        $owner               = new User();
        $owner->user_id      = 1;
        $owner->display_name = 'alice';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($page, $owner) {
                 if ($name === 'User') {
                     return $owner;
                 }
                 if ($name === 'Page') {
                     return $page;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($page, $editor_record) {
                 if ($name === 'Page') {
                     return [1 => $page];
                 }
                 if ($name === 'PageEditor') {
                     return [1 => $editor_record];
                 }
                 return [];
             });

        $result = $model->getData();

        // Page appears only once.
        $this->assertCount(1, $result['pages']);
    }

    // --- Subscribed pages ---

    public function test_subscribed_page_is_included(): void {
        [$model, $repo] = $this->makeModel(1);

        $subscription                         = new PageSubscription();
        $subscription->page_subscription_id   = 5;
        $subscription->page_id                = 30;
        $subscription->user_id                = 1;

        $sub_page = $this->makePage(30, 99, 'Sub Page');

        $sub_owner               = new User();
        $sub_owner->user_id      = 99;
        $sub_owner->display_name = 'carol';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($sub_page, $sub_owner) {
                 if ($name === 'User' && $id === 1) {
                     $u               = new User();
                     $u->user_id      = 1;
                     $u->display_name = 'alice';
                     return $u;
                 }
                 if ($name === 'Page' && $id === 30) {
                     return $sub_page;
                 }
                 if ($name === 'User' && $id === 99) {
                     return $sub_owner;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($subscription) {
                 if ($name === 'PageSubscription') {
                     return [5 => $subscription];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(1, $result['pages']);
        $this->assertSame(5, $result['pages'][0]['subscription_id']);
        $this->assertSame('carol', $result['pages'][0]['owner_display_name']);
    }

    public function test_subscribed_private_non_group_page_is_excluded(): void {
        [$model, $repo] = $this->makeModel(1);

        $subscription                       = new PageSubscription();
        $subscription->page_subscription_id = 5;
        $subscription->page_id              = 30;
        $subscription->user_id              = 1;

        // Private personal page — should be skipped.
        $private_page = $this->makePage(30, 99, 'Secret', false);

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($private_page) {
                 if ($name === 'User' && $id === 1) {
                     $u               = new User();
                     $u->user_id      = 1;
                     $u->display_name = 'alice';
                     return $u;
                 }
                 if ($name === 'Page' && $id === 30) {
                     return $private_page;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($subscription) {
                 if ($name === 'PageSubscription') {
                     return [5 => $subscription];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(0, $result['pages']);
    }

    // --- Group pages ---

    public function test_group_member_pages_are_included(): void {
        [$model, $repo] = $this->makeModel(1);

        $membership                  = new GroupMember();
        $membership->group_member_id = 1;
        $membership->group_id        = 7;
        $membership->user_id         = 1;

        $group       = new Group();
        $group->group_id = 7;
        $group->name = 'Dev Team';
        $group->slug = 'dev-team';

        $group_page          = new Page();
        $group_page->page_id  = 50;
        $group_page->user_id  = 99;
        $group_page->group_id = 7;
        $group_page->title    = 'Team Links';
        $group_page->is_public = true;

        $page_owner               = new User();
        $page_owner->user_id      = 99;
        $page_owner->display_name = 'dan';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($group, $page_owner) {
                 if ($name === 'User' && $id === 1) {
                     $u               = new User();
                     $u->user_id      = 1;
                     $u->display_name = 'alice';
                     return $u;
                 }
                 if ($name === 'Group') {
                     return $group;
                 }
                 if ($name === 'User') {
                     return $page_owner;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($membership, $group_page) {
                 if ($name === 'GroupMember') {
                     return [1 => $membership];
                 }
                 if ($name === 'Page' && isset($filter['group_id'])) {
                     return [50 => $group_page];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(1, $result['pages']);
        $this->assertTrue($result['pages'][0]['is_group_member']);
        $this->assertSame('dev-team', $result['pages'][0]['group_slug']);
        $this->assertSame('dan', $result['pages'][0]['owner_display_name']);
    }

    // --- Sorting and groups list ---

    public function test_pages_are_sorted_by_title(): void {
        [$model, $repo] = $this->makeModel(1);

        $page_b = $this->makePage(1, 1, 'Bravo');
        $page_a = $this->makePage(2, 1, 'Alpha');
        $page_c = $this->makePage(3, 1, 'Charlie');

        $owner               = new User();
        $owner->user_id      = 1;
        $owner->display_name = 'alice';

        $repo->method('get')->willReturn($owner);
        $this->stubEmptySources(
            $repo,
            ['Page' => [1 => $page_b, 2 => $page_a, 3 => $page_c]],
            $owner
        );

        $result = $model->getData();

        $titles = array_column($result['pages'], 'title');
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $titles);
    }

    public function test_groups_list_is_populated(): void {
        [$model, $repo] = $this->makeModel(1);

        $membership                  = new GroupMember();
        $membership->group_member_id = 1;
        $membership->group_id        = 7;
        $membership->user_id         = 1;

        $group           = new Group();
        $group->group_id = 7;
        $group->name     = 'Dev Team';
        $group->slug     = 'dev-team';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($group) {
                 if ($name === 'User') {
                     $u               = new User();
                     $u->user_id      = $id;
                     $u->display_name = 'alice';
                     return $u;
                 }
                 if ($name === 'Group') {
                     return $group;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($membership) {
                 if ($name === 'GroupMember') {
                     return [1 => $membership];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(1, $result['groups']);
        $this->assertSame('Dev Team', $result['groups'][0]['name']);
        $this->assertSame('dev-team', $result['groups'][0]['slug']);
    }
}
