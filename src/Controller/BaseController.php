<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller;

use DealNews\GetConfig\GetConfig;
use Dealnews\Indexera\Data\Settings;
use Dealnews\Indexera\Data\User;
use Dealnews\Indexera\Repository;
use PageMill\HTTP\Response;
use PageMill\MVC\ControllerAbstract;

/**
 * Base controller for all HTML controllers.
 *
 * Loads the authenticated user from session on every request and
 * provides opt-in auth enforcement via $require_auth.
 *
 * @package Dealnews\Indexera\Controller
 */
abstract class BaseController extends ControllerAbstract {

    /**
     * Set to true in subclasses that require an authenticated user.
     * handleRequest() will redirect to /login when no user is present.
     *
     * @var bool
     */
    protected bool $require_auth = false;

    /**
     * Set to true in subclasses that require admin privileges.
     * handleRequest() will redirect to /dashboard for non-admins.
     *
     * @var bool
     */
    protected bool $require_admin = false;

    /**
     * Set to true in subclasses that need model data available inside
     * getResponder() (e.g. to branch on not_found). When true,
     * buildModels() runs before getResponder() instead of after.
     *
     * @var bool
     */
    protected bool $build_models_first = false;

    /**
     * The authenticated user, or null for guests.
     *
     * @var User|null
     */
    protected ?User $current_user = null;

    /**
     * @param string $request_path Current request path.
     * @param array  $inputs       Route tokens and other request inputs.
     */
    public function __construct(string $request_path, array $inputs = []) {
        parent::__construct($request_path, $inputs);
        $this->loadCurrentUser();
    }

    /**
     * Enforces authentication before dispatching when $require_auth
     * is true, then delegates to the parent pipeline. When
     * $build_models_first is true, models are built before the
     * responder is selected so that getResponder() can branch on
     * model data (e.g. not_found).
     *
     * POST requests are rejected with 403 if the CSRF token is absent
     * or does not match the session token.
     *
     * @return void
     */
    public function handleRequest(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
            !$this->validateCsrfToken())
        {
            http_response_code(403);
            return;
        }

        if ($this->require_auth) {
            $this->requireAuth();
        }

        if ($this->require_admin) {
            $this->requireAdmin();
        }

        if (!$this->build_models_first) {
            parent::handleRequest();
            return;
        }

        // Build models first so getResponder() can inspect $this->data.
        $this->filterInput($this->getFilters());
        $this->buildModels($this->getModels());

        $responder = $this->getResponder();

        if ($responder->acceptable()) {
            $this->doActions($this->getRequestActions(), true);
            $this->doActions($this->getDataActions());
            $responder->respond($this->data, $this->inputs);
        }
    }

    /**
     * Returns the per-session CSRF token, generating one if needed.
     *
     * @return string
     */
    protected function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Returns true if the submitted csrf_token POST field matches
     * the session token. Uses hash_equals() to prevent timing attacks.
     *
     * @return bool
     */
    protected function validateCsrfToken(): bool {
        $submitted = (string)($_POST['csrf_token'] ?? '');
        $expected  = (string)($_SESSION['csrf_token'] ?? '');
        return $expected !== '' && hash_equals($expected, $submitted);
    }

    /**
     * Redirects to /login if there is no authenticated user.
     *
     * @return void
     */
    protected function requireAuth(): void {
        if ($this->current_user === null) {
            Response::init()->redirect('/login');
        }
    }

    /**
     * Returns a 403 and redirects to /dashboard if the current user
     * is not an admin (or is not logged in).
     *
     * @return void
     */
    protected function requireAdmin(): void {
        if ($this->current_user === null || !$this->current_user->is_admin) {
            http_response_code(403);
            Response::init()->redirect('/dashboard');
        }
    }

    /**
     * Loads the session user and application settings into $this->data
     * so views receive them automatically.
     *
     * @return void
     */
    protected function loadCurrentUser(): void {
        $repository = new Repository();

        if (!empty($_SESSION['user_id'])) {
            $this->current_user             = $repository->get('User', (int)$_SESSION['user_id']);
            $this->data['current_user']     = $this->current_user;
        }

        $this->data['settings']    = $repository->get('Settings', 1) ?? new Settings();
        $this->data['csrf_token']  = $this->generateCsrfToken();

        $config                          = GetConfig::init();
        $this->data['oauth_github']      = $config->get('oauth.github.client_id') !== null;
        $this->data['oauth_google']      = $config->get('oauth.google.client_id') !== null;
        $this->data['oauth_microsoft']   = $config->get('oauth.microsoft.client_id') !== null;
    }
}
