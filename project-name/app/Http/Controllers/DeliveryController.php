<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function updateStatus(Request $request, Delivery $delivery)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,delivered,failed',
        ]);

        $delivery->status = $data['status'];

        if ($data['status'] === 'delivered') {
            $delivery->delivered_at = now();
        }

        $delivery->save();

        return $delivery;
    }
}
