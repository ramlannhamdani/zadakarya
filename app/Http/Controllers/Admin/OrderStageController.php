<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStage;
use App\Support\Stages;
use Illuminate\Http\Request;

class OrderStageController extends Controller
{
    public function start(Request $request, Order $order, OrderStage $stage)
    {
        $this->ensureBelongs($order, $stage);

        if (! $stage->isPending()) {
            return back()->with('error', 'Tahap ini sudah dimulai.');
        }

        $stage->update([
            'status' => Stages::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'note' => $this->note($request, $stage),
            'updated_by' => auth()->id(),
        ]);

        $order->update(['current_stage' => $stage->stage_number]);
        $order->logActivity('Tahap "'.$stage->name.'" dimulai');

        return back()->with('success', 'Tahap "'.$stage->name.'" dimulai.');
    }

    public function complete(Request $request, Order $order, OrderStage $stage)
    {
        $this->ensureBelongs($order, $stage);

        if ($stage->isCompleted()) {
            return back()->with('error', 'Tahap ini sudah selesai.');
        }

        $stage->update([
            'status' => Stages::STATUS_COMPLETED,
            'started_at' => $stage->started_at ?? now(),
            'completed_at' => now(),
            'note' => $this->note($request, $stage),
            'updated_by' => auth()->id(),
        ]);

        $order->logActivity('Tahap "'.$stage->name.'" selesai');

        // Move the current stage forward: the next pending stage starts automatically.
        $next = $order->stages()->where('stage_number', $stage->stage_number + 1)->first();
        if ($next && $next->isPending()) {
            $next->update([
                'status' => Stages::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'updated_by' => auth()->id(),
            ]);
            $order->update(['current_stage' => $next->stage_number]);
            $order->logActivity('Tahap "'.$next->name.'" dimulai');
        } elseif (! $next) {
            // Final stage completed → the order itself is done.
            $order->update(['current_stage' => $stage->stage_number, 'status' => 'completed']);
            $order->logActivity('Semua tahap produksi selesai — pesanan ditandai Selesai');
        }

        return back()->with('success', 'Tahap "'.$stage->name.'" diselesaikan.');
    }

    public function reopen(Request $request, Order $order, OrderStage $stage)
    {
        $this->ensureBelongs($order, $stage);

        if (! $stage->isCompleted()) {
            return back()->with('error', 'Hanya tahap selesai yang dapat dibuka kembali.');
        }

        $stage->update([
            'status' => Stages::STATUS_IN_PROGRESS,
            'completed_at' => null,
            'updated_by' => auth()->id(),
        ]);

        // Later stages go back to pending so the timeline stays consistent.
        $order->stages()
            ->where('stage_number', '>', $stage->stage_number)
            ->where('status', '!=', Stages::STATUS_PENDING)
            ->get()
            ->each(fn ($s) => $s->update([
                'status' => Stages::STATUS_PENDING,
                'started_at' => null,
                'completed_at' => null,
            ]));

        $order->update([
            'current_stage' => $stage->stage_number,
            'status' => $order->status === 'completed' ? 'active' : $order->status,
        ]);
        $order->logActivity('Tahap "'.$stage->name.'" dibuka kembali');

        return back()->with('success', 'Tahap "'.$stage->name.'" dibuka kembali.');
    }

    private function ensureBelongs(Order $order, OrderStage $stage): void
    {
        abort_unless($stage->order_id === $order->id, 404);
    }

    private function note(Request $request, OrderStage $stage): ?string
    {
        $note = trim((string) $request->input('note', ''));

        return $note !== '' ? $note : $stage->note;
    }
}
