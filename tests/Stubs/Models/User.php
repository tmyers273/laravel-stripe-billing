<?php

namespace TMyers\StripeBilling\Tests\Stubs\Models;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Authenticatable
{
    use \Illuminate\Auth\Authenticatable;

    protected $guarded = [''];
}