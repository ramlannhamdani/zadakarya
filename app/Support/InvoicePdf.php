<?php

namespace App\Support;

use App\Models\Invoice;
use Barryvdh\DomPDF\PDF as PdfWrapper;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Satu tempat pembuatan PDF invoice — dipakai panel admin maupun tautan publik
 * yang dibagikan ke customer, supaya berkasnya benar-benar sama persis.
 */
class InvoicePdf
{
    public static function make(Invoice $invoice): PdfWrapper
    {
        $invoice->loadMissing(['order.customer', 'order.payments', 'order.invoices', 'items']);

        return Pdf::loadView('admin.invoices.pdf', compact('invoice'))->setPaper('a4', 'landscape');
    }

    /** Nama berkas yang dilihat customer saat invoice sampai di WhatsApp. */
    public static function filename(Invoice $invoice): string
    {
        return 'Invoice '.$invoice->invoice_number.'.pdf';
    }
}
