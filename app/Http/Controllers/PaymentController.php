<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    /**
     * Initiate payment (Chargily stub).
     */
    public function initiate(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'order_id' => 'sometimes|exists:orders,id'
        ]);

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'status' => 'pending',
            'external_id' => null // from Chargily response
        ]);

        // In real implementation: call Chargily API to create checkout URL

        return response()->json([
            'message' => 'Payment initiated',
            'payment_id' => $payment->id,
            'redirect_url' => 'https://pay.chargily.dz/test/' . $payment->id
        ]);
    }

    /**
     * Verify / webhook stub.
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'status' => 'required|in:paid,failed'
        ]);

        $payment = Payment::findOrFail($data['payment_id']);
        $payment->status = $data['status'];
        $payment->save();

        return response()->json(['message' => 'Payment status updated', 'payment' => $payment]);
    }
}
