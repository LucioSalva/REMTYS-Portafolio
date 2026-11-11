<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests de validacion de entrada de FolioService (sin BD).
 * Contrato: reservarFolio rechaza tipos desconocidos y anios fuera de rango
 * ANTES de abrir transaccion/tocar la BD.
 */
final class FolioServiceValidationTest extends TestCase
{
    private FolioService $svc;

    public static function setUpBeforeClass(): void
    {
        require_once APP_PATH . '/services/FolioService.php';
    }

    protected function setUp(): void
    {
        $pdo = $this->createMock(PDO::class);
        // Aseguramos que cualquier llamada a PDO dispararia un fallo del test:
        // si la validacion de entrada fallara, el mock se invocaria y el test
        // explotaria con "unexpected method call".
        $pdo->expects($this->never())->method('beginTransaction');
        $pdo->expects($this->never())->method('prepare');
        $this->svc = new FolioService($pdo);
    }

    public function testTipoInvalidoLanzaInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Tipo de folio no soportado/i');
        $this->svc->reservarFolio('XYZ');
    }

    public function testTipoVacioLanzaInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->reservarFolio('   ');
    }

    public function testAnioDemasiadoChicoLanzaInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Ano fuera de rango|A.o fuera de rango/u');
        $this->svc->reservarFolio('EXP', null, null, 1999);
    }

    public function testAnioDemasiadoGrandeLanzaInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->reservarFolio('EXP', null, null, 3000);
    }

    public function testAnularFolioSinMotivoLanzaInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/motivo/i');
        $this->svc->anularFolio('EXP-2026-000001', '');
    }

    public function testAnularFolioConMotivoSoloEspaciosRechazado(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->anularFolio('EXP-2026-000001', "   \t\n  ");
    }
}
