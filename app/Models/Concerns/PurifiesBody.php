<?php

namespace App\Models\Concerns;

use App\Support\BodyHtml;

/**
 * Passa il campo `body` da BodyHtml::clean() a ogni assegnazione via Eloquent.
 *
 * Copre il percorso CRUD admin (controller -> $model->body = $data['body'] -> save()), che è l'unico
 * modo in cui un editor scrive HTML.
 */
trait PurifiesBody
{
    public function setBodyAttribute(?string $value): void
    {
        $this->attributes['body'] = BodyHtml::clean($value);
    }
}
