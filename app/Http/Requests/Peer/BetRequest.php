<?php

namespace App\Http\Requests\Peer;

use Illuminate\Foundation\Http\FormRequest;

class BetRequest extends FormRequest
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
            'peer_id' => ['nullable', 'exists:peers,id'],
            'peers' => ['array', 'min:5', 'max:5'],
            'peers.*.main' => ['required', 'exists:players,id'],
            'peers.*.sub' => ['required', 'exists:players,id'],
            'peers.*.main_player_match_id' => ['required', 'exists:player_matches,id'],
            'peers.*.sub_player_match_id' => ['required', 'exists:player_matches,id'],
        ];
    }
}
