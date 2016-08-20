<?php
namespace Aishan\LaravelLiumi\Facades;
use Illuminate\Support\Facades\Facade;
class Liumi extends Facade{
    protected static function getFacadeAccessor()
    {
        return 'liumi';
    }
}