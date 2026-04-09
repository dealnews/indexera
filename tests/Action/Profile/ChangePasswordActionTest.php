<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Action\Profile;

use Dealnews\Indexera\Action\Profile\ChangePasswordAction;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Subclass that suppresses the redirect so tests don't call exit.
 */
class TestableChangePasswordAction extends ChangePasswordAction {
    public string $redirected_to = '';

    protected function doRedirect(string $url): void {
        $this->redirected_to = $url;
    }
}

/**
 * Tests for ChangePasswordAction.
 *
 * @package Dealnews\Indexera\Tests\Action\Profile
 */
#[AllowMockObjectsWithoutExpectations]
class ChangePasswordActionTest extends TestCase {

    /**
     * Builds a ChangePasswordAction with a mocked repository injected.
     *
     * @return array{TestableChangePasswordAction, \PHPUnit\Framework\MockObject\MockObject}
     */
    protected function makeAction(): array {
        $repo   = $this->makeRepositoryMock();
        $action = new TestableChangePasswordAction([]);
        $this->setProperty($action, 'repository', $repo);
        return [$action, $repo];
    }

    public function test_password_mismatch_returns_error(): void {
        [$action] = $this->makeAction();
        $action->user_id          = 1;
        $action->current_password = 'old';
        $action->new_password     = 'newpass1';
        $action->confirm_password = 'newpass2';

        $result = $action->doAction();

        $this->assertSame(['error' => 'New passwords do not match.'], $result);
    }

    public function test_new_password_too_short_returns_error(): void {
        [$action] = $this->makeAction();
        $action->user_id          = 1;
        $action->current_password = 'old';
        $action->new_password     = 'short';
        $action->confirm_password = 'short';

        $result = $action->doAction();

        $this->assertSame(['error' => 'New password must be at least 8 characters.'], $result);
    }

    public function test_user_not_found_returns_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->user_id          = 99;
        $action->current_password = 'anything';
        $action->new_password     = 'newpass123';
        $action->confirm_password = 'newpass123';

        $repo->method('get')->willReturn(null);

        $result = $action->doAction();

        $this->assertSame(['error' => 'Current password is incorrect.'], $result);
    }

    public function test_wrong_current_password_returns_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->user_id          = 1;
        $action->current_password = 'wrongold';
        $action->new_password     = 'newpass123';
        $action->confirm_password = 'newpass123';

        $user           = new User();
        $user->user_id  = 1;
        $user->password = password_hash('correctold', PASSWORD_DEFAULT);

        $repo->method('get')->willReturn($user);

        $result = $action->doAction();

        $this->assertSame(['error' => 'Current password is incorrect.'], $result);
    }

    public function test_correct_password_saves_new_hash_and_redirects(): void {
        [$action, $repo] = $this->makeAction();
        $action->user_id          = 1;
        $action->current_password = 'correctold';
        $action->new_password     = 'newpass123';
        $action->confirm_password = 'newpass123';

        $user           = new User();
        $user->user_id  = 1;
        $user->password = password_hash('correctold', PASSWORD_DEFAULT);

        $repo->method('get')->willReturn($user);
        $repo->method('save')->willReturn($user);

        $result = $action->doAction();

        $this->assertNull($result);
        $this->assertSame('/profile', $action->redirected_to);
        $this->assertTrue(password_verify('newpass123', $user->password));
    }
}
