<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categorie_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'promo' => 'nullable|decimal:2',
            'prix' => 'required|decimal:2',
            'origine' => 'required|string|max:255',
            'coupe' => 'required|string|max:255',
            'stock_global' => 'required|integer',
            'variants' => 'required|array',
            'variants.*.taille' => 'required|string|max:255',
            'variants.*.couleur' => 'required|string|max:255',
            'variants.*.sku' => 'required|string|max:255',
            'variants.*.stock' => 'required|integer',
            'images' => 'required|array',
            'images.*.url_image' => 'required|string|max:255',
            'images.*.is_principal' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.required' => 'La catégorie est requise',
            'categorie_id.exists' => 'La catégorie n\'existe pas',
            'name.required' => 'Le nom est requis',
            'name.max' => 'Le nom doit contenir au maximum 255 caractères',
            'description.required' => 'La description est requise',
            'description.max' => 'La description doit contenir au maximum 255 caractères',
            'slug.required' => 'Le slug est requis',
            'slug.max' => 'Le slug doit contenir au maximum 255 caractères',
            'promo.decimal' => 'Le promo doit être un nombre décimal',
            'prix.required' => 'Le prix est requis',
            'prix.decimal' => 'Le prix doit être un nombre décimal',
            'origine.required' => 'L\'origine est requise',
            'origine.max' => 'L\'origine doit contenir au maximum 255 caractères',
            'coupe.required' => 'La coupe est requise',
            'coupe.max' => 'La coupe doit contenir au maximum 255 caractères',
            'stock_global.required' => 'Le stock global est requis',
            'stock_global.integer' => 'Le stock global doit être un nombre entier',
        ];
    }
}
