<?php

namespace Tests\Unit;

use App\Support\DbDump;
use PDO;
use PHPUnit\Framework\TestCase;

class DbDumpTest extends TestCase
{
    /** Le righe generate, rieseguite su un database vuoto, riproducono i dati (apici, a capo, NULL, numeri). */
    public function test_insert_si_riesegue_e_riproduce_le_righe(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER, nome TEXT, nota TEXT, prezzo REAL)');
        $righe = [
            ['id' => 1, 'nome' => "L'Aquila \"città\"", 'nota' => "riga1\nriga2", 'prezzo' => 12.5],
            ['id' => 2, 'nome' => 'Rossi', 'nota' => null, 'prezzo' => 0.0],
        ];

        $sql = DbDump::insertSql($pdo, 't', ['id', 'nome', 'nota', 'prezzo'], $righe);
        $pdo->exec($sql);

        $this->assertStringContainsString('NULL', $sql);
        $this->assertSame($righe, $pdo->query('SELECT * FROM t ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
    }

    public function test_percorso_rifiuta_nomi_non_validi(): void
    {
        $this->assertNull(DbDump::percorso('../.env'));
        $this->assertNull(DbDump::percorso('niles-20260101-000000-zzzzzzzz.sql'));
    }
}
