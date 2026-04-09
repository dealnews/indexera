<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests\Model;

use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Data\Link;
use Dealnews\Indexera\Data\Page;
use Dealnews\Indexera\Data\PageEditor;
use Dealnews\Indexera\Data\Section;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Model\PageEditModel;
use Dealnews\Indexera\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for PageEditModel.
 *
 * Covers the three-level access check (owner, group member, registered
 * editor) and the sections/links/editors data loading.
 *
 * @package Dealnews\Indexera\Tests\Model
 */
#[AllowMockObjectsWithoutExpectations]
class PageEditModelTest extends TestCase {

    /**
     * Builds a PageEditModel with a mock repository injected.
     *
     * @param int $page_id
     * @param int $current_user_id
     *
     * @return array{PageEditModel, MockObject}
     */
    protected function makeModel(
        int $page_id = 10,
        int $current_user_id = 1
    ): array {
        $repo  = $this->makeRepositoryMock();
        $model = new PageEditModel([
            'page_id'         => $page_id,
            'current_user_id' => $current_user_id,
        ]);
        $this->setProperty($model, 'repository', $repo);
        return [$model, $repo];
    }

    /**
     * Builds a basic Page owned by the given user with no group.
     *
     * @param int $page_id
     * @param int $user_id
     *
     * @return Page
     */
    protected function makePage(int $page_id, int $user_id): Page {
        $page          = new Page();
        $page->page_id = $page_id;
        $page->user_id = $user_id;
        $page->group_id = 0;
        return $page;
    }

    public function test_zero_page_id_returns_not_found(): void {
        [$model] = $this->makeModel(0, 1);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_zero_user_id_returns_not_found(): void {
        [$model] = $this->makeModel(10, 0);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_missing_page_returns_not_found(): void {
        [$model, $repo] = $this->makeModel(10, 1);

        $repo->method('get')->willReturn(null);

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_owner_can_edit(): void {
        [$model, $repo] = $this->makeModel(10, 1);

        $page = $this->makePage(10, 1);  // user 1 owns it

        $repo->method('get')->willReturn($page);
        $repo->method('find')->willReturn([]);

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertTrue($result['is_owner']);
    }

    public function test_non_owner_without_group_or_editor_record_returns_not_found(): void {
        [$model, $repo] = $this->makeModel(10, 5);

        $page = $this->makePage(10, 99);  // owned by someone else, no group

        $repo->method('get')->willReturn($page);
        $repo->method('find')->willReturn([]);   // no editor record

        $result = $model->getData();

        $this->assertTrue($result['not_found']);
    }

    public function test_group_member_can_edit(): void {
        [$model, $repo] = $this->makeModel(10, 5);

        $page           = $this->makePage(10, 99);
        $page->group_id = 7;  // belongs to a group

        $member                  = new GroupMember();
        $member->group_member_id = 1;

        $repo->method('get')->willReturn($page);
        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($member) {
                 if ($name === 'GroupMember') {
                     return [1 => $member];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertFalse($result['is_owner']);
    }

    public function test_registered_editor_can_edit(): void {
        [$model, $repo] = $this->makeModel(10, 5);

        $page = $this->makePage(10, 99);  // no group

        $editor_record                   = new PageEditor();
        $editor_record->page_editor_id   = 1;
        $editor_record->user_id          = 5;

        $editor_user               = new User();
        $editor_user->user_id      = 5;
        $editor_user->display_name = 'eve';
        $editor_user->email        = 'eve@example.com';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($page, $editor_user) {
                 if ($name === 'Page') {
                     return $page;
                 }
                 if ($name === 'User') {
                     return $editor_user;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($editor_record) {
                 // Access check: filter has user_id.
                 if ($name === 'PageEditor' && isset($filter['user_id'])) {
                     return [1 => $editor_record];
                 }
                 // Full editor list: no user_id in filter.
                 if ($name === 'PageEditor') {
                     return [1 => $editor_record];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertFalse($result['not_found']);
        $this->assertFalse($result['is_owner']);
    }

    public function test_sections_and_links_are_loaded(): void {
        [$model, $repo] = $this->makeModel(10, 1);

        $page = $this->makePage(10, 1);

        $section             = new Section();
        $section->section_id = 3;
        $section->page_id    = 10;
        $section->title      = 'My Section';

        $link             = new Link();
        $link->link_id    = 7;
        $link->section_id = 3;
        $link->label      = 'Example';
        $link->url        = 'https://example.com';

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($page) {
                 if ($name === 'Page') {
                     return $page;
                 }
                 return null;
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name, array $filter) use ($section, $link) {
                 if ($name === 'Section') {
                     return [3 => $section];
                 }
                 if ($name === 'Link') {
                     return [7 => $link];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(1, $result['sections']);
        $this->assertSame('My Section', $result['sections'][0]['title']);
        $this->assertCount(1, $result['sections'][0]['links']);
        $this->assertSame('Example', $result['sections'][0]['links'][0]['label']);
    }

    public function test_editor_with_missing_user_is_skipped(): void {
        [$model, $repo] = $this->makeModel(10, 1);

        $page = $this->makePage(10, 1);

        $orphan_editor                 = new PageEditor();
        $orphan_editor->page_editor_id = 1;
        $orphan_editor->user_id        = 999;  // user row was deleted

        $repo->method('get')
             ->willReturnCallback(function (string $name, int $id) use ($page) {
                 if ($name === 'Page') {
                     return $page;
                 }
                 return null;  // user not found
             });

        $repo->method('find')
             ->willReturnCallback(function (string $name) use ($orphan_editor) {
                 if ($name === 'PageEditor') {
                     return [1 => $orphan_editor];
                 }
                 return [];
             });

        $result = $model->getData();

        $this->assertCount(0, $result['editors']);
    }
}
