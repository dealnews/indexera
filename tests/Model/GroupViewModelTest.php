<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Model;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Model\GroupViewModel;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for GroupViewModel.
 *
 * Covers group resolution, membership-based page filtering, and
 * owner display name hydration.
 *
 * @package Dealnews\Indexera\Tests\Model
 */
#[AllowMockObjectsWithoutExpectations]
class GroupViewModelTest extends TestCase {

    /**
     * Builds a GroupViewModel with a mock repository injected.
     *
     * @param string $group_slug
     * @param int    $current_user_id
     *
     * @return array{GroupViewModel, MockObject}
     */
    protected function makeModel(
        string $group_slug = 'mygroup',
        int $current_user_id = 0
    ): array {
        $repo  = $this->makeRepositoryMock();
        $model = new GroupViewModel([
            'group_slug'      => $group_slug,
            'current_user_id' => $current_user_id,
        ]);
        $this->setProperty($model, 'repository', $repo);
        return [$model, $repo];
    }

    public function test_empty_slug_returns_not_found(): void {
        [$model] = $this->makeModel('');

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_unknown_group_returns_not_found(): void {
        [$model, $repo] = $this->makeModel('nosuchgroup');

        $repo->method('find')->willReturn([]);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_guest_sees_only_public_pages(): void {
        [$model, $repo] = $this->makeModel('mygroup', 0);

        $group           = new Group();
        $group->group_id = 7;
        $group->slug     = 'mygroup';

        $public_page          = new Page();
        $public_page->page_id = 1;
        $public_page->user_id = 2;
        $public_page->is_public = true;

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($group, $public_page) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 // Guests only: filter includes is_public = true.
                 if ($name === 'Page' && ($filter['is_public'] ?? false) === true) {
                     return [1 => $public_page];
                 }
                 return [];
             });

        $owner           = new User();
        $owner->user_id  = 2;
        $owner->display_name = 'alice';

        $repo->method('get')->willReturn($owner);

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertFalse($result['is_member']);
        $this->assertCount(1, $result['pages']);
    }

    public function test_member_sees_all_pages(): void {
        [$model, $repo] = $this->makeModel('mygroup', 1);

        $group           = new Group();
        $group->group_id = 7;

        $member                  = new GroupMember();
        $member->group_member_id = 1;

        $public_page            = new Page();
        $public_page->page_id   = 1;
        $public_page->user_id   = 2;
        $public_page->is_public = true;

        $private_page            = new Page();
        $private_page->page_id   = 2;
        $private_page->user_id   = 2;
        $private_page->is_public = false;

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($group, $member, $public_page, $private_page) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 if ($name === 'GroupMember') {
                     return [1 => $member];
                 }
                 // Member: no is_public filter.
                 if ($name === 'Page') {
                     return [1 => $public_page, 2 => $private_page];
                 }
                 return [];
             });

        $owner           = new User();
        $owner->user_id  = 2;
        $owner->display_name = 'alice';

        $repo->method('get')->willReturn($owner);

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertTrue($result['is_member']);
        $this->assertCount(2, $result['pages']);
    }

    public function test_page_owner_display_name_is_populated(): void {
        [$model, $repo] = $this->makeModel('mygroup', 0);

        $group           = new Group();
        $group->group_id = 7;

        $page            = new Page();
        $page->page_id   = 1;
        $page->user_id   = 3;
        $page->is_public = true;

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($group, $page) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 if ($name === 'Page') {
                     return [1 => $page];
                 }
                 return [];
             });

        $owner               = new User();
        $owner->user_id      = 3;
        $owner->display_name = 'bob';

        $repo->method('get')->willReturn($owner);

        $result = $model->getData();

        $this->assertSame('bob', $result['pages'][0]['owner_display_name']);
    }
}
