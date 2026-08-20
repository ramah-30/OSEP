<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A pool of extra client accounts that act as reviewers for planners and hotels
 * (so those listings can show a believable number of distinct reviews). Real
 * demo logins (@osep.test / Password123!), idempotent on email, non-production.
 */
class DemoReviewersSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    /** @var array<int, array{0:string,1:string,2:string}> [email, first, last] */
    private const PEOPLE = [
        ['grace.mwakalinga@osep.test', 'Grace', 'Mwakalinga'],
        ['emmanuel.shirima@osep.test', 'Emmanuel', 'Shirima'],
        ['neema.kileo@osep.test', 'Neema', 'Kileo'],
        ['baraka.mushi@osep.test', 'Baraka', 'Mushi'],
        ['aisha.juma@osep.test', 'Aisha', 'Juma'],
        ['joseph.laizer@osep.test', 'Joseph', 'Laizer'],
        ['fatuma.ally@osep.test', 'Fatuma', 'Ally'],
        ['daniel.massawe@osep.test', 'Daniel', 'Massawe'],
        ['rehema.kimaro@osep.test', 'Rehema', 'Kimaro'],
        ['peter.mnyika@osep.test', 'Peter', 'Mnyika'],
        ['zainabu.hassan@osep.test', 'Zainabu', 'Hassan'],
        ['john.komba@osep.test', 'John', 'Komba'],
        ['mariam.said@osep.test', 'Mariam', 'Said'],
        ['frank.mollel@osep.test', 'Frank', 'Mollel'],
    ];

    public function run(): void
    {
        foreach (self::PEOPLE as [$email, $first, $last]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first, 'last_name' => $last,
                    'phone' => '+2557' . random_int(10_000_000, 99_999_999),
                    'password' => Hash::make(self::PASSWORD),
                    'account_type' => AccountType::Client,
                    'country' => 'Tanzania',
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
            $user->assignRole(AccountType::Client->value);
        }
    }
}
