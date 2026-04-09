<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Api\Action;

use Dealnews\Indexera\Api\Action\OwnershipTrait;
use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Link;
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
 * Concrete class exposing OwnershipTrait methods for testing.
 */
class OwnershipTestDouble {
    use OwnershipTrait;

    protected Repository $repository;

    public function __construct(Repository $repository) {
        $this->repository = $repository;
    }

    public function checkIsOwned(object $object): bool {
        return $this->isOwned($object);
    }

    public function checkIsPageEditable(int $page_id, int $user_id): bool {
        return $this->isPageEditable($page_id, $user_id);
    }

    public function checkIsSectionEditable(int $section_id, int $user_id): bool {
        return $this->isSectionEditable($section_id, $user_id);
    }

    public function checkIsGroupMember(int $group_id, int $user_id): bool {
        return $this->isGroupMember($group_id, $user_id);
    }
}

/**
 * Tests for OwnershipTrait.
 *
 * @package Dealnews\Indexera\Tests\Api\Action
 */
#[AllowMockObjectsWithoutExpectations]
class OwnershipTraitTest extends TestCase {

    protected MockObject&Repository $repo;
    protected OwnershipTestDouble $subject;

    protected function setUp(): void {
        parent::setUp();
        $this->repo    = $this->makeRepositoryMock();
        $this->subject = new OwnershipTestDouble($this->repo);
        $this->setSessionUser(1);
    }

    // --- isOwned: Page ---

    public function test_is_owned_page_by_owner(): void {
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;
        $page->group_id = 0;

        $this->assertTrue($this->subject->checkIsOwned($page));
    }

    public function test_is_owned_page_not_owned_by_different_user(): void {
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;
        $page->group_id = 0;

        $this->repo->method('find')->willReturn([]);

        $this->assertFalse($this->subject->checkIsOwned($page));
    }

    public function test_is_owned_page_by_registered_editor(): void {
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;
        $page->group_id = 0;

        $editor           = new PageEditor();
        $editor->page_editor_id = 1;

        $this->repo->method('find')->willReturn([1 => $editor]);

        $this->assertTrue($this->subject->checkIsOwned($page));
    }

    public function test_is_owned_group_page_by_member(): void {
        $page           = new Page();
        $page->page_id  = 20;
        $page->user_id  = 99;
        $page->group_id = 5;

        $member                 = new GroupMember();
        $member->group_member_id = 1;

        $this->repo->method('find')->willReturn([1 => $member]);

        $this->assertTrue($this->subject->checkIsOwned($page));
    }

    public function test_is_owned_group_page_false_for_non_member(): void {
        $page           = new Page();
        $page->page_id  = 20;
        $page->user_id  = 99;
        $page->group_id = 5;

        $this->repo->method('find')->willReturn([]);

        $this->assertFalse($this->subject->checkIsOwned($page));
    }

    // --- isOwned: Section ---

    public function test_is_owned_section_delegates_to_page_editable(): void {
        $section          = new Section();
        $section->section_id = 3;
        $section->page_id    = 10;

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;   // session user owns the page
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);

        $this->assertTrue($this->subject->checkIsOwned($section));
    }

    // --- isOwned: Link ---

    public function test_is_owned_link_delegates_through_section_to_page(): void {
        $link             = new Link();
        $link->link_id    = 5;
        $link->section_id = 3;

        $section             = new Section();
        $section->section_id = 3;
        $section->page_id    = 10;

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;
        $page->group_id = 0;

        $this->repo->method('get')
                   ->willReturnMap([
                       ['Section', 3, $section],
                       ['Page',    10, $page],
                   ]);

        $this->assertTrue($this->subject->checkIsOwned($link));
    }

    // --- isOwned: Group ---

    public function test_is_owned_group_true_for_member(): void {
        $group           = new Group();
        $group->group_id = 7;

        $member                  = new GroupMember();
        $member->group_member_id = 1;

        $this->repo->method('find')->willReturn([1 => $member]);

        $this->assertTrue($this->subject->checkIsOwned($group));
    }

    public function test_is_owned_group_false_for_non_member(): void {
        $group           = new Group();
        $group->group_id = 7;

        $this->repo->method('find')->willReturn([]);

        $this->assertFalse($this->subject->checkIsOwned($group));
    }

    // --- isOwned: fallback user_id property ---

    public function test_is_owned_falls_back_to_user_id_match(): void {
        $sub                       = new PageSubscription();
        $sub->page_subscription_id = 1;
        $sub->user_id              = 1;   // matches session user
        $sub->page_id              = 10;

        $this->assertTrue($this->subject->checkIsOwned($sub));
    }

    public function test_is_owned_falls_back_to_user_id_mismatch(): void {
        $sub                       = new PageSubscription();
        $sub->page_subscription_id = 1;
        $sub->user_id              = 99;
        $sub->page_id              = 10;

        $this->assertFalse($this->subject->checkIsOwned($sub));
    }

    // --- isPageEditable ---

    public function test_is_page_editable_returns_false_for_missing_page(): void {
        $this->repo->method('get')->willReturn(null);

        $this->assertFalse($this->subject->checkIsPageEditable(999, 1));
    }

    public function test_is_page_editable_true_for_page_owner(): void {
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);

        $this->assertTrue($this->subject->checkIsPageEditable(10, 1));
    }

    public function test_is_page_editable_true_for_group_member(): void {
        $page           = new Page();
        $page->page_id  = 10;
        $page->user_id  = 99;
        $page->group_id = 5;

        $member                  = new GroupMember();
        $member->group_member_id = 1;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([1 => $member]);

        $this->assertTrue($this->subject->checkIsPageEditable(10, 1));
    }

    // --- isGroupMember ---

    public function test_is_group_member_true_when_record_exists(): void {
        $member                  = new GroupMember();
        $member->group_member_id = 1;

        $this->repo->method('find')->willReturn([1 => $member]);

        $this->assertTrue($this->subject->checkIsGroupMember(5, 1));
    }

    public function test_is_group_member_false_when_no_record(): void {
        $this->repo->method('find')->willReturn([]);

        $this->assertFalse($this->subject->checkIsGroupMember(5, 1));
    }
}
