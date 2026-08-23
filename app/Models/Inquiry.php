<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    public const STATUSES = [
        'new' => 'Baru',
        'contacted' => 'Sudah Dihubungi',
        'negotiation' => 'Negosiasi',
        'deal' => 'Deal',
        'not_interested' => 'Tidak Berminat',
        'closed' => 'Ditutup',
    ];

    protected $fillable = [
        'name', 'company', 'whatsapp', 'email', 'service_id', 'service_name',
        'estimated_quantity', 'target_date', 'description', 'attachment_path',
        'attachment_name', 'admin_notes', 'status', 'customer_id',
    ];

    protected function casts(): array
    {
        return ['target_date' => 'date'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
