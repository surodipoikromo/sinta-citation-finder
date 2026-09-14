<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ImportCorpusRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['csv' => ['required','file','max:2048']]; }
    public function messages(): array { return ['csv.required' => 'Pilih file CSV yang akan diimpor.', 'csv.max' => 'Ukuran file CSV maksimal 2 MB.']; }
}
