<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Repositories\StoreRepository;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __construct(
        private readonly StoreRepository $stores,
        private readonly StoreService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('stores/index', [
            'records' => StoreResource::collection($this->stores->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => $this->config(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('stores.index');
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('stores.index')->with('success', 'Store berhasil dibuat.');
    }

    public function show(Store $store): RedirectResponse
    {
        return redirect()->route('stores.index');
    }

    public function edit(Store $store): RedirectResponse
    {
        return redirect()->route('stores.index');
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $this->service->update($store, $request->validated());

        return redirect()->route('stores.index')->with('success', 'Store berhasil diperbarui.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->service->delete($store);

        return redirect()->route('stores.index')->with('success', 'Store berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'title' => 'Stores',
            'singular' => 'Store',
            'route' => '/stores',
            'searchPlaceholder' => 'Cari store',
            'defaults' => ['receipt_number_format' => '{STORE_CODE}/{YYYY}/{MM}/{NUMBER}', 'receipt_sequence' => 0],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode', 'required' => true, 'table' => true],
                ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                ['name' => 'phone', 'label' => 'Telepon', 'table' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'table' => true],
                ['name' => 'city', 'label' => 'Kota', 'table' => true],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'province', 'label' => 'Provinsi'],
                ['name' => 'postal_code', 'label' => 'Kode Pos'],
                ['name' => 'receipt_number_format', 'label' => 'Format Nomor Struk'],
                ['name' => 'receipt_sequence', 'label' => 'Urutan Struk', 'type' => 'number'],
                ['name' => 'receipt_sequence_year', 'label' => 'Tahun Urutan', 'type' => 'number'],
                ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
            ],
        ];
    }
}
