<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Action\Admin;

use Dealnews\Indexera\Action\Admin\ToggleAdminAction;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Subclass that suppresses the redirect so tests don't call exit.
 */
class TestableToggleAdminAction extends ToggleAdminAction {
    public string $redirected_to = '';

    protected function doRedirect(string $url): void {
        $this->redirected_to = $url;
    }
}

/**
 * Tests for ToggleAdminAction.
 *
 * @package Dealnews\Indexera\Tests\Action\Admin
 */
#[AllowMockObjectsWithoutExpectations]
class ToggleAdminActionTest extends TestCase {

    /**
     * Builds a ToggleAdminAction with a mocked repository injected.
     *
     * @return array{TestableToggleAdminAction, \PHPUnit\Framework\MockObject\MockObject}
     */
    protected function makeAction(): array {
        $repo   = $this->makeRepositoryMock();
        $action = new TestableToggleAdminAction([]);
        $this->setProperty($action, 'repository', $repo);
        return [$action, $repo];
    }

    public function test_zero_user_id_redirects_without_touching_db(): void {
        [$action] = $this->makeAction();
        $action->user_id         = 0;
        $action->current_user_id = 1;

        $action->doAction();

        $this->assertSame('/admin/users', $action->redirected_to);
    }

    public function test_self_demotion_is_rejected(): void {
        [$action] = $this->makeAction();
        $action->user_id         = 5;
        $action->current_user_id = 5;

        $action->doAction();

        $this->assertSame('/admin/users', $action->redirected_to);
    }

    public function test_missing_user_still_redirects(): void {
        [$action, $repo] = $this->makeAction();
        $action->user_id         = 99;
        $action->current_user_id = 1;

        $repo->method('get')->willReturn(null);

        $action->doAction();

        $this->assertSame('/admin/users', $action->redirected_to);
    }

    public function test_toggles_is_admin_from_false_to_true(): void {
        [$action, $repo] = $this->makeAction();
        $action->user_id         = 5;
        $action->current_user_id = 1;

        $user           = new User();
        $user->user_id  = 5;
        $user->is_admin = false;

        $repo->method('get')->willReturn($user);
        $repo->method('save')->willReturn($user);

        $action->doAction();

        $this->assertTrue($user->is_admin);
        $this->assertSame('/admin/users', $action->redirected_to);
    }

    public function test_toggles_is_admin_from_true_to_false(): void {
        [$action, $repo] = $this->makeAction();
        $action->user_id         = 5;
        $action->current_user_id = 1;

        $user           = new User();
        $user->user_id  = 5;
        $user->is_admin = true;

        $repo->method('get')->willReturn($user);
        $repo->method('save')->willReturn($user);

        $action->doAction();

        $this->assertFalse($user->is_admin);
    }
}
