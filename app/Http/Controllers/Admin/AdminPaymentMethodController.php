<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class AdminPaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('position')->get();

        $stats = [
            'total'     => PaymentTransaction::count(),
            'completed' => PaymentTransaction::where('status', 'completed')->count(),
            'pending'   => PaymentTransaction::whereIn('status', ['pending', 'processing'])->count(),
            'failed'    => PaymentTransaction::where('status', 'failed')->count(),
            'revenue'   => PaymentTransaction::where('status', 'completed')->sum('net_amount'),
        ];

        $recent = PaymentTransaction::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.payment-methods.index', compact('methods', 'stats', 'recent'));
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);

        return back()->with(
            'success',
            "Moyen de paiement « {$paymentMethod->name} » " . ($paymentMethod->is_active ? 'activé' : 'désactivé') . '.'
        );
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'position'    => 'required|integer|min:0',
        ]);

        $paymentMethod->update($validated);

        return back()->with('success', 'Moyen de paiement mis à jour.');
    }

    public function transactions(Request $request)
    {
        $transactions = PaymentTransaction::with('user', 'paymentMethod')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->method, fn($q) => $q->where('payment_method_code', $request->method))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.payment-methods.transactions', compact('transactions'));
    }
}
