<?php

namespace Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Shop\Models\Invoice;
use Modules\Shop\Models\InvoiceSettings;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice): View
    {
        $invoice->load(['order.items', 'customer']);

        return view()->file(base_path('modules/Shop/Views/invoices/show.blade.php'), [
            'currentPage' => 'shop',
            'invoice' => $invoice,
            'order' => $invoice->order,
            'customer' => $invoice->customer,
            'settings' => InvoiceSettings::current(),
            'renderMode' => 'page',
        ]);
    }

    public function settingsIndex(): View
    {
        return view()->file(base_path('modules/Shop/Views/invoices/settings.blade.php'), [
            'currentPage' => 'shop',
            'settings' => InvoiceSettings::current(),
        ]);
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'       => ['required', 'string', 'max:200'],
            'company_address'    => ['nullable', 'string', 'max:500'],
            'company_siret'      => ['nullable', 'string', 'max:50'],
            'company_vat'        => ['nullable', 'string', 'max:50'],
            'company_iban'       => ['nullable', 'string', 'max:50'],
            'company_phone'      => ['nullable', 'string', 'max:50'],
            'company_email'      => ['nullable', 'email', 'max:200'],
            'company_logo_url'   => ['nullable', 'string', 'max:500'],
            'invoice_footer'     => ['nullable', 'string', 'max:1000'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'currency'           => ['required', 'string', 'max:10'],
        ]);

        InvoiceSettings::current()->fill($validated)->save();

        return redirect()->route('admin.shop.invoices.settings')
            ->with('success', 'Paramètres facture mis à jour.');
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        $invoice->load(['order.items', 'customer']);

        $html = view()->file(base_path('modules/Shop/Views/invoices/show.blade.php'), [
            'currentPage' => 'shop',
            'invoice' => $invoice,
            'order' => $invoice->order,
            'customer' => $invoice->customer,
            'settings' => InvoiceSettings::current(),
            'renderMode' => 'snapshot',
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHTML($html)
            ->setPaper('a4')
            ->setWarnings(false);

        $invoiceNumber = Str::slug((string) ($invoice->invoice_number ?: ('invoice-' . $invoice->id)));

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoiceNumber . '.pdf"',
        ]);
    }
}
