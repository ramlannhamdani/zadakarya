<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoicePdf;

class InvoiceController extends Controller
{
    /**
     * Invoice versi publik untuk dibagikan ke customer. URL-nya ditandatangani,
     * jadi hanya tautan yang memang dibagikan admin yang bisa dibuka — menebak
     * id atau nomor invoice tidak cukup.
     */
    public function show(Invoice $invoice)
    {
        return InvoicePdf::make($invoice)->stream(InvoicePdf::filename($invoice));
    }
}
