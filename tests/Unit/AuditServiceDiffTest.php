<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests para logica pura de AuditService (diff/sanitizar) via Reflection.
 * No tocan BD: validan el calculo de campos_diff que acompana cada registro
 * en audit_cambios.
 */
final class AuditServiceDiffTest extends TestCase
{
    private AuditService $svc;

    public static function setUpBeforeClass(): void
    {
        require_once APP_PATH . '/services/AuditService.php';
    }

    protected function setUp(): void
    {
        // AuditService requiere PDO en constructor, pero los metodos privados
        // que probamos no lo usan. Un mock minimo evita abrir conexion.
        $pdo = $this->createMock(PDO::class);
        $this->svc = new AuditService($pdo);
    }

    private function invocar(string $metodo, array $args)
    {
        $ref = new ReflectionMethod(AuditService::class, $metodo);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->svc, $args);
    }

    public function testDiffInsertIncluyeTodasLasLlaves(): void
    {
        $diff = $this->invocar('calcularDiff', [null, ['a' => 1, 'b' => 'x']]);
        $this->assertSame(['antes' => null, 'despues' => 1],   $diff['a']);
        $this->assertSame(['antes' => null, 'despues' => 'x'], $diff['b']);
    }

    public function testDiffDeleteIncluyeTodasLasLlaves(): void
    {
        $diff = $this->invocar('calcularDiff', [['a' => 1, 'b' => 'x'], null]);
        $this->assertSame(['antes' => 1,   'despues' => null], $diff['a']);
        $this->assertSame(['antes' => 'x', 'despues' => null], $diff['b']);
    }

    public function testDiffUpdateSoloIncluyeLlavesCambiadas(): void
    {
        $old = ['a' => 1, 'b' => 'x', 'c' => 'igual'];
        $new = ['a' => 2, 'b' => 'x', 'c' => 'igual'];
        $diff = $this->invocar('calcularDiff', [$old, $new]);
        $this->assertArrayHasKey('a', $diff);
        $this->assertArrayNotHasKey('b', $diff, 'columna estable no debe estar en diff');
        $this->assertArrayNotHasKey('c', $diff);
        $this->assertSame(['antes' => 1, 'despues' => 2], $diff['a']);
    }

    public function testDiffUpdateSinCambiosDevuelveNull(): void
    {
        $old = ['a' => 1, 'b' => 'x'];
        $new = ['a' => 1, 'b' => 'x'];
        $diff = $this->invocar('calcularDiff', [$old, $new]);
        $this->assertNull($diff);
    }

    public function testDiffDetectaLlavesNuevasYEliminadas(): void
    {
        $old = ['a' => 1];
        $new = ['a' => 1, 'b' => 'nuevo'];
        $diff = $this->invocar('calcularDiff', [$old, $new]);
        $this->assertSame(['antes' => null, 'despues' => 'nuevo'], $diff['b']);
    }

    public function testSanitizarEnmascaraCamposSensibles(): void
    {
        $out = $this->invocar('sanitizar', [[
            'id'       => 1,
            'password' => 'plaintext',
            'token'    => 'abc',
            'db_pass'  => 'admin',
            'nombre'   => 'Juan',
        ]]);
        $this->assertSame(1,          $out['id']);
        $this->assertSame('Juan',     $out['nombre']);
        $this->assertSame('[REDACTED]', $out['password']);
        $this->assertSame('[REDACTED]', $out['token']);
        $this->assertSame('[REDACTED]', $out['db_pass']);
    }

    public function testSanitizarTruncaStringsMuyLargos(): void
    {
        $grande = str_repeat('x', 2500);
        $out = $this->invocar('sanitizar', [['blob' => $grande]]);
        $this->assertStringEndsWith('...[TRUNCATED]', $out['blob']);
        $this->assertLessThanOrEqual(2100, strlen($out['blob']));
    }
}
