<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public access
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lock_id' => 'required|integer|exists:booking_locks,id',
            'session_id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'id_card' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ];
    }
}
