<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'convenience_fee',    // added
        'method',
        'installment_no',
        'due_date',
        'paid_at',
        'status',
        'notes',
        'reference_id',       // added
        'raw_response',       // added
        'reference_number',
    ];

    // Relationships

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Scopes for common queries

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    // Is this payment fully settled?
    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    // Mark as paid
    public function markAsPaid($date = null)
    {
        $this->status = 'paid';
        $this->paid_at = $date ?? now();
        $this->save();
    }

    // For display: "Installment 2 of 6" etc.
    public function getInstallmentLabelAttribute()
    {
        if ($this->method === 'installment' && $this->installment_no) {
            $total = $this->booking->payments()->where('method', 'installment')->count();
            return "Installment {$this->installment_no} of {$total}";
        }
        return 'Full Payment';
    }
}
