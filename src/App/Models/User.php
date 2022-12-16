<?php

namespace NTNUI\Swimming\App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $password_hash
 * @property Carbon? $password_updated_at
 */
class User extends Model
{
    protected $fillable = [
        'name', // add this line to the fillable array
        "username",
        "password_hash",
    ];
}
