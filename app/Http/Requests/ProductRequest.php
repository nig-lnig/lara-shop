<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validation of the admin product form (create and edit). */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Accept "12,50" as well as "12.50" for the price.
        if (is_string($this->unit_price)) {
            $this->merge(['unit_price' => str_replace(',', '.', trim($this->unit_price))]);
        }

        // An unchecked checkbox sends nothing: turn "absent" into false.
        $this->merge(['is_listed' => $this->boolean('is_listed')]);
    }

    public function rules(): array
    {
        return [
            'designation' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            // At most 2 decimals, between 0.01 and 99 999 999.99
            'unit_price' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/', 'numeric', 'gt:0'],
            'family' => ['required', Rule::in(array_keys(Product::FAMILIES))],
            // Only active (non archived) suppliers can be chosen.
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->whereNull('archived_at')],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_listed' => ['boolean'],
            // max = the upload limit of PHP itself (upload_max_filesize), in KB
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::maxImageKb()],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_price.regex' => 'Le prix doit être un nombre avec au plus 2 décimales (ex. 12,50).',
            'image.max' => 'L’image ne doit pas dépasser '.self::maxImageMb().' Mo.',
            'image.uploaded' => 'L’image n’a pas pu être envoyée : elle dépasse probablement '.self::maxImageMb().' Mo (limite de PHP).',
        ];
    }

    /** Largest upload PHP accepts, in KB (php.ini: upload_max_filesize, often "2M"). Capped at 5 MB. */
    public static function maxImageKb(): int
    {
        $value = trim((string) ini_get('upload_max_filesize')) ?: '2M';
        $number = (int) $value;
        $kb = match (strtoupper(substr($value, -1))) {
            'G' => $number * 1024 * 1024,
            'M' => $number * 1024,
            'K' => $number,
            default => intdiv($number, 1024),
        };

        return max(512, min($kb, 5 * 1024));
    }

    public static function maxImageMb(): string
    {
        return rtrim(rtrim(number_format(self::maxImageKb() / 1024, 1, ',', ''), '0'), ',');
    }
}
