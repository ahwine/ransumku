<?php

namespace App\Http\Controllers;

use App\Models\Farm;

abstract class Controller
{
    /** Kandang aktif milik user yang sedang login. */
    protected function farm(): Farm
    {
        return auth()->user()->activeFarm() ?? auth()->user()->farm;
    }

    /** Otorisasi: model harus milik kandang user (selain itu 404). */
    protected function own($model)
    {
        abort_unless($model->farm_id === $this->farm()->id, 404);

        return $model;
    }
}
