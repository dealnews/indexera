<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Model;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Model\GroupManageModel;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for GroupManageModel.
 *
 * Covers group resolution, membership gate, and member list hydration.
 *
 * @package Dealnews\Indexera\Tests\Model
 */
#[AllowMockObjectsWithoutExpectations]
class GroupManageModelTest extends TestCase {

    /**
     * Builds a GroupManageModel with a mock repository injected.
     *
     * @param string $group_slug
     * @param int    $current_user_id
     *
     * @return array{GroupManageModel, MockObject}
     */
    protected function makeModel(
        string $group_slug = 'mygroup',
        int $current_user_id = 1
    ): array {
        $repo  = $this->makeRepositoryMock();
        $model = new GroupManageModel([
            'group_slug'      => $group_slug,
            'current_user_id' => $current_user_id,
        ]);
        $this->setProperty($model, 'repository', $repo);
        return [$model, $repo];
    }

    public function test_empty_slug_returns_not_found(): void {
        [$model] = $this->makeModel('', 1);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_zero_user_id_returns_not_found(): void {
        [$model] = $this->makeModel('mygroup', 0);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_unknown_group_returns_not_found(): void {
        [$model, $repo] = $this->makeModel('nosuchgroup', 1);

        $repo->method('find')->willReturn([]);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_non_member_returns_not_member_flag(): void {
        [$model, $repo] = $this->makeModel('mygroup', 5);

        $group           = new Group();
        $group->group_id = 7;
        $group->slug     = 'mygroup';

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($group) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 // Membership check returns empty — user is not a member.
                 return [];
             });

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertTrue($result['not_member']);
        $this->assertSame($group, $result['group']);
    }

    public function test_member_gets_member_list(): void {
        [$model, $repo] = $this->makeModel('mygroup', 1);

        $group           = new Group();
        $group->group_id = 7;

        $caller_member                  = new GroupMember();
        $caller_member->group_member_id = 1;
        $caller_member->user_id         = 1;

        $user           = new User();
        $user->user_id  = 1;
        $user->display_name = 'alice';
        $user->email    = 'alice@example.com';

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($group, $caller_member) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 // Both the membership check and the full list return the same member.
                 if ($name === 'GroupMember') {
                     return [1 => $caller_member];
                 }
                 return [];
             });

        $repo->method('get')->willReturn($user);

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertFalse($result['not_member']);
        $this->assertCount(1, $result['members']);
        $this->assertSame('alice', $result['members'][0]['display_name']);
    }

    public function test_member_with_missing_user_is_skipped(): void {
        [$model, $repo] = $this->makeModel('mygroup', 1);

        $group           = new Group();
        $group->group_id = 7;

        $caller_member                  = new GroupMember();
        $caller_member->group_member_id = 1;
        $caller_member->user_id         = 1;

        $orphan_member                  = new GroupMember();
        $orphan_member->group_member_id = 2;
        $orphan_member->user_id         = 999;  // user row was deleted

        $user           = new User();
        $user->user_id  = 1;

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($group, $caller_member, $orphan_member) {
                 if ($name === 'Group') {
                     return [7 => $group];
                 }
                 if ($name === 'GroupMember') {
                     return [1 => $caller_member, 2 => $orphan_member];
                 }
                 return [];
             });

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($user) {
                 if ($id === 1) {
                     return $user;
                 }
                 return null;  // orphan user not found
             });

        $result = $model->getData();

        $this->assertCount(1, $result['members']);
    }
}
