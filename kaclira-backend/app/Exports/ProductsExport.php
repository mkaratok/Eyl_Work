<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Product::query()->with(['category', 'creator', 'activePrices']);

        // Apply filters
        if (!empty($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['admin_approved'])) {
            $query->where('admin_approved', $this->filters['admin_approved']);
        }

        if (isset($this->filters['featured'])) {
            $query->where('is_featured', $this->filters['featured']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Brand',
            'Model',
            'Barcode',
            'Category',
            'Status',
            'Admin Approved',
            'Featured',
            'Min Price',
            'Max Price',
            'Active Sellers',
            'Created By',
            'Created At',
            'Updated At'
        ];
    }

    public function map($product): array
    {
        $prices = $product->activePrices;
        $minPrice = $prices->min('price') ?? 0;
        $maxPrice = $prices->max('price') ?? 0;

        return [
            $product->id,
            $product->name,
            $product->brand ?? 'N/A',
            $product->model ?? 'N/A',
            $product->barcode ?? 'N/A',
            $product->category?->name ?? 'N/A',
            ucfirst($product->status),
            $product->admin_approved ? 'Yes' : 'No',
            $product->is_featured ? 'Yes' : 'No',
            $minPrice > 0 ? number_format($minPrice, 2) . ' TL' : 'N/A',
            $maxPrice > 0 ? number_format($maxPrice, 2) . ' TL' : 'N/A',
            $prices->count(),
            $product->creator?->name ?? 'N/A',
            $product->created_at->format('Y-m-d H:i:s'),
            $product->updated_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:O' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return 'Products Report';
    }
}
