<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests para helpers de paginacion keyset (app/helpers/paginacion.php).
 * Solo probamos las funciones puras: cursorCodificar / cursorDesdeRequest /
 * urlPaginacion. keysetFetch requiere PDO y se cubre en tests de integracion.
 */
final class PaginacionKeysetTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once APP_PATH . '/helpers/paginacion.php';
    }

    protected function tearDown(): void
    {
        $_GET = [];
    }

    public function testCodificarYDecodificarEsReversible(): void
    {
        $cur = cursorCodificar('2026-04-14 12:00:00', 42);
        $_GET['cursor'] = $cur;
        $dec = cursorDesdeRequest();
        $this->assertSame(['2026-04-14 12:00:00', 42], $dec);
    }

    public function testCursorAusenteDevuelveNull(): void
    {
        $_GET = [];
        $this->assertNull(cursorDesdeRequest());
    }

    public function testCursorMalFormadoDevuelveNull(): void
    {
        $_GET['cursor'] = 'no-es-base64-valido!!!';
        $this->assertNull(cursorDesdeRequest());
    }

    public function testCursorSinSeparadorDevuelveNull(): void
    {
        // base64url de "solo-un-valor" (sin '|')
        $_GET['cursor'] = rtrim(strtr(base64_encode('solo-un-valor'), '+/', '-_'), '=');
        $this->assertNull(cursorDesdeRequest());
    }

    public function testCursorConIdNoEnteroDevuelveNull(): void
    {
        $_GET['cursor'] = rtrim(strtr(base64_encode('x|abc'), '+/', '-_'), '=');
        $this->assertNull(cursorDesdeRequest());
    }

    public function testCodificacionUsaBase64UrlSafe(): void
    {
        // Valor con chars que al pasar por base64 generan + y / — deben convertirse a - y _.
        $cur = cursorCodificar('????>>>>', 1);
        $this->assertStringNotContainsString('+', $cur);
        $this->assertStringNotContainsString('/', $cur);
        $this->assertStringNotContainsString('=', $cur, 'padding = debe eliminarse');
    }

    public function testUrlPaginacionPreservaQueryYReemplazaCursor(): void
    {
        $_GET = ['modulo' => 'admin', 'accion' => 'contribuyentes', 'q' => 'juan', 'cursor' => 'viejo'];
        $url = urlPaginacion('nuevo_cursor_xyz');
        $this->assertStringStartsWith(BASE_URL . '/?', $url);
        $this->assertStringContainsString('modulo=admin', $url);
        $this->assertStringContainsString('accion=contribuyentes', $url);
        $this->assertStringContainsString('q=juan', $url);
        $this->assertStringContainsString('cursor=nuevo_cursor_xyz', $url);
        $this->assertStringNotContainsString('cursor=viejo', $url);
    }

    public function testUrlPaginacionPermiteExcluirClaves(): void
    {
        $_GET = ['modulo' => 'admin', 'accion' => 'contribuyentes', 'q' => 'juan'];
        $url = urlPaginacion('xyz', ['q']);
        $this->assertStringNotContainsString('q=juan', $url);
        $this->assertStringContainsString('cursor=xyz', $url);
    }
}
