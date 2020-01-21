<?php

namespace Tests\Feature;

use App\User;
use App\Auth\PasswordReset;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function requiredFields()
    {
        $fields = [
            'email',
        ];

        collect($fields)
            ->each(function ($field) {
                $response = $this->post('/api/v1/auth/password/create', array_merge($this->data(), [$field => '']));

                /** MUST detect that response encounters a 422 HTTP Status error */
                $response->assertStatus(422);
            });
    }

    /** @test */
    public function invalidParameters()
    {
        /**
         * MUST NOT accept invalid inputs from Request Validation Ruling
         *
         * @ref     App\Http\Requests\ForgotPasswordRequests
         */

        $rule = [
            'email' => ['email'],
        ];

        /** Field/s MUST NOT accept non-`email` input */
        collect(array_keys($rule))
            ->each(function ($field) use ($rule) {
                $this->assertFalse(
                    validator([ $field => $this->faker->text(), ], $rule)->passes()
                );

                $this->assertFalse(
                    validator([ $field => $this->faker->randomDigit, ], $rule)->passes()
                );
            });
    }

    /** @test */
    public function validParameters()
    {
        /**
         * MUST accept valid inputs from Request Validation Ruling
         *
         * @ref     App\Http\Requests\ForgotPasswordRequests
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
    }

    /** @test */
    public function createResetRequest()
    {
        User::create([
            'firstname'              => 'ran',
            'lastname'               => 'dom',
            'name'                   => 'ran dom user',
            'email'                  => 'user@ninepinetech.com',
            'password'               => 'password',
            'password_confirmation'  => 'password',
            'postcode'               => 1234,
            'phone_country_code'     => 3,
            'country'                => 2,
            'state'                  => 1,
            'city'                   => 1,
            'currency_id'            => 1,
            'odds_type'              => 1,
            'address'                => 'U1903 Orient Square',
            'phone'                  => '123 456 7890',
        ]);

        $response = $this->post('/api/v1/auth/password/create', $this->data());

        /** Response SHOULD be able to submit data without errors occuring */
        $response->assertSessionHasNoErrors();
    }

    /** Faker generated data */
    private function data()
    {
        return [
            'email' => 'user@ninepinetech.com',
        ];
    }
}