<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('type', 20)->default(Payment::TYPE_SETTLEMENT)->after('amount');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Sebelum kolom ini ada, DP dibedakan dengan mencari huruf "DP" di catatan —
     * sehingga "Pelunasan setelah DP" terhitung DP dan "Uang muka" terhitung
     * pelunasan. Penetapan ulang di sini memakai aturan yang bisa dijelaskan:
     * pembayaran pertama sebuah pesanan adalah DP bila pesanan itu memang
     * mencatat DP atau catatannya menyebut uang muka; sisanya pelunasan.
     */
    private function backfill(): void
    {
        $orders = DB::table('orders')->select('id', 'dp_amount')->get()->keyBy('id');

        $payments = DB::table('payments')
            ->select('id', 'order_id', 'note')
            ->orderBy('order_id')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $seen = [];

        foreach ($payments as $payment) {
            $isFirst = ! isset($seen[$payment->order_id]);
            $seen[$payment->order_id] = true;

            $orderHasDp = (int) ($orders[$payment->order_id]->dp_amount ?? 0) > 0;
            $noteSaysDp = (bool) preg_match('/\b(dp|uang\s*muka|panjar|down\s*payment)\b/i', (string) $payment->note);

            if ($isFirst && ($orderHasDp || $noteSaysDp)) {
                DB::table('payments')->where('id', $payment->id)->update(['type' => Payment::TYPE_DP]);
            }
        }
    }
};
