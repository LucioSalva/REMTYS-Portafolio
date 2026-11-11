<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests puros (sin BD) para helpers en app/helpers/audit.php.
 * Cubren: correlationId, enmascararEmail, sanitizarContextoLog.
 */
final class AuditHelpersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once APP_PATH . '/helpers/audit.php';
    }

    public function testCorrelationIdEsEstableEnLaMismaRequest(): void
    {
        $a = correlationId();
        $b = correlationId();
        $this->assertSame($a, $b, 'correlationId debe memorizarse tras la primera llamada');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-]{8,40}$/', $a);
    }

    public function testEnmascararEmailNormal(): void
    {
        $res = enmascararEmail('juan.perez@ejemplo.com');
        $this->assertStringStartsWith('ju', $res);
        $this->assertStringContainsString('@ejemplo.com', $res);
        $this->assertStringContainsString('*', $res);
        $this->assertNotSame('juan.perez@ejemplo.com', $res);
    }

    public function testEnmascararEmailMuyCortoNoRompe(): void
    {
        $res = enmascararEmail('a@b.co');
        $this->assertSame('a@b.co', $res, 'usuarios de 1-2 chars no se enmascaran para no perder semantica');
    }

    public function testEnmascararEmailInvalidoDevuelveMarcador(): void
    {
        $this->assertSame('***@***', enmascararEmail('no-es-un-email'));
    }

    public function testSanitizarRedactaCamposSensibles(): void
    {
        $in = [
            'usuario_id' => 42,
            'password'   => 'secreto',
            'token'      => 'abc123',
            'cookie'     => 'sid=xxx',
            'dato_ok'    => 'visible',
        ];
        $out = sanitizarContextoLog($in);
        $this->assertSame(42,          $out['usuario_id']);
        $this->assertSame('[REDACTED]', $out['password']);
        $this->assertSame('[REDACTED]', $out['token']);
        $this->assertSame('[REDACTED]', $out['cookie']);
        $this->assertSame('visible',   $out['dato_ok']);
    }

    public function testSanitizarTruncaStringsLargos(): void
    {
        $in = ['payload' => str_repeat('x', 900)];
        $out = sanitizarContextoLog($in);
        $this->assertStringEndsWith('...[TRUNCATED]', $out['payload']);
        $this->assertLessThanOrEqual(120, strlen($out['payload']));
    }

    public function testSanitizarRespectaValoresNoString(): void
    {
        $in = ['contador' => 7, 'flag' => true, 'lista' => [1, 2, 3]];
        $out = sanitizarContextoLog($in);
        $this->assertSame(7,            $out['contador']);
        $this->assertSame(true,         $out['flag']);
        $this->assertSame([1, 2, 3],    $out['lista']);
    }
}
