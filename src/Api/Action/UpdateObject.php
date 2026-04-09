<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Api\Action;

use Moonspot\ValueObjects\Interfaces\Export;

/**
 * Authenticated, ownership-checked create and update action.
 *
 * On create: auto-injects user_id for objects that own it directly,
 * and verifies parent ownership for Section and Link. Admins may
 * create User accounts directly, including setting is_admin.
 * On update: verifies the existing object is owned by the session user,
 * then strips the POST body to only the fields in UPDATE_ALLOWLIST (if
 * an entry exists for the object type).
 *
 * @package Dealnews\Indexera\Api\Action
 */
class UpdateObject extends \DealNews\DataMapperAPI\Action\UpdateObject {

    use AuthTrait;
    use OwnershipTrait;

    /**
     * Field allowlists for PUT requests, keyed by object name.
     * Objects not listed here are unrestricted.
     *
     * @var array<string, string[]>
     */
    protected const UPDATE_ALLOWLIST = [
        'User'  => ['display_name', 'avatar_url'],
        'Group' => ['name', 'description'],
    ];

    /**
     * Fields that must be stripped from POST data on create for
     * non-admin callers, keyed by object name.
     *
     * @var array<string, string[]>
     */
    protected const CREATE_STRIPLIST = [
        'User' => ['is_admin'],
    ];

    /**
     * Enforces ownership before delegating to the parent.
     *
     * @throws \LogicException When ownership cannot be verified.
     *
     * @return array
     */
    public function loadData(): array {
        $user_id = (int)$_SESSION['user_id'];

        if ($this->object_id > 0) {
            // Update — verify the existing object is owned.
            $object = $this->repository->get(
                $this->object_name,
                $this->object_id
            );

            if ($object === null || !$this->isOwned($object)) {
                return [
                    'http_status' => 403,
                    'error'       => 'Forbidden',
                ];
            }

            $this->filterPostData();

            if ($this->object_name === 'User') {
                $post_data = json_decode($this->post_data, true) ?? [];
                if (isset($post_data['display_name'])) {
                    $username = strtolower(trim((string)$post_data['display_name']));
                    if (!preg_match('/^[a-z0-9_-]+$/', $username)) {
                        return [
                            'http_status' => 422,
                            'error'       => 'Username may only contain lowercase ' .
                                             'letters, numbers, hyphens, and underscores.',
                        ];
                    }
                    $post_data['display_name'] = $username;
                    $this->post_data           = json_encode($post_data);
                }
            }
        } else {
            // Create — inject or verify ownership via post data.
            $post_data = json_decode($this->post_data, true) ?? [];

            switch ($this->object_name) {
                case 'Section':
                    $page_id = (int)($post_data['page_id'] ?? 0);
                    if ($page_id === 0 || !$this->isPageEditable($page_id, $user_id)) {
                        throw new \LogicException(
                            'A valid page_id editable by the current user is required'
                        );
                    }
                    break;

                case 'Link':
                    $section_id = (int)($post_data['section_id'] ?? 0);
                    if ($section_id === 0 || !$this->isSectionEditable($section_id, $user_id)) {
                        throw new \LogicException(
                            'A valid section_id editable by the current user is required'
                        );
                    }
                    break;

                case 'PageEditor':
                    $page_id = (int)($post_data['page_id'] ?? 0);
                    $email   = trim((string)($post_data['email'] ?? ''));

                    if ($page_id === 0 || !$this->isPageEditable($page_id, $user_id)) {
                        return [
                            'http_status' => 403,
                            'error'       => 'Forbidden',
                        ];
                    }

                    if ($email === '') {
                        return [
                            'http_status' => 400,
                            'error'       => 'email is required',
                        ];
                    }

                    $target_users = $this->repository->find(
                        'User',
                        ['email' => $email],
                        limit: 1
                    );
                    $target_user  = !empty($target_users) ? reset($target_users) : null;

                    if ($target_user === null) {
                        return [
                            'http_status' => 404,
                            'error'       => 'No user found with that email address.',
                        ];
                    }

                    $page = $this->repository->get('Page', $page_id);

                    if ($page !== null &&
                        (int)$page->user_id === (int)$target_user->user_id)
                    {
                        return [
                            'http_status' => 422,
                            'error'       => 'The page owner cannot be added as an editor.',
                        ];
                    }

                    $existing = $this->repository->find('PageEditor', [
                        'page_id' => $page_id,
                        'user_id' => $target_user->user_id,
                    ], limit: 1);

                    if (!empty($existing)) {
                        return [
                            'http_status' => 409,
                            'error'       => 'That user is already an editor of this page.',
                        ];
                    }

                    $post_data['user_id'] = $target_user->user_id;
                    unset($post_data['email']);
                    $this->post_data = json_encode($post_data);
                    break;

                case 'PageSubscription':
                    $page_id = (int)($post_data['page_id'] ?? 0);
                    $page    = $page_id > 0
                        ? $this->repository->get('Page', $page_id)
                        : null;

                    if ($page === null) {
                        return [
                            'http_status' => 404,
                            'error'       => 'Page not found.',
                        ];
                    }

                    // Group pages: members may subscribe even if not public.
                    if (!$page->is_public) {
                        $can_access = (int)$page->group_id > 0 &&
                                      $this->isGroupMember((int)$page->group_id, $user_id);

                        if (!$can_access) {
                            return [
                                'http_status' => 404,
                                'error'       => 'Page not found.',
                            ];
                        }
                    }

                    if ((int)$page->user_id === $user_id) {
                        return [
                            'http_status' => 403,
                            'error'       => 'You cannot subscribe to your own page.',
                        ];
                    }

                    $post_data['user_id'] = $user_id;
                    $this->post_data      = json_encode($post_data);
                    break;

                case 'Group':
                    $name = trim((string)($post_data['name'] ?? ''));
                    if ($name === '') {
                        return [
                            'http_status' => 400,
                            'error'       => 'name is required',
                        ];
                    }

                    $slug = $this->generateSlug($name);
                    if ($slug === '') {
                        return [
                            'http_status' => 400,
                            'error'       => 'name must contain at least one alphanumeric character',
                        ];
                    }

                    $existing = $this->repository->find('Group', ['slug' => $slug], limit: 1);
                    if (!empty($existing)) {
                        return [
                            'http_status' => 409,
                            'error'       => 'A group with that name already exists.',
                        ];
                    }

                    $post_data['slug']       = $slug;
                    $post_data['created_by'] = $user_id;
                    $this->post_data         = json_encode($post_data);
                    break;

                case 'GroupMember':
                    $group_id = (int)($post_data['group_id'] ?? 0);
                    $email    = trim((string)($post_data['email'] ?? ''));

                    if ($group_id === 0 || !$this->isGroupMember($group_id, $user_id)) {
                        return [
                            'http_status' => 403,
                            'error'       => 'Forbidden',
                        ];
                    }

                    if ($email === '') {
                        return [
                            'http_status' => 400,
                            'error'       => 'email is required',
                        ];
                    }

                    $target_users = $this->repository->find(
                        'User',
                        ['email' => $email],
                        limit: 1
                    );
                    $target_user  = !empty($target_users) ? reset($target_users) : null;

                    if ($target_user === null) {
                        return [
                            'http_status' => 404,
                            'error'       => 'No user found with that email address.',
                        ];
                    }

                    $existing = $this->repository->find('GroupMember', [
                        'group_id' => $group_id,
                        'user_id'  => $target_user->user_id,
                    ], limit: 1);

                    if (!empty($existing)) {
                        return [
                            'http_status' => 409,
                            'error'       => 'That user is already a member of this group.',
                        ];
                    }

                    $post_data['user_id'] = $target_user->user_id;
                    unset($post_data['email']);
                    $this->post_data = json_encode($post_data);
                    break;

                case 'User':
                    // Normalize and validate username.
                    $raw_username = trim((string)($post_data['display_name'] ?? ''));
                    if ($raw_username !== '') {
                        $username = strtolower($raw_username);
                        if (!preg_match('/^[a-z0-9_-]+$/', $username)) {
                            return [
                                'http_status' => 422,
                                'error'       => 'Username may only contain lowercase ' .
                                                 'letters, numbers, hyphens, and underscores.',
                            ];
                        }
                        $post_data['display_name'] = $username;
                    }

                    // Admins may create user accounts with full control,
                    // including setting is_admin. Passwords are hashed here
                    // before the parent saves the object.
                    $session_user = $this->repository->get('User', $user_id);

                    if ($session_user !== null && $session_user->is_admin) {
                        if (!empty($post_data['password'])) {
                            $post_data['password'] = password_hash(
                                $post_data['password'],
                                PASSWORD_DEFAULT
                            );
                        } else {
                            unset($post_data['password']);
                        }
                        $this->post_data = json_encode($post_data);
                        break;
                    }

                    // Non-admin: force user_id and strip protected fields.
                    $post_data['user_id'] = $user_id;
                    $strip                = array_flip(self::CREATE_STRIPLIST['User']);
                    $post_data            = array_diff_key($post_data, $strip);
                    $this->post_data      = json_encode($post_data);
                    break;

                default:
                    // Force user_id to the session user for all other objects,
                    // and strip any fields that must never be set via the API.
                    $post_data['user_id'] = $user_id;
                    if (isset(self::CREATE_STRIPLIST[$this->object_name])) {
                        $strip     = array_flip(self::CREATE_STRIPLIST[$this->object_name]);
                        $post_data = array_diff_key($post_data, $strip);
                    }
                    $this->post_data = json_encode($post_data);
                    break;
            }
        }

        $result = parent::loadData();

        // After a Group is created, add the creator as the first member.
        if ($this->object_name === 'Group' &&
            $this->object_id === 0 &&
            !empty($result) &&
            empty($result['error']))
        {
            $saved_data = json_decode($this->post_data, true) ?? [];
            $slug       = (string)($saved_data['slug'] ?? '');
            $new_groups = !empty($slug)
                ? $this->repository->find('Group', ['slug' => $slug], limit: 1)
                : [];
            $new_group  = !empty($new_groups) ? reset($new_groups) : null;

            if ($new_group !== null) {
                $member           = new \Dealnews\Indexera\Data\GroupMember();
                $member->group_id = (int)$new_group->group_id;
                $member->user_id  = $user_id;
                $this->repository->save('GroupMember', $member);
            }
        }

        return $result;
    }

    /**
     * Strips the POST body to only allowed fields for the current object,
     * if an allowlist entry exists.
     *
     * @return void
     */
    protected function filterPostData(): void {
        if (!isset(self::UPDATE_ALLOWLIST[$this->object_name])) {
            return;
        }

        $allowed         = self::UPDATE_ALLOWLIST[$this->object_name];
        $post_data       = json_decode($this->post_data, true) ?? [];
        $post_data       = array_intersect_key($post_data, array_flip($allowed));
        $this->post_data = json_encode($post_data);
    }

    /**
     * Formats the object for the API response, stripping the password
     * hash so it is never returned to clients.
     *
     * @param Export $data The saved object.
     *
     * @return array
     */
    protected function formatObject(Export $data): array {
        $result = parent::formatObject($data);
        unset($result['password']);

        if ($this->object_name === 'PageEditor' && isset($result['user_id'])) {
            $editor_user = $this->repository->get('User', (int)$result['user_id']);
            if ($editor_user !== null) {
                $result['display_name'] = $editor_user->display_name;
                $result['email']        = $editor_user->email;
            }
        }

        if ($this->object_name === 'GroupMember' && isset($result['user_id'])) {
            $member_user = $this->repository->get('User', (int)$result['user_id']);
            if ($member_user !== null) {
                $result['display_name'] = $member_user->display_name;
                $result['email']        = $member_user->email;
            }
        }

        return $result;
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
