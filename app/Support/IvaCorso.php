<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * IVA dei corsi (tipologia e corso): il costo è sempre il prezzo finale, comprensivo di IVA se prevista; l'IVA serve solo
 * a scorporare gli importi nella guida alla fattura. Regole di validazione, normalizzazione e scorporo in un solo punto.
 */
class IvaCorso
{
    public const ALIQUOTA_PREDEFINITA = 22;

    /** @return array<string, array> */
    public static function regole(): array
    {
        return [
            'iva_prevista' => ['boolean'],
            'aliquota_iva' => ['nullable', 'required_if:iva_prevista,1', 'numeric', 'min:0.01', 'max:99.99'],
        ];
    }

    /** Aliquota per l'interfaccia: «22%», «5,5%». */
    public static function aliquotaTesto(float|string|null $aliquota): string
    {
        return rtrim(rtrim(number_format((float) $aliquota, 2, ',', ''), '0'), ',').'%';
    }

    /** Casella → booleano; aliquota vuota se l'IVA non è prevista. Con costo 0 (campo $costoKey) l'IVA non si può attivare. */
    public static function normalizza(array $data, Request $request, string $costoKey): array
    {
        $data['iva_prevista'] = $request->boolean('iva_prevista') && (float) ($data[$costoKey] ?? 0) > 0;
        $data['aliquota_iva'] = $data['iva_prevista'] ? ($data['aliquota_iva'] ?? null) : null;

        return $data;
    }

    /**
     * Scorpora l'IVA da un importo lordo (centesimi). L'imponibile è arrotondato al centesimo e l'IVA è la differenza,
     * così imponibile + IVA = lordo sempre.
     *
     * @return array{imponibile: int, iva: int}
     */
    public static function scorpora(int $lordoCent, float $aliquota): array
    {
        $imponibile = (int) round($lordoCent / (1 + $aliquota / 100));

        return ['imponibile' => $imponibile, 'iva' => $lordoCent - $imponibile];
    }
}
