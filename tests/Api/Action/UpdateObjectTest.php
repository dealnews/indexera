<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Api\Action;

use Dealnews\Indexera\Api\Action\UpdateObject;
use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\PageEditor;
use Dealnews\Indexera\Data\PageSubscription;
use Dealnews\Indexera\Data\Section;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Repository;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for UpdateObject.
 *
 * Focuses on the validation and authorization paths that return before
 * the parent save is reached. Success paths are covered where the
 * repository mock can supply the required new/save responses.
 *
 * @package Dealnews\Indexera\Tests\Api\Action
 */
#[AllowMockObjectsWithoutExpectations]
class UpdateObjectTest extends TestCase {

    protected MockObject&Repository $repo;
    protected UpdateObject $action;

    protected function setUp(): void {
        parent::setUp();
        $this->setSessionUser(1);
        $this->repo   = $this->makeRepositoryMock();
        $this->action = new UpdateObject();
        $this->setProperty($this->action, 'repository', $this->repo);
        $this->setProperty($this->action, 'object_id', 0);
        $this->setProperty($this->action, 'post_data', '{}');
    }

    /**
     * Convenience to set the object type being created/updated.
     *
     * @param string $name
     *
     * @return void
     */
    protected function setObjectName(string $name): void {
        $this->setProperty($this->action, 'object_name', $name);
    }

    /**
     * Convenience to simulate an update request (object_id > 0).
     *
     * @param int $id
     *
     * @return void
     */
    protected function setObjectId(int $id): void {
        $this->setProperty($this->action, 'object_id', $id);
    }

    // --- Update path: ownership ---

    public function test_update_returns_403_when_object_not_found(): void {
        $this->setObjectName('Page');
        $this->setObjectId(10);

        $this->repo->method('get')->willReturn(null);

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    public function test_update_returns_403_when_not_owned(): void {
        $this->setObjectName('Page');
        $this->setObjectId(10);

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;  // owned by someone else
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    // --- Create: Section ---

    public function test_create_section_without_page_id_throws(): void {
        $this->setObjectName('Section');
        $this->setProperty($this->action, 'post_data', json_encode([]));

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    public function test_create_section_with_uneditable_page_throws(): void {
        $this->setObjectName('Section');
        $this->setProperty($this->action, 'post_data', json_encode(['page_id' => 10]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    // --- Create: PageEditor ---

    public function test_create_page_editor_returns_403_for_uneditable_page(): void {
        $this->setObjectName('PageEditor');
        $this->setProperty($this->action, 'post_data', json_encode([
            'page_id' => 10,
            'email'   => 'editor@example.com',
        ]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    public function test_create_page_editor_returns_400_with_no_email(): void {
        $this->setObjectName('PageEditor');
        $this->setProperty($this->action, 'post_data', json_encode([
            'page_id' => 10,
        ]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;  // session user owns the page
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $result = $this->action->loadData();

        $this->assertSame(400, $result['http_status']);
    }

    public function test_create_page_editor_returns_404_for_unknown_email(): void {
        $this->setObjectName('PageEditor');
        $this->setProperty($this->action, 'post_data', json_encode([
            'page_id' => 10,
            'email'   => 'nobody@example.com',
        ]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);  // email lookup returns empty

        $result = $this->action->loadData();

        $this->assertSame(404, $result['http_status']);
    }

    public function test_create_page_editor_returns_422_when_target_is_page_owner(): void {
        $this->setObjectName('PageEditor');
        $this->setProperty($this->action, 'post_data', json_encode([
            'page_id' => 10,
            'email'   => 'owner@example.com',
        ]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;
        $page->group_id = 0;

        $target_user          = new User();
        $target_user->user_id = 1;  // same as page owner
        $target_user->email   = 'owner@example.com';

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')
                   ->willReturnOnConsecutiveCalls(
                       [1 => $target_user],  // email lookup
                   );

        $result = $this->action->loadData();

        $this->assertSame(422, $result['http_status']);
    }

    public function test_create_page_editor_returns_409_when_already_editor(): void {
        $this->setObjectName('PageEditor');
        $this->setProperty($this->action, 'post_data', json_encode([
            'page_id' => 10,
            'email'   => 'editor@example.com',
        ]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;
        $page->group_id = 0;

        $target_user          = new User();
        $target_user->user_id = 5;
        $target_user->email   = 'editor@example.com';

        $existing_editor                 = new PageEditor();
        $existing_editor->page_editor_id = 1;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')
                   ->willReturnOnConsecutiveCalls(
                       [5 => $target_user],          // email lookup
                       [1 => $existing_editor],      // duplicate check
                   );

        $result = $this->action->loadData();

        $this->assertSame(409, $result['http_status']);
    }

    // --- Create: PageSubscription ---

    public function test_create_subscription_returns_404_for_nonexistent_page(): void {
        $this->setObjectName('PageSubscription');
        $this->setProperty($this->action, 'post_data', json_encode(['page_id' => 999]));

        $this->repo->method('get')->willReturn(null);

        $result = $this->action->loadData();

        $this->assertSame(404, $result['http_status']);
    }

    public function test_create_subscription_returns_404_for_private_page_non_member(): void {
        $this->setObjectName('PageSubscription');
        $this->setProperty($this->action, 'post_data', json_encode(['page_id' => 10]));

        $page            = new Page();
        $page->page_id   = 10;
        $page->user_id   = 99;
        $page->group_id  = 0;
        $page->is_public = false;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $result = $this->action->loadData();

        $this->assertSame(404, $result['http_status']);
    }

    public function test_create_subscription_returns_403_when_subscribing_to_own_page(): void {
        $this->setObjectName('PageSubscription');
        $this->setProperty($this->action, 'post_data', json_encode(['page_id' => 10]));

        $page            = new Page();
        $page->page_id   = 10;
        $page->user_id   = 1;  // same as session user
        $page->group_id  = 0;
        $page->is_public = true;

        $this->repo->method('get')->willReturn($page);

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    // --- Create: Group ---

    public function test_create_group_returns_400_with_no_name(): void {
        $this->setObjectName('Group');
        $this->setProperty($this->action, 'post_data', json_encode([]));

        $result = $this->action->loadData();

        $this->assertSame(400, $result['http_status']);
    }

    public function test_create_group_returns_409_for_duplicate_slug(): void {
        $this->setObjectName('Group');
        $this->setProperty($this->action, 'post_data', json_encode(['name' => 'My Group']));

        $existing_group           = new Group();
        $existing_group->group_id = 3;

        $this->repo->method('find')->willReturn([3 => $existing_group]);

        $result = $this->action->loadData();

        $this->assertSame(409, $result['http_status']);
    }

    // --- Create: GroupMember ---

    public function test_create_group_member_returns_403_for_non_member(): void {
        $this->setObjectName('GroupMember');
        $this->setProperty($this->action, 'post_data', json_encode([
            'group_id' => 7,
            'email'    => 'newbie@example.com',
        ]));

        $this->repo->method('find')->willReturn([]);  // session user is not a member

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    public function test_create_group_member_returns_404_for_unknown_email(): void {
        $this->setObjectName('GroupMember');
        $this->setProperty($this->action, 'post_data', json_encode([
            'group_id' => 7,
            'email'    => 'nobody@example.com',
        ]));

        $caller_member                  = new GroupMember();
        $caller_member->group_member_id = 1;

        $this->repo->method('find')
                   ->willReturnOnConsecutiveCalls(
                       [1 => $caller_member],  // isGroupMember: session user is a member
                       [],                     // email lookup: no user found
                   );

        $result = $this->action->loadData();

        $this->assertSame(404, $result['http_status']);
    }

    public function test_create_group_member_returns_409_when_already_member(): void {
        $this->setObjectName('GroupMember');
        $this->setProperty($this->action, 'post_data', json_encode([
            'group_id' => 7,
            'email'    => 'existing@example.com',
        ]));

        $caller_member                  = new GroupMember();
        $caller_member->group_member_id = 1;

        $target_user          = new User();
        $target_user->user_id = 5;

        $existing_member                  = new GroupMember();
        $existing_member->group_member_id = 2;

        $this->repo->method('find')
                   ->willReturnOnConsecutiveCalls(
                       [1 => $caller_member],     // isGroupMember: session user is a member
                       [5 => $target_user],       // email lookup
                       [2 => $existing_member],   // duplicate check
                   );

        $result = $this->action->loadData();

        $this->assertSame(409, $result['http_status']);
    }

    // --- User: username validation ---

    public function test_create_user_normalises_display_name_to_lowercase(): void {
        $this->setObjectName('User');
        $this->setProperty($this->action, 'post_data', json_encode([
            'email'        => 'u@example.com',
            'display_name' => 'alice',
            'password'     => 'hashed',
        ]));

        // Session user is admin.
        $session_user           = new User();
        $session_user->user_id  = 1;
        $session_user->is_admin = true;

        $saved_user          = new User();
        $saved_user->user_id = 2;
        $saved_user->display_name = 'alice';

        $this->repo->method('get')->willReturn($session_user);
        $this->repo->method('new')->willReturn(new User());
        $this->repo->method('save')->willReturn($saved_user);

        $result = $this->action->loadData();

        $this->assertArrayNotHasKey('error', $result);
    }

    public function test_create_user_returns_422_for_invalid_username(): void {
        $this->setObjectName('User');
        $this->setProperty($this->action, 'post_data', json_encode([
            'email'        => 'u@example.com',
            'display_name' => 'Alice Smith',  // spaces not allowed
        ]));

        $session_user           = new User();
        $session_user->user_id  = 1;
        $session_user->is_admin = true;

        $this->repo->method('get')->willReturn($session_user);

        $result = $this->action->loadData();

        $this->assertSame(422, $result['http_status']);
    }
}
