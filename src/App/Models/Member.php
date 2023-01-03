<?php

namespace NTNUI\Swimming\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property Gender $gender
 * @property Carbon $birth_date
 * @property string $phone
 * @property string $email
 * @property string $address
 * @property int $zip
 * @property string? $license
 * @property bool $have_volunteered
 * @property int $cin_id
 * @property Carbon $approved_at
 * @property Carbon $license_forwarded_at
 */
class Member extends Model
{
    const HASH_ALGORITHM = "sha256";
    function set_cin(int $customer_identification_number): void
    {
        // TODO: validate cin number
        $cin_row = Cin::new();
        $cin_row->cin = $customer_identification_number;
        $cin_row->member_hash = $this->generate_member_hash();
        $cin_row->save();
    }

    function get_cin(): ?int
    {
        // I have no idea if this will work
        return Cin::where("id", $this->cin_id);
    }

    private function generate_member_hash()
    {
        return hash(self::HASH_ALGORITHM, $this->birth_date . $this->phone . $this->gender);
    }


    static function register(array $enrollment_data)
    {
        $missing_keys = [];
        foreach (["name", "birth_date", "phone", "gender", "email", "address", "zip"] as $key) {
            if ($enrollment_data[$key] === null) {
                $missing_keys[] = $key;
            }
        }
        if (count($missing_keys) > 0) {
            // TODO: ApiException::missingArgument("Following inputs...");
            throw new \InvalidArgumentException("Following input are missing: [" . implode(", ", $missing_keys) . "]");
        }

        // TODO: validate all fields before saving

        $member = self::new();
        $member->name = $enrollment_data["name"];
        $member->birth_date = Carbon::createFromFormat("Y-m-d", $enrollment_data["birth_date"]);
        $member->phone = $enrollment_data["phone"];
        $member->gender = $enrollment_data["gender"];
        $member->email = $enrollment_data["email"];
        $member->address = $enrollment_data["address"];
        $member->zip = $enrollment_data["zip"];
        $member->license = $enrollment_data["license"];
        $member->save();
    }
}
