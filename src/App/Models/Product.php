<?php

namespace NTNUI\Swimming\App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $product_hash
 * @property string $name
 * @property string? $description
 * @property int $price
 * @property int? $price_member
 * @property Carbon $available_from
 * @property Carbon $available_until
 * @property int? $max_orders_per_customer_per_year
 * @property bool $require_phone
 * @property bool $require_email
 * @property bool $require_comment
 * @property bool $require_active_membership
 * @property int? $amount_available
 * @property int $image_id
 * @property bool $visible
 * @property bool $enabled
 */
class Product extends Model
{
}
