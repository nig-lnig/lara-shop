<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Ownership rules for orders. Laravel finds this class automatically
 * (Order model -> OrderPolicy). Used in controllers with $this->authorize()
 * / Gate::authorize() and in Blade with @can.
 */
class OrderPolicy
{
    /** Admins see every order; a customer only sees the orders of their client. */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || ($user->client_id !== null && $user->client_id === $order->client_id);
    }

    /** A customer may cancel their own order while it is still pending. */
    public function cancel(User $user, Order $order): bool
    {
        return $user->client_id !== null
            && $user->client_id === $order->client_id
            && $order->canBeCancelledByCustomer();
    }

    /** Only admins change the fulfillment status. */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
