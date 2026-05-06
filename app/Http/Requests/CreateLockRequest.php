<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLockRequest extends FormRequest
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
            'court_id' => 'required|integer|exists:courts,id',
            'category' => 'nullable|string',
            'start_time' => 'required|date_format:Y-m-d H:i|after_or_equal:now',
            'duration' => 'nullable|integer|min:30' // in minutes, defaults to min_booking_duration
        ];
    }
}
