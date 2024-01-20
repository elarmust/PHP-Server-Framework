<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Http\Csrf;

use Framework\Http\Session\Session;
use Framework\Configuration\Configuration;

class Csrf {
    private int $defaultExpiration;

    public function __construct(private Configuration $configuration) {
        $this->defaultExpiration = $this->configuration->getConfig('defaultCsrfTokenExpirationSeconds') ?? 86400;
    }

    /**
     * Generate a new CSRF token for Session. Returns the newly created CSRF token key.
     *
     * @param Session &$session
     * @param int $expiration
     * @return string
     */
    public function generateCsrfToken(Session &$session, ?int $expiration = null): string {
        $expiration ??= $this->defaultExpiration;
        $data = $session->getData();
        $key = bin2hex(random_bytes(32));
        $data['csrfTokens'][$key] = ['start' => time(), 'expiration' => $expiration];
        $session->setData($data);
        return $key;
    }

    /**
     * Check if Session object contains a valid token provided.
     * This will also delete matching and expired keys.
     *
     * @param string $token
     * @param Session $session
     * @return bool
     */
    public function validateCsrfToken(string $token, Session $session): bool {
        $return = false;
        foreach ($session->getData()['csrfTokens'] ?? [] as $sessionToken => $tokenData) {
            if ($token === $sessionToken) {
                if (time() - $tokenData['start'] < $tokenData['expiration']) {
                    $sessionData = $session->getData();
                    unset($sessionData['csrfTokens'][$sessionToken]);
                    $session->setData($sessionData);
                    $return = true;
                    break;
                }
            }
        }

        $this->cleanupSession($session);
        return $return;
    }

    /**
     * Cleans the session of expired CSRF tokens.
     *
     * @param Session $session
     * @return void
     */
    public function cleanupSession(Session $session): void {
        $sessionData = $session->getData();
        foreach ($sessionData['csrfTokens'] ?? [] as $sessionToken => $tokenData) {
            if (time() - $tokenData['start'] > $tokenData['expiration']) {
                unset($sessionData['csrfTokens'][$sessionToken]);
            }
        }

        if (isset($sessionData['csrfTokens']) && count($sessionData['csrfTokens'])) {
            unset($sessionData['csrfTokens']);
        }

        $session->setData($sessionData);
    }
}
