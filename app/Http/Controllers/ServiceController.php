<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ServiceController extends Controller
{
    public function index()
    {
        if (request()->wantsJson()) {
            $services = Service::query()->with('creator:id,name');

            return DataTables::of($services)
                ->addIndexColumn()
                ->editColumn('name', fn ($row) =>
                    '<a href="' . route('services.show', $row->id) . '" class="text-primary fw-bold">' . e($row->name) . '</a>'
                    . '<br><small class="text-muted">' . e($row->code) . '</small>'
                )
                ->editColumn('variant', fn ($row) => $row->variant !== 'n/a' ? ucfirst($row->variant) : '—')
                ->editColumn('status', fn ($row) => $row->toggleButton(route('services.update-status', $row->id)))
                ->addColumn('action', function ($row) {
                    $btn = $row->editButton(route('services.edit', $row->id));
                    $btn .= $row->deleteButton(route('services.destroy', $row->id));
                    return $btn;
                })
                ->editColumn('created_at', fn ($row) => $row->created_at->format('d M Y'))
                ->rawColumns(['name', 'status', 'action'])
                ->make(true);
        }

        return view('services.index');
    }

    public function create()
    {
        $variants = ['n/a', 'live-in', 'live-out'];
        return view('services.create', compact('variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:services,code'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'variant'     => ['required', 'in:n/a,live-in,live-out'],
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $service = Service::create($validated);
            activity()->causedBy(auth()->user())->performedOn($service)->log('Service created');
            flash()->success('Service created successfully.');
            return redirect()->route('services.show', $service->id);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function show(Service $service)
    {
        $service->load(['priceVersions.creator', 'priceVersions.approver']);
        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $variants = ['n/a', 'live-in', 'live-out'];
        return view('services.edit', compact('service', 'variants'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:services,code,' . $service->id],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'variant'     => ['required', 'in:n/a,live-in,live-out'],
        ]);

        try {
            $service->update($validated);
            activity()->causedBy(auth()->user())->performedOn($service)->log('Service updated');
            flash()->success('Service updated successfully.');
            return redirect()->route('services.show', $service->id);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Service $service)
    {
        try {
            activity()->causedBy(auth()->user())->performedOn($service)->log('Service deleted');
            $service->delete();
            flash()->success('Service deleted.');
        } catch (\Exception $e) {
            flash()->error('Failed to delete service.');
        }
        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);
            $service->is_active = $request->toggle_input === 'true' ? 1 : 0;
            $service->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
