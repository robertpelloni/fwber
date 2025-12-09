<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="VenueRegisterRequest",
 *   required={"name", "email", "password", "password_confirmation", "business_type", "address", "city", "state", "zip_code"},
 *   @OA\Property(property="name", type="string", maxLength=255, description="Venue name"),
 *   @OA\Property(property="email", type="string", format="email", maxLength=255, description="Venue email"),
 *   @OA\Property(property="password", type="string", format="password", minLength=8, description="Password"),
 *   @OA\Property(property="password_confirmation", type="string", format="password", description="Password confirmation"),
 *   @OA\Property(property="business_type", type="string", description="Type of business"),
 *   @OA\Property(property="address", type="string", description="Street address"),
 *   @OA\Property(property="city", type="string", description="City"),
 *   @OA\Property(property="state", type="string", description="State"),
 *   @OA\Property(property="zip_code", type="string", description="ZIP code")
 * )
 */
class VenueRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:venues',
            'password' => 'required|string|min:8|confirmed',
            'business_type' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip_code' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Venue name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'business_type.required' => 'Business type is required.',
            'address.required' => 'Address is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'zip_code.required' => 'ZIP code is required.',
        ];
    }
}
