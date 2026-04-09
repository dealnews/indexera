<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Action\Auth;

use Dealnews\Indexera\Action\Auth\RegisterAction;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Subclass that suppresses the redirect so tests don't call exit.
 */
class TestableRegisterAction extends RegisterAction {
    public string $redirected_to = '';

    protected function doRedirect(string $url): void {
        $this->redirected_to = $url;
    }
}

/**
 * Tests for RegisterAction.
 *
 * @package Dealnews\Indexera\Tests\Action\Auth
 */
#[AllowMockObjectsWithoutExpectations]
class RegisterActionTest extends TestCase {

    /**
     * Builds a RegisterAction with a mocked repository injected.
     *
     * @return array{RegisterAction, \PHPUnit\Framework\MockObject\MockObject}
     */
    protected function makeAction(): array {
        $repo   = $this->makeRepositoryMock();
        $action = new TestableRegisterAction([]);
        $this->setProperty($action, 'repository', $repo);
        return [$action, $repo];
    }

    public function test_empty_fields_returns_error(): void {
        [$action] = $this->makeAction();

        $result = $action->doAction();

        $this->assertSame(['error' => 'All fields are required.'], $result);
    }

    public function test_missing_password_returns_error(): void {
        [$action] = $this->makeAction();
        $action->email        = 'user@example.com';
        $action->display_name = 'alice';

        $result = $action->doAction();

        $this->assertSame(['error' => 'All fields are required.'], $result);
    }

    public function test_username_with_spaces_returns_error(): void {
        [$action] = $this->makeAction();
        $action->email            = 'user@example.com';
        $action->display_name     = 'alice smith';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $result = $action->doAction();

        $this->assertStringContainsString('Username may only contain', $result['error']);
    }

    public function test_special_characters_in_username_are_rejected(): void {
        [$action] = $this->makeAction();
        $action->email            = 'user@example.com';
        $action->display_name     = 'alice!';  // exclamation mark is not allowed
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $result = $action->doAction();

        $this->assertStringContainsString('Username may only contain', $result['error']);
    }

    public function test_username_is_normalised_to_lowercase(): void {
        [$action, $repo] = $this->makeAction();
        $action->email            = 'user@example.com';
        $action->display_name     = 'alice';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $saved_user       = new User();
        $saved_user->user_id    = 42;
        $saved_user->display_name = 'alice';

        $repo->method('find')->willReturn([]);
        $repo->method('save')->willReturn($saved_user);

        $action->doAction();

        $this->assertSame('alice', $action->display_name);
    }

    public function test_hyphens_and_underscores_are_valid_in_username(): void {
        [$action, $repo] = $this->makeAction();
        $action->email            = 'user@example.com';
        $action->display_name     = 'alice_smith-99';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $saved_user           = new User();
        $saved_user->user_id  = 1;

        $repo->method('find')->willReturn([]);
        $repo->method('save')->willReturn($saved_user);

        $result = $action->doAction();

        $this->assertNull($result);
    }

    public function test_password_mismatch_returns_error(): void {
        [$action] = $this->makeAction();
        $action->email            = 'user@example.com';
        $action->display_name     = 'alice';
        $action->password         = 'secret123';
        $action->password_confirm = 'different';

        $result = $action->doAction();

        $this->assertSame(['error' => 'Passwords do not match.'], $result);
    }

    public function test_duplicate_email_returns_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->email            = 'taken@example.com';
        $action->display_name     = 'alice';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $existing           = new User();
        $existing->user_id  = 5;
        $existing->email    = 'taken@example.com';

        $repo->method('find')
             ->willReturn([5 => $existing]);

        $result = $action->doAction();

        $this->assertSame(['error' => 'An account with that email already exists.'], $result);
    }

    public function test_first_registered_user_becomes_admin(): void {
        [$action, $repo] = $this->makeAction();
        $action->email            = 'first@example.com';
        $action->display_name     = 'founder';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $saved_user          = new User();
        $saved_user->user_id = 1;
        $saved_user->is_admin = true;

        // No existing users — first call (email check) returns empty,
        // second call (first-user check) also returns empty.
        $repo->method('find')->willReturn([]);
        $repo->expects($this->once())
             ->method('save')
             ->with('User', $this->callback(function (User $u) {
                 return $u->is_admin === true;
             }))
             ->willReturn($saved_user);

        $action->doAction();
    }

    public function test_subsequent_user_is_not_admin(): void {
        [$action, $repo] = $this->makeAction();
        $action->email            = 'second@example.com';
        $action->display_name     = 'newbie';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $existing_user          = new User();
        $existing_user->user_id = 1;

        $saved_user          = new User();
        $saved_user->user_id = 2;
        $saved_user->is_admin = false;

        // Email check: no match. First-user check: returns existing user.
        $repo->expects($this->exactly(2))
             ->method('find')
             ->willReturnOnConsecutiveCalls([], [1 => $existing_user]);

        $repo->expects($this->once())
             ->method('save')
             ->with('User', $this->callback(function (User $u) {
                 return $u->is_admin === false;
             }))
             ->willReturn($saved_user);

        $action->doAction();
    }

    public function test_successful_registration_sets_session_and_redirects(): void {
        [$action, $repo] = $this->makeAction();
        $action->email            = 'user@example.com';
        $action->display_name     = 'alice';
        $action->password         = 'secret123';
        $action->password_confirm = 'secret123';

        $saved_user          = new User();
        $saved_user->user_id = 7;

        $repo->method('find')->willReturn([]);
        $repo->method('save')->willReturn($saved_user);

        $result = $action->doAction();

        $this->assertNull($result);
        $this->assertSame(7, $_SESSION['user_id']);
        $this->assertSame('/dashboard', $action->redirected_to);
    }
}
