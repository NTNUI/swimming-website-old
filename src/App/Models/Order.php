<?php

namespace NTNUI\Swimming\App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $email
 * @property string|NULL $phone
 * @property string $intent_id
 * @property OrderStatus $order_status
 * @property string $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 */
class Order extends Model
{
}
