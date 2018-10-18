<?php

namespace TMyers\StripeBilling\Tests\Stubs\Models;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\HasSubscriptions;

class User extends Model implements Authenticatable
{
    use \Illuminate\Auth\Authenticatable, HasSubscriptions;

    protected $guarded = [''];
}