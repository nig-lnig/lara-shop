<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

/** Admin CRUD for clients (PDF: Client). */
class ClientController extends Controller
{
    public const FILTERS = ['q', 'type', 'archived'];

    public function index(Request $request)
    {
        $filters = $this->filters($request, self::FILTERS);

        $clients = Client::filter($filters)
            ->archiveState($filters['archived'] ?? null)
            ->with('user:id,client_id,email')
            ->withCount('orders')
            ->orderBy('id')
            ->paginate(config('shop.admin_per_page'))
            ->withQueryString();

        return view('admin.clients.index', compact('clients', 'filters'));
    }

    public function create()
    {
        return view('admin.clients.form', ['client' => new Client(['type' => Client::DEFAULT_TYPE, 'special_discount' => 0])]);
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());

        return redirect()->route('admin.clients.index')->with('success', "Client {$client->code} créé.");
    }

    public function edit(Client $client)
    {
        return view('admin.clients.form', compact('client'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        // Changing the discount affects FUTURE orders only:
        // past orders keep the rate stored in orders.discount_rate.
        $client->update($request->validated());

        return redirect()->route('admin.clients.index')->with('success', "Client {$client->code} mis à jour.");
    }

    /**
     * A client that has orders or a login account is ARCHIVED (kept for history).
     * Only a client with no history at all is really deleted.
     */
    public function destroy(Client $client)
    {
        if ($client->orders()->exists() || $client->user()->exists()) {
            $client->archive();

            return back()->with('success', "Client {$client->code} archivé (il possède un historique, il n’a pas été supprimé).");
        }

        $client->delete();

        return back()->with('success', "Client {$client->code} supprimé.");
    }

    public function restore(Client $client)
    {
        $client->unarchive();

        return back()->with('success', "Client {$client->code} restauré.");
    }
}
