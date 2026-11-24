<?php

declare(strict_types=1);

/*
 * Shared setup for the examples. Run any example from the repository root, for example:
 *
 *   WSIGN_API_KEY=sk_test_... php examples/create-signature-request.php tpl_123 you@example.com
 *
 * WSIGN_API_BASE overrides the base URL (default https://wsign.io/api/v1).
 */

require __DIR__ . '/../vendor/autoload.php';

$apiKey = getenv('WSIGN_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set WSIGN_API_KEY to a test key (sk_test_...) from https://developers.wsign.io\n");
    exit(1);
}

$options = [];
$base = getenv('WSIGN_API_BASE');
if (is_string($base) && $base !== '') {
    $options['base_url'] = $base;
}

return new Wsign\Client($apiKey, $options);
