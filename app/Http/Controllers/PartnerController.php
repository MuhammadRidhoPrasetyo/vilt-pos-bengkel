<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use App\Repositories\PartnerRepository;
use App\Repositories\PartnerRoleRepository;
use App\Repositories\StoreRepository;
use App\Services\PartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function __construct(
        private readonly PartnerRepository $partners,
        private readonly StoreRepository $stores,
        private readonly PartnerRoleRepository $partnerRoles,
        private readonly PartnerService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('partners/index', [
            'records' => PartnerResource::collection($this->partners->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($store) => ['label' => $store->name, 'value' => $store->id]),
                'partnerRoles' => $this->partnerRoles->options()->map(fn ($role) => ['label' => $role->name, 'value' => $role->id]),
            ],
            'config' => $this->config(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('partners.index');
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('partners.index')->with('success', 'Partner berhasil dibuat.');
    }

    public function show(Partner $partner): RedirectResponse
    {
        return redirect()->route('partners.index');
    }

    public function edit(Partner $partner): RedirectResponse
    {
        return redirect()->route('partners.index');
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $this->service->update($partner, $request->validated());

        return redirect()->route('partners.index')->with('success', 'Partner berhasil diperbarui.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $this->service->delete($partner);

        return redirect()->route('partners.index')->with('success', 'Partner berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'title' => 'Partners',
            'singular' => 'Partner',
            'route' => '/partners',
            'searchPlaceholder' => 'Cari partner',
            'defaults' => ['kind' => 'person', 'is_active' => true, 'roles' => []],
            'fields' => [
                ['name' => 'store_id', 'label' => 'Store', 'type' => 'select', 'optionKey' => 'stores', 'table' => true, 'displayKey' => 'store.name'],
                ['name' => 'linked_store_id', 'label' => 'Linked Store', 'type' => 'select', 'optionKey' => 'stores', 'displayKey' => 'linked_store.name'],
                ['name' => 'code', 'label' => 'Kode', 'required' => true, 'table' => true],
                ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                ['name' => 'kind', 'label' => 'Jenis', 'type' => 'select', 'required' => true, 'table' => true, 'options' => [['label' => 'Person', 'value' => 'person'], ['label' => 'Organization', 'value' => 'organization']]],
                ['name' => 'contact_person', 'label' => 'Contact Person', 'table' => true],
                ['name' => 'phone', 'label' => 'Telepon', 'table' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'city', 'label' => 'Kota'],
                ['name' => 'province', 'label' => 'Provinsi'],
                ['name' => 'postal_code', 'label' => 'Kode Pos'],
                ['name' => 'npwp', 'label' => 'NPWP'],
                ['name' => 'bank_name', 'label' => 'Bank'],
                ['name' => 'bank_account', 'label' => 'No. Rekening'],
                ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'table' => true],
                ['name' => 'roles', 'label' => 'Partner Roles', 'type' => 'multiselect', 'optionKey' => 'partnerRoles', 'table' => true, 'displayKey' => 'roles'],
                ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
            ],
        ];
    }
}
