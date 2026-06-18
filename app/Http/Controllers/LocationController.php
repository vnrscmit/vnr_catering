<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */

       use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function index()
    {
        $locations = Location::orderBy('name', 'ASC')->paginate(10);
        return view('admin.locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|unique:locations,name|max:255',
            'short_code' => 'required|string|unique:locations,short_code|max:50',
            'status' => 'required',
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Location created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        return view('admin.locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:locations,name,' . $location->id . '|max:255',
            'short_code' => 'required|string|unique:locations,short_code,' . $location->id . '|max:50',
            'status' => 'sometimes|boolean',
        ]);

        $validated['status'] = $request->has('status') ? 1 : 0;

        $location->update($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully!');
    }
}
