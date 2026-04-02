<?php

namespace Addons\CatminShop\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $company_name
 * @property string|null $company_address
 * @property string|null $company_siret
 * @property string|null $company_vat
 * @property string|null $company_iban
 * @property string|null $company_phone
 * @property string|null $company_email
 * @property string|null $company_logo_url
 * @property string|null $invoice_footer
 * @property int    $payment_terms_days
 * @property string $currency
 */
class InvoiceSettings extends Model
{
    protected $table = 'shop_invoice_settings';

    protected $fillable = [
        'company_name',
        'company_address',
        'company_siret',
        'company_vat',
        'company_iban',
        'company_phone',
        'company_email',
        'company_logo_url',
        'invoice_footer',
        'payment_terms_days',
        'currency',
    ];

    /**
     * Always return the single settings row, creating defaults if absent.
     */
    public static function current(): static
    {
        return static::firstOrCreate([], ['company_name' => '', 'payment_terms_days' => 30, 'currency' => 'EUR']);
    }
}
