<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Responder;

use PageMill\MVC\ResponderAbstract;

/**
 * Generic HTML responder that delegates view selection to the caller.
 *
 * Accepts a fully-qualified view class name at construction time so a
 * single responder class can serve every HTML action without subclassing.
 *
 * @package Dealnews\Indexera\Responder
 */
class HtmlResponder extends ResponderAbstract {

    /**
     * @param string                    $view_class    FQCN of the view to render.
     * @param \PageMill\HTTP\Response|null $http_response Optional HTTP response
     *                                                 object (injected for testing).
     */
    public function __construct(
        protected string $view_class,
        ?\PageMill\HTTP\Response $http_response = null
    ) {
        parent::__construct($http_response);
    }

    /**
     * Returns the view class name supplied at construction.
     *
     * @param array $data   Template variables.
     * @param array $inputs Route tokens / request inputs.
     *
     * @return string Fully-qualified view class name.
     */
    protected function getView(array $data, array $inputs): string {
        return $this->view_class;
    }
}
