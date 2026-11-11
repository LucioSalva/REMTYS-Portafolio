<?php
declare(strict_types=1);

/**
 * Helper CSP - Nonce por request para permitir <script> y <style> inline
 * sin depender de 'unsafe-inline' en la politica de seguridad.
 *
 * Uso:
 *   - Header:   Content-Security-Policy: script-src 'self' 'nonce-<?= cspNonce() ?>'; ...
 *   - Vista:    <script nonce="<?= cspNonce() ?>"> ... </script>
 *               <style  nonce="<?= cspNonce() ?>"> ... </style>
 */

function cspNonce(): string {
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}
