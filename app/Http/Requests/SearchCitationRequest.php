<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SearchCitationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'q' => ['required','string','min:5','max:500'],
            'sinta' => ['nullable','integer','between:1,6'],
            'year_from' => ['nullable','integer','between:1900,'.date('Y')],
            'limit' => ['nullable','integer','in:5,10,20'],
        ];
    }
    public function messages(): array {
        return [
            'q.required' => 'Masukkan kalimat yang ingin dicarikan referensinya.',
            'q.min' => 'Kalimat pencarian terlalu pendek.',
            'q.max' => 'Kalimat pencarian maksimal 500 karakter.',
        ];
    }
}
