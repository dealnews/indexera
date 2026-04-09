<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Api\Action;

use Dealnews\Indexera\Api\Action\SearchObjects;
use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\Section;
use Dealnews\Indexera\Repository;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for SearchObjects.
 *
 * @package Dealnews\Indexera\Tests\Api\Action
 */
#[AllowMockObjectsWithoutExpectations]
class SearchObjectsTest extends TestCase {

    protected MockObject&Repository $repo;
    protected SearchObjects $action;

    protected function setUp(): void {
        parent::setUp();
        $this->setSessionUser(1);
        $this->repo   = $this->makeRepositoryMock();
        $this->action = new SearchObjects();
        $this->setProperty($this->action, 'repository', $this->repo);
        $this->setProperty($this->action, 'object_name', '');
        $this->setProperty($this->action, 'post_data', '{}');
    }

    // --- Section ---

    public function test_section_search_with_no_page_id_throws(): void {
        $this->setProperty($this->action, 'object_name', 'Section');

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    public function test_section_search_with_uneditable_page_throws(): void {
        $this->setProperty($this->action, 'object_name', 'Section');
        $this->setProperty($this->action, 'post_data', json_encode([
            'filter' => ['page_id' => 10],
        ]));

        // Page belongs to a different user, no editor record.
        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    public function test_section_search_passes_ownership_check_for_editable_page(): void {
        $this->setProperty($this->action, 'object_name', 'Section');
        $this->setProperty($this->action, 'post_data', json_encode([
            'filter' => ['page_id' => 10],
        ]));

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 1;   // session user owns it
        $page->group_id = 0;

        $this->repo->method('get')->willReturn($page);
        $this->repo->method('find')->willReturn([]);

        // Our ownership check passes; the parent throws because the mock
        // repository doesn't provide a real DB mapper. Assert the exception
        // is the parent's, not ours.
        try {
            $this->action->loadData();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('does not support search', $e->getMessage());
        }
    }

    // --- Link ---

    public function test_link_search_with_no_section_id_throws(): void {
        $this->setProperty($this->action, 'object_name', 'Link');

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    public function test_link_search_with_uneditable_section_throws(): void {
        $this->setProperty($this->action, 'object_name', 'Link');
        $this->setProperty($this->action, 'post_data', json_encode([
            'filter' => ['section_id' => 3],
        ]));

        $section             = new Section();
        $section->section_id = 3;
        $section->page_id    = 10;

        $page          = new Page();
        $page->page_id = 10;
        $page->user_id = 99;
        $page->group_id = 0;

        $this->repo->method('get')
                   ->willReturnMap([
                       ['Section', 3, $section],
                       ['Page',    10, $page],
                   ]);
        $this->repo->method('find')->willReturn([]);

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    // --- GroupMember ---

    public function test_group_member_search_with_no_group_id_throws(): void {
        $this->setProperty($this->action, 'object_name', 'GroupMember');

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    public function test_group_member_search_for_non_member_throws(): void {
        $this->setProperty($this->action, 'object_name', 'GroupMember');
        $this->setProperty($this->action, 'post_data', json_encode([
            'filter' => ['group_id' => 7],
        ]));

        $this->repo->method('find')->willReturn([]);

        $this->expectException(\LogicException::class);

        $this->action->loadData();
    }

    public function test_group_member_search_passes_ownership_check_for_member(): void {
        $this->setProperty($this->action, 'object_name', 'GroupMember');
        $this->setProperty($this->action, 'post_data', json_encode([
            'filter' => ['group_id' => 7],
        ]));

        $member                  = new GroupMember();
        $member->group_member_id = 1;
        $member->group_id        = 7;
        $member->user_id         = 1;

        $this->repo->method('find')->willReturn([1 => $member]);

        // Our membership check passes; the parent throws because mock
        // repository has no real DB mapper.
        try {
            $this->action->loadData();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('does not support search', $e->getMessage());
        }
    }

    // --- Default case ---

    public function test_default_object_injects_user_id_filter(): void {
        $this->setProperty($this->action, 'object_name', 'Page');
        $this->setProperty($this->action, 'post_data', json_encode([]));

        $this->repo->method('find')->willReturn([]);

        // Our code writes user_id into post_data before calling the parent.
        // The parent throws because the mock has no real DB mapper — catch it.
        try {
            $this->action->loadData();
        } catch (\LogicException $e) {
            // Expected; only the parent's exception is acceptable here.
        }

        $post_data = json_decode(
            $this->getProperty($this->action, 'post_data'),
            true
        );
        $this->assertSame(1, $post_data['filter']['user_id']);
    }

    /**
     * Reads a protected property value using reflection.
     *
     * @param object $target
     * @param string $property
     *
     * @return mixed
     */
    protected function getProperty(object $target, string $property): mixed {
        $ref = new \ReflectionProperty($target, $property);
        return $ref->getValue($target);
    }
}
