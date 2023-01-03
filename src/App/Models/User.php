<?php

namespace NTNUI\Swimming\App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $password_hash
 * @property Carbon|NULL $password_updated_at
 */
class User extends Model
{
    protected $fillable = [
        'name',
        "username",
        "password_hash",
    ];
    protected $hidden = ["password_hash"];

    static public function new_from_json(array $json_payload): void
    {
        $user = new self();
        $user->name = $json_payload["name"];
        $user->username = $json_payload["username"];
        $user->password_hash = password_hash($json_payload["password"], \PASSWORD_DEFAULT);
        $user->save();
    }
}
