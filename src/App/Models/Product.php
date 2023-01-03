<?php

namespace NTNUI\Swimming\App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $product_hash
 * @property string $name
 * @property string|NULL $description
 * @property int $price
 * @property int|NULL $price_member
 * @property Carbon $available_from
 * @property Carbon $available_until
 * @property int|NULL $max_orders_per_customer_per_year
 * @property bool $require_phone
 * @property bool $require_email
 * @property bool $require_comment
 * @property bool $require_active_membership
 * @property int|NULL $amount_available
 * @property int $image_id
 * @property bool $visible
 * @property bool $enabled
 * 
 */
class Product extends Model
{

    public function new(array $input)
    {
        $required_keys = ["name", "price", "available_from", "available_until", "image_id"];
        foreach ($required_keys as $required_key) {
            if (!array_key_exists($required_key, $input)) {
                throw new \Exception("Missing required key: $required_key");
            }
        }

        $valid_keys = [
            "name",
            "description",
            "price",
            "price_member",
            "available_from",
            "available_until",
            "max_orders_per_customer_per_year",
            "require_phone",
            "require_email",
            "require_comment",
            "require_active_membership",
            "amount_available",
            "image_id",
            "visible",
            "enabled"
        ];

        foreach ($input as $key => $value) {
            if (!in_array($key, $valid_keys)) {
                throw new \Exception("Invalid key: $key");
            }
        }

        $this->name = $input["name"];
        $this->description = $input["description"] ?? NULL;
        $this->price = $input["price"];
        $this->price_member = $input["price_member"] ?? NULL;
        $this->available_from = $input["available_from"];
        $this->available_until = $input["available_until"];
        $this->max_orders_per_customer_per_year = $input["max_orders_per_customer_per_year"] ?? NULL;
        $this->require_phone = $input["require_phone"] ?? false;
        $this->require_email = $input["require_email"] ?? false;
        $this->require_comment = $input["require_comment"] ?? false;
        $this->require_active_membership = $input["require_active_membership"] ?? false;
        $this->amount_available = $input["amount_available"] ?? NULL;
        $this->image_id = $input["image_id"];
        $this->visible = $input["visible"] ?? true;
        $this->enabled = $input["enabled"] ?? true;
        $this->save();
    }
}
