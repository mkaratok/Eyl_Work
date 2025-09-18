<?php

namespace App\Exports;

use App\Models\Seller;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class SellersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Seller::query()->with(['parentSeller'])->withCount(['productPrices', 'childSellers']);

        // Apply filters
        if (!empty($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['subscription_type'])) {
            $query->where('subscription_type', $this->filters['subscription_type']);
        }

        if (!empty($this->filters['parent_only'])) {
            $query->parents();
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Company Name',
            'Contact Name',
            'Email',
            'Phone',
            'Status',
            'Subscription Type',
            'Commission Rate',
            'Is Verified',
            'Is Parent',
            'Parent Seller',
            'Products Count',
            'Sub-Sellers Count',
            'Subscription Expires',
            'Created At',
            'Updated At'
        ];
    }

    public function map($seller): array
    {
        return [
            $seller->id,
            $seller->company_name,
            $seller->contact_name,
            $seller->email,
            $seller->phone ?? 'N/A',
            ucfirst($seller->status),
            ucfirst($seller->subscription_type),
            $seller->commission_rate . '%',
            $seller->is_verified ? 'Yes' : 'No',
            $seller->is_parent ? 'Yes' : 'No',
            $seller->parentSeller?->company_name ?? 'N/A',
            $seller->product_prices_count,
            $seller->child_sellers_count,
            $seller->subscription_expires_at?->format('Y-m-d') ?? 'N/A',
            $seller->created_at->format('Y-m-d H:i:s'),
            $seller->updated_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:P' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return 'Sellers Report';
    }
}
