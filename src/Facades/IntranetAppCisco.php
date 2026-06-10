<?php

namespace Hwkdo\IntranetAppCisco\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\IntranetAppCisco\IntranetAppCisco
 */
class IntranetAppCisco extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\IntranetAppCisco\IntranetAppCisco::class;
    }
}
