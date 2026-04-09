<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use Dealnews\Indexera\Data\Group;
use Dealnews\Indexera\Data\GroupMember;
use Dealnews\Indexera\Repository;
use Dealnews\Indexera\Responder\HtmlResponder;
use Dealnews\Indexera\View\GroupCreateView;
use PageMill\HTTP\Response;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles group creation form submission.
 *
 * Validates input, generates a slug from the name, creates the group,
 * and adds the creator as the first member. On success, redirects to
 * the new group's home page.
 *
 * Requires authentication.
 *
 * @package Dealnews\Indexera\Controller
 */
class GroupCreatePostController extends BaseController {

    /**
     * Require an authenticated user.
     *
     * @var bool
     */
    protected bool $require_auth = true;

    /**
     * Error message to display if creation fails.
     *
     * @var string
     */
    protected string $error = '';

    /**
     * Returns no models — all data comes from POST.
     *
     * @return array
     */
    protected function getModels(): array {
        return [];
    }

    /**
     * Returns the create form view, carrying back any validation error.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(GroupCreateView::class);
    }

    /**
     * Validates and creates the group, then redirects on success.
     *
     * @return void
     */
    public function handleRequest(): void {
        if (!$this->validateCsrfToken()) {
            http_response_code(403);
            return;
        }

        if ($this->current_user === null) {
            Response::init()->redirect('/login');
            return;
        }

        $name        = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            $this->data['error'] = 'Group name is required.';
            $responder           = $this->getResponder();
            $responder->respond($this->data, $this->inputs);
            return;
        }

        $slug = $this->generateSlug($name);

        if ($slug === '') {
            $this->data['error'] = 'Group name must contain at least one alphanumeric character.';
            $responder           = $this->getResponder();
            $responder->respond($this->data, $this->inputs);
            return;
        }

        $repository = Repository::init();
        $existing   = $repository->find('Group', ['slug' => $slug], limit: 1);

        if (!empty($existing)) {
            $this->data['error'] = 'A group with that name already exists.';
            $responder           = $this->getResponder();
            $responder->respond($this->data, $this->inputs);
            return;
        }

        $group              = new Group();
        $group->slug        = $slug;
        $group->name        = $name;
        $group->description = $description !== '' ? $description : null;
        $group->created_by  = $this->current_user->user_id;

        $group = $repository->save('Group', $group);

        $member           = new GroupMember();
        $member->group_id = $group->group_id;
        $member->user_id  = $this->current_user->user_id;
        $repository->save('GroupMember', $member);

        Response::init()->redirect('/groups/' . $group->slug);
    }

    /**
     * Converts a name string to a URL-safe slug.
     *
     * @param string $name The raw name to slugify.
     *
     * @return string
     */
    protected function generateSlug(string $name): string {
        $slug = mb_strtolower($name, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?? '';
    }
}
