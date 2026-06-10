<?php

namespace Hwkdo\IntranetAppCisco\Models;

use Hwkdo\IntranetAppCisco\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;

class IntranetAppCiscoSettings extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): IntranetAppCiscoSettings|null
    {
        return self::orderBy('version', 'desc')->first();
    }
}
