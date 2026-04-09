<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Api\Action;

use Dealnews\Indexera\Api\Action\DeleteObject;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\PageEditor;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Repository;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for DeleteObject.
 *
 * Focuses on the authorization paths that return before the parent
 * delete is reached. The success path is tested where the repository
 * mock can satisfy the parent's delete() call.
 *
 * @package Dealnews\Indexera\Tests\Api\Action
 */
#[AllowMockObjectsWithoutExpectations]
class DeleteObjectTest extends TestCase {

    protected MockObject&Repository $repo;
    protected DeleteObject $action;

    protected function setUp(): void {
        parent::setUp();
        $this->setSessionUser(1);
        $this->repo   = $this->makeRepositoryMock();
        $this->action = new DeleteObject();
        $this->setProperty($this->action, 'repository', $this->repo);
        $this->setProperty($this->action, 'object_name', 'Page');
        $this->setProperty($this->action, 'object_id', 10);
    }

    // --- Object not found ---

    public function test_not_found_returns_404(): void {
        $this->repo->method('get')->willReturn(null);

        $result = $this->action->loadData();

        $this->assertSame(404, $result['http_status']);
    }

    // --- Non-User objects: ownership check ---

    public function test_non_user_unowned_object_returns_403(): void {
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;   // different user
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    public function test_non_user_owned_object_is_deleted(): void {
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;   // session user owns it
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('delete')->willReturn(true);

        $result = $this->action->loadData();

        $this->assertSame(200, $result['http_status']);
    }

    // --- User objects: admin-only path ---

    public function test_user_delete_by_non_admin_returns_403(): void {
        $this->setProperty($this->action, 'object_name', 'User');
        $this->setProperty($this->action, 'object_id', 5);

        $target_user          = new User();
        $target_user->user_id = 5;

        $session_user           = new User();
        $session_user->user_id  = 1;
        $session_user->is_admin = false;

        $this->repo->method('get')
                   ->willReturnMap([
                       ['User', 5, $target_user],
                       ['User', 1, $session_user],
                   ]);

        $result = $this->action->loadData();

        $this->assertSame(403, $result['http_status']);
    }

    public function test_user_delete_of_self_by_admin_returns_422(): void {
        $this->setProperty($this->action, 'object_name', 'User');
        $this->setProperty($this->action, 'object_id', 1);  // session user id

        $session_user           = new User();
        $session_user->user_id  = 1;
        $session_user->is_admin = true;

        // Both get() calls (target and session user) return the same admin user.
        $this->repo->method('get')->willReturn($session_user);

        $result = $this->action->loadData();

        $this->assertSame(422, $result['http_status']);
    }

    public function test_admin_can_delete_another_user(): void {
        $this->setProperty($this->action, 'object_name', 'User');
        $this->setProperty($this->action, 'object_id', 5);

        $target_user          = new User();
        $target_user->user_id = 5;

        $session_user           = new User();
        $session_user->user_id  = 1;
        $session_user->is_admin = true;

        $this->repo->method('get')
                   ->willReturnMap([
                       ['User', 5, $target_user],
                       ['User', 1, $session_user],
                   ]);
        $this->repo->method('delete')->willReturn(true);

        $result = $this->action->loadData();

        $this->assertSame(200, $result['http_status']);
    }
}
