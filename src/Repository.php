<?php

declare(strict_types=1);

namespace Dealnews\Indexera;

use DealNews\DB\CRUD;
use Dealnews\Indexera\Mapper\GroupMapper;
use Dealnews\Indexera\Mapper\GroupMemberMapper;
use Dealnews\Indexera\Mapper\LinkMapper;
use Dealnews\Indexera\Mapper\PageEditorMapper;
use Dealnews\Indexera\Mapper\PageMapper;
use Dealnews\Indexera\Mapper\PageSubscriptionMapper;
use Dealnews\Indexera\Mapper\SectionMapper;
use Dealnews\Indexera\Mapper\SessionMapper;
use Dealnews\Indexera\Mapper\SettingsMapper;
use Dealnews\Indexera\Mapper\UserIdentityMapper;
use Dealnews\Indexera\Mapper\UserMapper;

/**
 * Central repository for loading and saving application objects.
 *
 * Registered names: User, UserIdentity, Group, GroupMember, Page,
 * Section, Link, PageSubscription, PageEditor, Session, Settings.
 *
 * @package Dealnews\Indexera
 */
class Repository extends \DealNews\DataMapper\Repository {

    /**
     * Creates the repository and registers all mappers.
     *
     * @param CRUD|null $crud Optional CRUD instance injected into
     *                        each mapper, primarily for testing.
     */
    public function __construct(?CRUD $crud = null) {
        parent::__construct([
            'User'             => new UserMapper($crud),
            'UserIdentity'     => new UserIdentityMapper($crud),
            'Group'            => new GroupMapper($crud),
            'GroupMember'      => new GroupMemberMapper($crud),
            'Page'             => new PageMapper($crud),
            'Section'          => new SectionMapper($crud),
            'Link'             => new LinkMapper($crud),
            'PageSubscription' => new PageSubscriptionMapper($crud),
            'PageEditor'       => new PageEditorMapper($crud),
            'Session'          => new SessionMapper($crud),
            'Settings'         => new SettingsMapper($crud),
        ]);
    }
}
