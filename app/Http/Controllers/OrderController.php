<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Support\PdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** The signed-in customer's orders. */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Only the orders of the signed-in customer's client.
        $orders = Order::where('client_id', $request->user()->client_id)
            ->withCount('lines')
            ->latest('order_date')->latest('id')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        // OrderPolicy::view -> 403 if this order belongs to another client.
        Gate::authorize('view', $order);

        $order->load('lines.product', 'client');

        return view('orders.show', compact('order'));
    }

    /** Printable summary (the browser can "Save as PDF"). */
    public function print(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load('lines.product', 'client');

        return view('orders.print', compact('order'));
    }

    /** Real PDF file of the order summary (dompdf). */
    public function pdf(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load('lines.product', 'client');

        return PdfExport::document('pdf.order', compact('order'), 'commande-'.$order->number.'.pdf');
    }

    public function cancel(Order $order, OrderService $orders)
    {
        Gate::authorize('cancel', $order);

        $orders->changeStatus($order, 'cancelled');

        return back()->with('success', "La commande {$order->number} a été annulée. Les articles ont été remis en stock.");
    }
}
