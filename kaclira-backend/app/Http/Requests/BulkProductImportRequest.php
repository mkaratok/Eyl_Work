<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkProductImportRequest extends FormRequest
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
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'import_type' => 'required|in:create,update,upsert',
            'skip_duplicates' => 'nullable|boolean',
            'validate_barcodes' => 'nullable|boolean',
            'auto_approve' => 'nullable|boolean',
            'default_category_id' => 'nullable|integer|exists:categories,id',
            'default_brand' => 'nullable|string|max:100',
            'default_status' => 'nullable|in:draft,pending,published',
            'mapping' => 'nullable|array',
            'mapping.name' => 'nullable|string|max:50',
            'mapping.description' => 'nullable|string|max:50',
            'mapping.brand' => 'nullable|string|max:50',
            'mapping.model' => 'nullable|string|max:50',
            'mapping.sku' => 'nullable|string|max:50',
            'mapping.barcode' => 'nullable|string|max:50',
            'mapping.category' => 'nullable|string|max:50',
            'mapping.weight' => 'nullable|string|max:50',
            'mapping.price' => 'nullable|string|max:50',
            'mapping.specifications' => 'nullable|array',
            'mapping.specifications.*' => 'string|max:50',
            'preview_only' => 'nullable|boolean',
            'batch_size' => 'nullable|integer|min:10|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Import file is required',
            'file.file' => 'Upload must be a valid file',
            'file.mimes' => 'File must be Excel (.xlsx, .xls) or CSV format',
            'file.max' => 'File size cannot exceed 10MB',
            'import_type.required' => 'Import type is required',
            'import_type.in' => 'Import type must be one of: create, update, upsert',
            'skip_duplicates.boolean' => 'Skip duplicates must be true or false',
            'validate_barcodes.boolean' => 'Validate barcodes must be true or false',
            'auto_approve.boolean' => 'Auto approve must be true or false',
            'default_category_id.exists' => 'Default category does not exist',
            'default_brand.max' => 'Default brand cannot exceed 100 characters',
            'default_status.in' => 'Default status must be one of: draft, pending, published',
            'mapping.array' => 'Column mapping must be an array',
            'mapping.name.max' => 'Name column mapping cannot exceed 50 characters',
            'mapping.description.max' => 'Description column mapping cannot exceed 50 characters',
            'mapping.brand.max' => 'Brand column mapping cannot exceed 50 characters',
            'mapping.model.max' => 'Model column mapping cannot exceed 50 characters',
            'mapping.sku.max' => 'SKU column mapping cannot exceed 50 characters',
            'mapping.barcode.max' => 'Barcode column mapping cannot exceed 50 characters',
            'mapping.category.max' => 'Category column mapping cannot exceed 50 characters',
            'mapping.weight.max' => 'Weight column mapping cannot exceed 50 characters',
            'mapping.price.max' => 'Price column mapping cannot exceed 50 characters',
            'mapping.specifications.array' => 'Specifications mapping must be an array',
            'preview_only.boolean' => 'Preview only must be true or false',
            'batch_size.integer' => 'Batch size must be an integer',
            'batch_size.min' => 'Batch size must be at least 10',
            'batch_size.max' => 'Batch size cannot exceed 1000',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $booleanFields = ['skip_duplicates', 'validate_barcodes', 'auto_approve', 'preview_only'];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ]);
            }
        }

        // Parse mapping if sent as JSON string
        if ($this->has('mapping') && is_string($this->mapping)) {
            $mapping = json_decode($this->mapping, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['mapping' => $mapping]);
            }
        }

        // Set defaults
        if (!$this->has('import_type')) {
            $this->merge(['import_type' => 'create']);
        }

        if (!$this->has('batch_size')) {
            $this->merge(['batch_size' => 100]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate file extension matches content type
            if ($this->hasFile('file')) {
                $file = $this->file('file');
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                
                $validMimeTypes = [
                    'xlsx' => [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel'
                    ],
                    'xls' => [
                        'application/vnd.ms-excel',
                        'application/excel'
                    ],
                    'csv' => [
                        'text/csv',
                        'text/plain',
                        'application/csv'
                    ]
                ];

                if (isset($validMimeTypes[$extension])) {
                    if (!in_array($mimeType, $validMimeTypes[$extension])) {
                        $validator->errors()->add('file', 'File content does not match the file extension');
                    }
                }
            }

            // Validate auto_approve permission (only admins can auto-approve)
            if ($this->auto_approve && auth()->check()) {
                $user = auth()->user();
                if (!$user->hasRole('admin')) {
                    $validator->errors()->add('auto_approve', 'Only administrators can auto-approve imported products');
                }
            }

            // Validate default category is active
            if ($this->default_category_id) {
                $category = \App\Models\Category::find($this->default_category_id);
                if ($category && !$category->is_active) {
                    $validator->errors()->add('default_category_id', 'Default category is not active');
                }
            }

            // Validate mapping fields
            if ($this->mapping && is_array($this->mapping)) {
                $requiredMappings = ['name', 'description', 'brand'];
                $providedMappings = array_filter($this->mapping);
                
                foreach ($requiredMappings as $required) {
                    if (empty($providedMappings[$required])) {
                        $validator->errors()->add("mapping.{$required}", "Column mapping for {$required} is required");
                    }
                }

                // Validate no duplicate column mappings
                $mappingValues = array_filter(array_values($this->mapping));
                if (count($mappingValues) !== count(array_unique($mappingValues))) {
                    $validator->errors()->add('mapping', 'Duplicate column mappings are not allowed');
                }
            }

            // Validate import type specific requirements
            if ($this->import_type === 'update' && empty($this->mapping['sku']) && empty($this->mapping['barcode'])) {
                $validator->errors()->add('mapping', 'Update import requires either SKU or barcode column mapping');
            }
        });
    }

    /**
     * Get validated data with additional processing.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Set defaults for boolean fields
        $validated['skip_duplicates'] = $validated['skip_duplicates'] ?? false;
        $validated['validate_barcodes'] = $validated['validate_barcodes'] ?? true;
        $validated['auto_approve'] = $validated['auto_approve'] ?? false;
        $validated['preview_only'] = $validated['preview_only'] ?? false;

        // Set default status based on user role and auto_approve
        if (!isset($validated['default_status'])) {
            if ($validated['auto_approve'] && auth()->user()->hasRole('admin')) {
                $validated['default_status'] = 'published';
            } else {
                $validated['default_status'] = 'pending';
            }
        }

        return $validated;
    }

    /**
     * Get the import configuration.
     */
    public function getImportConfig(): array
    {
        $validated = $this->validated();
        
        return [
            'import_type' => $validated['import_type'],
            'skip_duplicates' => $validated['skip_duplicates'],
            'validate_barcodes' => $validated['validate_barcodes'],
            'auto_approve' => $validated['auto_approve'],
            'preview_only' => $validated['preview_only'],
            'batch_size' => $validated['batch_size'],
            'defaults' => [
                'category_id' => $validated['default_category_id'] ?? null,
                'brand' => $validated['default_brand'] ?? null,
                'status' => $validated['default_status'],
                'created_by' => auth()->id(),
            ],
            'mapping' => $validated['mapping'] ?? $this->getDefaultMapping(),
        ];
    }

    /**
     * Get default column mapping.
     */
    protected function getDefaultMapping(): array
    {
        return [
            'name' => 'name',
            'description' => 'description',
            'brand' => 'brand',
            'model' => 'model',
            'sku' => 'sku',
            'barcode' => 'barcode',
            'category' => 'category',
            'weight' => 'weight',
            'price' => 'price',
        ];
    }

    /**
     * Get supported file formats.
     */
    public static function getSupportedFormats(): array
    {
        return [
            'xlsx' => 'Excel 2007+ (.xlsx)',
            'xls' => 'Excel 97-2003 (.xls)',
            'csv' => 'Comma Separated Values (.csv)',
        ];
    }

    /**
     * Get import type options.
     */
    public static function getImportTypes(): array
    {
        return [
            'create' => 'Create new products only',
            'update' => 'Update existing products only',
            'upsert' => 'Create new or update existing products',
        ];
    }
}
