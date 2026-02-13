<?php

namespace App\Http\Controllers;

use App\Services\MockDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // Search functionality
        $query = $request->input('search');
        $clients = $query
            ? MockDataService::searchClients($query)
            : MockDataService::getClients();

        return Inertia::render('Clients/Index', [
            'clients' => array_values($clients), // Ensure clean array for React
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'address' => 'required|string',
            'gst_number' => 'required|string',
            'state' => 'required|string',
            'state_code' => 'required|string',
        ]);

        MockDataService::addClient($validated);

        return redirect()->back()->with('message', 'Client added successfully');
    }

    public function update(Request $request, string $email)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'gst_number' => 'required|string',
            'state' => 'required|string',
            'state_code' => 'required|string',
        ]);

        MockDataService::updateClient($email, $validated);

        return redirect()->back()->with('message', 'Client updated successfully');
    }

    public function destroy(string $email)
    {
        MockDataService::deleteClient($email);
        return redirect()->back()->with('message', 'Client deleted successfully');
    }
}
