<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
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
        $productId = $this->route('id') ?? $this->route('product');

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId)
            ],
            'description' => 'sometimes|required|string|max:5000',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'brand' => 'sometimes|required|string|max:100',
            'model' => 'nullable|string|max:100',
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('products', 'barcode')->ignore($productId)
            ],
            'specifications' => 'nullable|array',
            'specifications.*' => 'string|max:1000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'thumbnail' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:999999.99',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,pending,published,rejected',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            
            // Additional update-specific fields
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'reorder_images' => 'nullable|array',
            'reorder_images.*' => 'string',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'name.max' => 'Product name cannot exceed 255 characters',
            'description.required' => 'Product description is required',
            'description.max' => 'Product description cannot exceed 5000 characters',
            'short_description.max' => 'Short description cannot exceed 500 characters',
            'category_id.required' => 'Product category is required',
            'category_id.exists' => 'Selected category does not exist',
            'brand.required' => 'Product brand is required',
            'brand.max' => 'Brand name cannot exceed 100 characters',
            'model.max' => 'Model name cannot exceed 100 characters',
            'sku.unique' => 'SKU already exists',
            'sku.max' => 'SKU cannot exceed 50 characters',
            'barcode.unique' => 'Barcode already exists',
            'barcode.max' => 'Barcode cannot exceed 20 characters',
            'specifications.array' => 'Specifications must be an array',
            'images.array' => 'Images must be an array',
            'images.max' => 'Maximum 10 images allowed',
            'images.*.image' => 'Each file must be a valid image',
            'images.*.mimes' => 'Images must be jpeg, png, jpg, or webp format',
            'images.*.max' => 'Each image cannot exceed 5MB',
            'weight.numeric' => 'Weight must be a number',
            'weight.min' => 'Weight cannot be negative',
            'weight.max' => 'Weight cannot exceed 999999.99',
            'dimensions.array' => 'Dimensions must be an array',
            'dimensions.length.numeric' => 'Length must be a number',
            'dimensions.width.numeric' => 'Width must be a number',
            'dimensions.height.numeric' => 'Height must be a number',
            'status.in' => 'Status must be one of: draft, pending, published, rejected',
            'meta_title.max' => 'Meta title cannot exceed 255 characters',
            'meta_description.max' => 'Meta description cannot exceed 500 characters',
            'tags.array' => 'Tags must be an array',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'is_featured.boolean' => 'Featured status must be true or false',
            'sort_order.integer' => 'Sort order must be an integer',
            'sort_order.min' => 'Sort order cannot be negative',
            'remove_images.array' => 'Remove images must be an array',
            'reorder_images.array' => 'Reorder images must be an array',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        // Parse dimensions if sent as JSON string
        if ($this->has('dimensions') && is_string($this->dimensions)) {
            $dimensions = json_decode($this->dimensions, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['dimensions' => $dimensions]);
            }
        }

        // Parse specifications if sent as JSON string
        if ($this->has('specifications') && is_string($this->specifications)) {
            $specifications = json_decode($this->specifications, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['specifications' => $specifications]);
            }
        }

        // Parse tags if sent as JSON string
        if ($this->has('tags') && is_string($this->tags)) {
            $tags = json_decode($this->tags, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['tags' => $tags]);
            }
        }

        // Parse remove_images if sent as JSON string
        if ($this->has('remove_images') && is_string($this->remove_images)) {
            $removeImages = json_decode($this->remove_images, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['remove_images' => $removeImages]);
            }
        }

        // Parse reorder_images if sent as JSON string
        if ($this->has('reorder_images') && is_string($this->reorder_images)) {
            $reorderImages = json_decode($this->reorder_images, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['reorder_images' => $reorderImages]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate barcode format if provided
            if ($this->barcode) {
                $barcodeValidation = Product::validateBarcode($this->barcode);
                if (!$barcodeValidation['valid']) {
                    $validator->errors()->add('barcode', $barcodeValidation['message']);
                }
            }

            // Validate category is active
            if ($this->category_id) {
                $category = \App\Models\Category::find($this->category_id);
                if ($category && !$category->is_active) {
                    $validator->errors()->add('category_id', 'Selected category is not active');
                }
            }

            // Validate dimensions structure
            if ($this->dimensions && is_array($this->dimensions)) {
                $allowedKeys = ['length', 'width', 'height'];
                $invalidKeys = array_diff(array_keys($this->dimensions), $allowedKeys);
                
                if (!empty($invalidKeys)) {
                    $validator->errors()->add('dimensions', 'Invalid dimension keys: ' . implode(', ', $invalidKeys));
                }

                foreach ($this->dimensions as $key => $value) {
                    if (!is_numeric($value) || $value < 0) {
                        $validator->errors()->add("dimensions.{$key}", "Dimension {$key} must be a positive number");
                    }
                }
            }

            // Validate specifications structure
            if ($this->specifications && is_array($this->specifications)) {
                foreach ($this->specifications as $key => $value) {
                    if (!is_string($key) || empty($key)) {
                        $validator->errors()->add('specifications', 'Specification keys must be non-empty strings');
                        break;
                    }
                    
                    if (!is_string($value)) {
                        $validator->errors()->add("specifications.{$key}", 'Specification values must be strings');
                    }
                }
            }

            // Validate tags
            if ($this->tags && is_array($this->tags)) {
                foreach ($this->tags as $index => $tag) {
                    if (!is_string($tag) || empty(trim($tag))) {
                        $validator->errors()->add("tags.{$index}", 'Tags must be non-empty strings');
                    }
                }
            }

            // Validate product exists and user has permission (for sellers)
            $productId = $this->route('id') ?? $this->route('product');
            if ($productId && auth()->check()) {
                $product = Product::find($productId);
                
                if (!$product) {
                    $validator->errors()->add('product', 'Product not found');
                } else {
                    // Check if seller is trying to update their own product
                    $user = auth()->user();
                    if ($user->hasRole('seller') && $product->created_by !== $user->id) {
                        $validator->errors()->add('product', 'You can only update your own products');
                    }

                    // Prevent status changes for approved products by sellers
                    if ($user->hasRole('seller') && $product->admin_approved && $this->has('status')) {
                        $validator->errors()->add('status', 'Cannot change status of approved products');
                    }
                }
            }
        });
    }

    /**
     * Get validated data with additional processing.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Clean and process tags
        if (isset($validated['tags']) && is_array($validated['tags'])) {
            $validated['tags'] = array_unique(array_map('trim', array_filter($validated['tags'])));
        }

        // Process remove_images
        if (isset($validated['remove_images']) && is_array($validated['remove_images'])) {
            $validated['remove_images'] = array_filter($validated['remove_images']);
        }

        // Process reorder_images
        if (isset($validated['reorder_images']) && is_array($validated['reorder_images'])) {
            $validated['reorder_images'] = array_filter($validated['reorder_images']);
        }

        return $validated;
    }

    /**
     * Get only the fields that should be updated.
     */
    public function getUpdateData(): array
    {
        $validated = $this->validated();
        
        // Remove image-related fields that are handled separately
        unset($validated['images'], $validated['remove_images'], $validated['reorder_images']);
        
        return array_filter($validated, function($value) {
            return $value !== null;
        });
    }
}
