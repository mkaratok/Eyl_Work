<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class XmlProductImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xml|max:51200', // 50MB max
            'import_type' => 'required|in:google_merchant,custom_xml',
            'update_existing' => 'nullable|boolean',
            'auto_approve' => 'nullable|boolean',
            'create_categories' => 'nullable|boolean',
            'default_category_id' => 'nullable|integer|exists:categories,id',
            'default_brand' => 'nullable|string|max:100',
            'preview_only' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'XML file is required',
            'file.mimes' => 'File must be a valid XML file',
            'file.max' => 'File size cannot exceed 50MB',
            'import_type.required' => 'Import type is required',
            'import_type.in' => 'Import type must be either google_merchant or custom_xml',
            'default_category_id.exists' => 'Selected default category does not exist',
        ];
    }

    /**
     * Get the import configuration from the request
     */
    public function getImportConfig(): array
    {
        return [
            'import_type' => $this->get('import_type'),
            'update_existing' => $this->boolean('update_existing', true),
            'auto_approve' => $this->boolean('auto_approve', false),
            'create_categories' => $this->boolean('create_categories', false),
            'default_category_id' => $this->get('default_category_id'),
            'default_brand' => $this->get('default_brand'),
            'preview_only' => $this->boolean('preview_only', false),
            'created_by' => auth()->id(),
        ];
    }
}