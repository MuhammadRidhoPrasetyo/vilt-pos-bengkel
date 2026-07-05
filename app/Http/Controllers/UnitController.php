<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Repositories\UnitRepository;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function __construct(
        private readonly UnitRepository $units,
        private readonly UnitService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('units/index', [
            'records' => UnitResource::collection($this->units->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => [
                'title' => 'Units',
                'singular' => 'Unit',
                'route' => '/units',
                'searchPlaceholder' => 'Cari unit',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'symbol', 'label' => 'Simbol', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('units.index');
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('units.index')->with('success', 'Unit berhasil dibuat.');
    }

    public function show(Unit $unit): RedirectResponse
    {
        return redirect()->route('units.index');
    }

    public function edit(Unit $unit): RedirectResponse
    {
        return redirect()->route('units.index');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->service->update($unit, $request->validated());

        return redirect()->route('units.index')->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->service->delete($unit);

        return redirect()->route('units.index')->with('success', 'Unit berhasil dihapus.');
    }
}
