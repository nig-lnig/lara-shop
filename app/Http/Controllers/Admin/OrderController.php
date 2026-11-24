<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\PdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Admin: all orders, search / filters, detail, status changes. */
class OrderController extends Controller
{
    public const FILTERS = ['q', 'status', 'payment_terms', 'from', 'to'];

    public function index(Request $request)
    {
        $filters = $this->filters($request, self::FILTERS);

        $orders = Order::filter($filters)
            ->with('client')
            ->withCount('lines')
            ->latest('order_date')->latest('id')
            ->paginate(config('shop.admin_per_page'))
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'filters'));
    }

    public function show(Order $order)
    {
        $order->load('lines.product', 'client.user');

        return view('admin.orders.show', compact('order'));
    }

    /** Same PDF summary as the customer gets. */
    public function pdf(Order $order)
    {
        $order->load('lines.product', 'client');

        return PdfExport::document('pdf.order', compact('order'), 'commande-'.$order->number.'.pdf');
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders)
    {
        Gate::authorize('updateStatus', $order);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        ]);

        $orders->changeStatus($order, $data['status']);

        // Shown inside the "Statut" card, right next to the form that was used.
        return back()->with('status_updated', "Statut mis à jour : « {$order->statusLabel()} ».");
    }
}
