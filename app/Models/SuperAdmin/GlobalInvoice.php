<?php

namespace App\Models\SuperAdmin;

use App\Models\Company;
use App\Models\OfflinePaymentMethod;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GlobalInvoice extends BaseModel
{

    protected $casts = [
        'pay_date' => 'datetime',
        'next_pay_date' => 'datetime',
    ];

    protected $appends = ['invoice_number'];

    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function currency()
    {
        return $this->belongsTo(GlobalCurrency::class)->withTrashed();
    }

    public function subscription()
    {
        return $this->belongsTo(GlobalSubscription::class);
    }

    public function offlinePaymentMethod()
    {
        return $this->belongsTo(OfflinePaymentMethod::class, 'offline_method_id')->withoutGlobalScopes()->whereNull('company_id');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('total', '>', 0);
    }

    protected function invoiceNumber(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->gateway == 'stripe') {
                    $invoiceNumber = $this->stripe_invoice_number;
                } else {
                    $invoiceNumber = $this->id;
                }

                return str($invoiceNumber)->padLeft(2, '0');
            },
        );
    }
}
