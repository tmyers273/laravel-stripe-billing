<?php

namespace TMyers\StripeBilling\Tests\Stubs\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Billable;

class User extends Model implements Authenticatable
{
    use \Illuminate\Auth\Authenticatable, Billable;

    protected $guarded = [''];
}
