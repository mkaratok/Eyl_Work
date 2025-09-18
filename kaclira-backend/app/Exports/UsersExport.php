<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = User::query()->with(['roles']);

        // Apply filters
        if (!empty($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['role'])) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->filters['role']);
            });
        }

        if (!empty($this->filters['status'])) {
            switch ($this->filters['status']) {
                case 'active':
                    $query->where('is_active', true)->whereNull('banned_at');
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'banned':
                    $query->whereNotNull('banned_at');
                    break;
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Role',
            'Status',
            'Email Verified',
            'Last Login',
            'Banned At',
            'Ban Reason',
            'Created At',
            'Updated At'
        ];
    }

    public function map($user): array
    {
        $status = 'Active';
        if (!$user->is_active) {
            $status = 'Inactive';
        } elseif ($user->banned_at) {
            $status = 'Banned';
        }

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? 'N/A',
            $user->roles->first()?->name ?? 'user',
            $status,
            $user->email_verified_at ? 'Yes' : 'No',
            $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            $user->banned_at?->format('Y-m-d H:i:s') ?? 'N/A',
            $user->ban_reason ?? 'N/A',
            $user->created_at->format('Y-m-d H:i:s'),
            $user->updated_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:L' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return 'Users Report';
    }
}
