<?php

namespace App\Http\Requests;

use App\Models\Provider;
use App\Models\SportOddType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $type = $this->route('type');
        $sportId = is_null($this->route('sportId')) ? null : $this->route('sportId');

        if ($type == 'general') {
            $priceFormats = implode(',', array_column(config('constants.price-format'), 'id'));

            return [
                'price_format'              => 'required|numeric|in:' . $priceFormats,
                'timezone'                  => 'required|numeric|exists:timezones,id',
            ];
        } else if ($type == 'profile') {
            return [
                'firstname'                 => 'required|string|max:32',
                'lastname'                  => 'required|string|max:32',
                'address'                   => 'required',
                'postcode'                  => 'required|string|regex:/^[0-9]{3,6}$/|min:3|max:6',
                'country_id'                => 'required|numeric|exists:countries,id',
                'state'                     => 'required|max:100',
                'city'                      => 'required|max:100',
                'phone'                     => 'required|string|regex:/^[0-9]{1,32}$/|min:7',
            ];
        } else if ($type == 'change-password') {
            return [
                'old_password'              => 'required|min:6|max:32',
                'password'                  => 'required|confirmed|min:6|max:32|alpha_num',
                'password_confirmation'     => 'required|same:password|min:6|max:32|alpha_num',
            ];
        } else if ($type == 'trade-page') {
            $tradeLayouts = implode(',', array_column(config('constants.trade-layout'), 'id'));
            $sortEvents = implode(',', array_column(config('constants.sort-event'), 'id'));

            return [
                'suggested'                 => 'required|boolean',
                'trade_background'          => 'required|boolean',
                'hide_comp_names_in_fav'    => 'required|boolean',
                'live_position_values'      => 'required|boolean',
                'hide_exchange_only'        => 'required|boolean',
                'trade_layout'              => 'required|numeric|in:' . $tradeLayouts,
                'sort_event'                => 'required|numeric|in:' . $sortEvents,
            ];
        } else if ($type == 'bet-slip') {
            $selections = implode(',', array_column(config('constants.betslip-adaptive-selection'), 'id'));

            return [
                'use_equivalent_bets'       => 'required|boolean',
                'offers_on_exchanges'       => 'required|boolean',
                'adv_placement_opt'         => 'required|boolean',
                'bets_to_fav'               => 'required|boolean',
                'adv_betslip_info'          => 'required|boolean',
                'tint_bookies'              => 'required|boolean',
                'adaptive_selection'        => 'required|numeric|in:' . $selections,
                'awaiting_placement_msg'    => 'required|boolean',
            ];
        } else if ($type == 'bookies') {
            $providers = Provider::getActiveProviders()->get()->toArray();
            $rules = [];

            foreach ($providers as $key => $provider) {
                $rules[$key . '.provider_id'] = [
                    'required',
                    function ($attribute, $value, $fail) {
                        $provider = Provider::where('id', $value)->where('is_enabled', 1)->first();

                        if (!$provider) {
                            $fail('Provider ID ' . $value . ' is invalid.');
                        }
                    },
                ];
                $rules[$key . '.active'] = 'required|boolean';
            }

            return $rules;
        } else if ($type == 'bet-columns') {
            $sportOddTypes = SportOddType::getEnabledSportOdds($sportId);
            $rules = [];

            foreach ($sportOddTypes as $key => $sportOddType) {
                $rules[$key . '.sport_odd_type_id'] = [
                    'required',
                    function ($attribute, $value, $fail) {
                        $provider = SportOddType::where('id', $value)->first();

                        if (!$provider) {
                            $fail('Sport Odd Type ID ' . $value . ' is invalid.');
                        }
                    },
                ];
                $rules[$key . '.active'] = 'required|boolean';
            }

            return $rules;
        } else if ($type == 'notifications-and-sounds') {
            return [
                'bet_confirm'               => 'required|boolean',
                'site_notifications'        => 'required|boolean',
                'popup_notifications'       => 'required|boolean',
                'order_notifications'       => 'required|boolean',
                'event_sounds'              => 'required|boolean',
                'order_sounds'              => 'required|boolean',
            ];
        } else if ($type == 'language') {
            $languages = implode(',', array_column(config('constants.language'), 'id'));

            return [
                'language'                  => 'required|numeric|in:' . $languages,
            ];
        } else {
            return [
                //
            ];
        }
    }

    /**
     * Assign custom return responses for every request validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'password.min'                 => trans('validation.custom.password.min', ['count' => 6]),
            'password_confirmation.min'    => trans('validation.custom.password_confirmation.min', ['count' => 6]),
            'password.max'                 => trans('validation.custom.password.max', ['count' => 32]),
            'password_confirmation.max'    => trans('validation.custom.password_confirmation.max', ['count' => 32]),
            'password_confirmation.same'   => trans('validation.custom.password_confirmation.new-same'),
            'phone.min'                    => trans('validation.custom.phone.min', ['min' => 7])
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status'                       => false,
            'status_code'                  => 422,
            'message'                      => trans('validation.custom.error'),
            'errors'                       => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
