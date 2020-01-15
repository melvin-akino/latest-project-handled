<?php

namespace Tests\Feature;

use App\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function requiredFields ()
    {
        $fields = [
            'email',
            'password',
            'password_confirmation',
            'name',
            'firstname',
            'lastname',
            'phone',
            'address',
            'postcode',
            'country',
            'state',
            'city',
            'phone_country_code',
            'odds_type',
            'currency_id',
        ];

        collect($fields)
            ->each(function ($field) {
                $response = $this->post('/api/auth/register', array_merge($this->data(), [$field => '']));

                /** MUST detect that response encounters a POST error */
                $response->assertSessionHasErrors($field);

                /** MUST NOT be able to add a record to database table */
                $this->assertCount(0, User::all());
            });
    }

    /** @test */
    public function invalidParameters ()
    {
        /**
         * MUST NOT accept invalid inputs from Request Validation Ruling
         *
         * @ref     App\Http\Requests\RegistrationRequests
         */

        $ifTextRule = [
            'email'               => ['email'],
            'postcode'            => ['numeric'],
            'phone_country_code'  => ['numeric'],
            'country'             => ['numeric'],
            'state'               => ['numeric'],
            'city'                => ['numeric'],
            'currency_id'         => ['numeric'],
            'odds_type'           => ['numeric'],
            'birthdate'           => ['date'],
        ];

        /** Field/s MUST NOT accept `text` input */
        collect(array_keys($ifTextRule))
            ->each(function ($field) use ($ifTextRule) {
                $this->assertFalse(
                    validator([ $field => $this->faker->text(), ], $ifTextRule)->passes()
                );
            });

        $ifIntRule = [
            'email'      => ['email'],
            'birthdate'  => ['date'],
        ];

        /** Field/s MUST NOT accept `integer` input */
        collect(array_keys($ifIntRule))
            ->each(function ($field) use ($ifIntRule) {
                $this->assertFalse(
                    validator([ $field => $this->faker->randomDigit, ], $ifIntRule)->passes()
                );
            });

        $inputLengthRule = [
            'name'                   => ['min:6', 'max:32'],
            'password'               => ['min:6', 'max:32'],
            'password_confirmation'  => ['min:6', 'max:32'],
        ];

        /** Field/s MUST NOT accept `integer` input */
        collect(array_keys($inputLengthRule))
            ->each(function ($field) use ($inputLengthRule) {
                $this->assertFalse(
                    validator([ $field => substr($this->faker->sha256, 0, rand(1, 5)), ], $inputLengthRule)->passes()
                );

                $this->assertFalse(
                    validator([ $field => $this->faker->sha256, ], $inputLengthRule)->passes()
                );
            });
    }

    /** @test */
    public function validParameters ()
    {
        /**
         * MUST accept valid inputs from Request Validation Ruling
         *
         * @ref     App\Http\Requests\RegistrationRequests
         */

        $ifEmailRule = [
            'email' => ['email'],
        ];

        /** Field/s SHOULD accept valid `email` input */
        collect(array_keys($ifEmailRule))
            ->each(function ($field) use ($ifEmailRule) {
                $this->assertTrue(
                    validator([ $field => $this->faker->email, ], $ifEmailRule)->passes()
                );
            });

        $ifDateRule = [
            'birthdate' => ['date'],
        ];

        /** Field/s SHOULD accept valid `date` input */
        collect(array_keys($ifDateRule))
            ->each(function ($field) use ($ifDateRule) {
                $this->assertTrue(
                    validator([ $field => $this->faker->date('Y-m-d', '2000-01-01'), ], $ifDateRule)->passes()
                );
            });

        $ifIntRule = [
            'postcode'              => ['numeric'],
            'phone_country_code'    => ['numeric'],
            'country'               => ['numeric'],
            'state'                 => ['numeric'],
            'city'                  => ['numeric'],
            'currency_id'           => ['numeric'],
            'odds_type'             => ['numeric'],
        ];

        /** Field/s SHOULD accept valid `integer` input */
        collect(array_keys($ifIntRule))
            ->each(function ($field) use ($ifIntRule) {
                $this->assertTrue(
                    validator([ $field => $this->faker->randomDigit, ], $ifIntRule)->passes()
                );
            });
    }

    /** @test */
    public function register ()
    {
        $response = $this->post('/api/auth/register', $this->data());

        /** Response SHOULD be able to submit data without errors occuring */
        $response->assertSessionHasNoErrors();

        /** MUST be able to add a record to database table */
        $this->assertCount(1, User::all());
    }

    /** Faker generated data */
    private function data()
    {
        $password = $this->faker->text(16);

        return [
            'email'                     => $this->faker->email,
            'password'                  => $password,
            'password_confirmation'     => $password,
            'name'                      => $this->faker->name,

            'firstname'                 => $this->faker->firstName,
            'lastname'                  => $this->faker->lastName,

            'phone'                     => $this->faker->phoneNumber,
            'address'                   => $this->faker->address,
            'postcode'                  => $this->faker->randomNumber(4),

            'country'                   => $this->faker->randomDigit,
            'state'                     => $this->faker->randomDigit,
            'city'                      => $this->faker->randomDigit,
            'phone_country_code'        => $this->faker->randomDigit,
            'odds_type'                 => $this->faker->randomDigit,
            'currency_id'               => $this->faker->randomDigit,

            'birthdate'                 => $this->faker->date('Y-m-d', '2000-01-01'),
            'created_at'                => now(),
            'updated_at'                => now(),
        ];
    }
}