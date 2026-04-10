<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Controller\Auth;

use DealNews\GetConfig\GetConfig;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Google;
use TheNetworg\OAuth2\Client\Provider\Azure;

/**
 * Builds OAuth2 provider instances from application config.
 *
 * Used by OAuthStartController and OAuthCallbackController to avoid
 * duplicating provider construction logic.
 *
 * Supported providers: github, google, microsoft.
 *
 * @package Dealnews\Indexera\Controller\Auth
 */
trait OAuthProviderTrait {

    /**
     * Builds the OAuth2 provider for the given provider name.
     *
     * Returns null for unsupported providers.
     *
     * @param string $provider_name
     *
     * @return \League\OAuth2\Client\Provider\AbstractProvider|null
     */
    protected function buildProvider(string $provider_name): ?\League\OAuth2\Client\Provider\AbstractProvider {
        $config   = GetConfig::init();
        $base_url = (isset($_SERVER['HTTPS']) || getenv('FORCE_HTTPS') ? 'https' : 'http') .
                    '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $provider = match ($provider_name) {
            'github' => new Github([
                'clientId'     => $config->get('oauth.github.client_id'),
                'clientSecret' => $config->get('oauth.github.client_secret'),
                'redirectUri'  => $base_url . '/auth/github/callback',
            ]),
            'google' => new Google([
                'clientId'     => $config->get('oauth.google.client_id'),
                'clientSecret' => $config->get('oauth.google.client_secret'),
                'redirectUri'  => $base_url . '/auth/google/callback',
            ]),
            'microsoft' => new Azure([
                'clientId'     => $config->get('oauth.microsoft.client_id'),
                'clientSecret' => $config->get('oauth.microsoft.client_secret'),
                'redirectUri'  => $base_url . '/auth/microsoft/callback',
            ]),
            default => null,
        };

        return $provider;
    }
}
