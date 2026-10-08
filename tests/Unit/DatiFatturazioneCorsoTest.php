<?php

namespace Tests\Unit;

use App\Models\DatiFatturazioneCorso;
use PHPUnit\Framework\TestCase;

class DatiFatturazioneCorsoTest extends TestCase
{
    public function test_fatturazione_come_iscritto_copia_dati_del_nominativo_come_privato(): void
    {
        $nominativo = [
            'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'm@example.test', 'telefono' => '333',
            'codice_fiscale' => 'rssmra80a01h501u', 'via' => 'Via Roma 1', 'comune' => 'Bologna',
            'provincia' => 'BO', 'cap' => '40100',
        ];

        $dati = DatiFatturazioneCorso::datiDaInput(
            DatiFatturazioneCorso::inputDaNominativo($nominativo, 'Bonifico'), $nominativo
        );

        $this->assertSame('privato', $dati['tipo']);
        $this->assertSame('Mario', $dati['nome']);
        $this->assertSame('RSSMRA80A01H501U', $dati['codice_fiscale']);
        $this->assertSame('Via Roma 1', $dati['via']);
        $this->assertSame('m@example.test', $dati['email']);
        $this->assertSame('Bonifico', $dati['metodo_pagamento']);
        $this->assertNull($dati['ragione_sociale']);
    }
}
