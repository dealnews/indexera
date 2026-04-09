<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Action\Auth;

use Dealnews\Indexera\Action\Auth\LoginAction;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Subclass that suppresses the redirect so tests don't call exit.
 */
class TestableLoginAction extends LoginAction {
    public string $redirected_to = '';

    protected function doRedirect(string $url): void {
        $this->redirected_to = $url;
    }
}

/**
 * Tests for LoginAction.
 *
 * @package Dealnews\Indexera\Tests\Action\Auth
 */
#[AllowMockObjectsWithoutExpectations]
class LoginActionTest extends TestCase {

    /**
     * Builds a LoginAction with a mocked repository injected.
     *
     * @return array{TestableLoginAction, \PHPUnit\Framework\MockObject\MockObject}
     */
    protected function makeAction(): array {
        $repo   = $this->makeRepositoryMock();
        $action = new TestableLoginAction([]);
        $this->setProperty($action, 'repository', $repo);
        return [$action, $repo];
    }

    public function test_empty_email_returns_error(): void {
        [$action] = $this->makeAction();
        $action->password = 'secret';

        $result = $action->doAction();

        $this->assertSame(['error' => 'Email and password are required.'], $result);
    }

    public function test_empty_password_returns_error(): void {
        [$action] = $this->makeAction();
        $action->email = 'user@example.com';

        $result = $action->doAction();

        $this->assertSame(['error' => 'Email and password are required.'], $result);
    }

    public function test_unknown_email_returns_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'nobody@example.com';
        $action->password = 'secret';

        $repo->method('find')->willReturn([]);

        $result = $action->doAction();

        $this->assertSame(['error' => 'Invalid email or password.'], $result);
    }

    public function test_oauth_only_user_with_null_password_returns_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'oauth@example.com';
        $action->password = 'secret';

        $user           = new User();
        $user->user_id  = 5;
        $user->password = null;

        $repo->method('find')->willReturn([5 => $user]);

        $result = $action->doAction();

        $this->assertSame(['error' => 'Invalid email or password.'], $result);
    }

    public function test_wrong_password_returns_error(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'user@example.com';
        $action->password = 'wrong';

        $user           = new User();
        $user->user_id  = 3;
        $user->password = password_hash('correct', PASSWORD_DEFAULT);

        $repo->method('find')->willReturn([3 => $user]);

        $result = $action->doAction();

        $this->assertSame(['error' => 'Invalid email or password.'], $result);
    }

    public function test_valid_credentials_set_session_and_redirect_to_dashboard(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'user@example.com';
        $action->password = 'secret123';

        $user           = new User();
        $user->user_id  = 7;
        $user->password = password_hash('secret123', PASSWORD_DEFAULT);

        $repo->method('find')->willReturn([7 => $user]);

        $result = $action->doAction();

        $this->assertNull($result);
        $this->assertSame(7, $_SESSION['user_id']);
        $this->assertSame('/dashboard', $action->redirected_to);
    }

    public function test_valid_credentials_with_safe_next_url_redirects_to_next(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'user@example.com';
        $action->password = 'secret123';
        $action->next     = '/some/page';

        $user           = new User();
        $user->user_id  = 7;
        $user->password = password_hash('secret123', PASSWORD_DEFAULT);

        $repo->method('find')->willReturn([7 => $user]);

        $action->doAction();

        $this->assertSame('/some/page', $action->redirected_to);
    }

    public function test_next_starting_with_double_slash_falls_back_to_dashboard(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'user@example.com';
        $action->password = 'secret123';
        $action->next     = '//evil.com/steal';

        $user           = new User();
        $user->user_id  = 7;
        $user->password = password_hash('secret123', PASSWORD_DEFAULT);

        $repo->method('find')->willReturn([7 => $user]);

        $action->doAction();

        $this->assertSame('/dashboard', $action->redirected_to);
    }

    public function test_next_not_starting_with_slash_falls_back_to_dashboard(): void {
        [$action, $repo] = $this->makeAction();
        $action->email    = 'user@example.com';
        $action->password = 'secret123';
        $action->next     = 'https://evil.com';

        $user           = new User();
        $user->user_id  = 7;
        $user->password = password_hash('secret123', PASSWORD_DEFAULT);

        $repo->method('find')->willReturn([7 => $user]);

        $action->doAction();

        $this->assertSame('/dashboard', $action->redirected_to);
    }
}
