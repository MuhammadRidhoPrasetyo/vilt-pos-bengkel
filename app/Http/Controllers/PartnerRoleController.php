<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRoleRequest;
use App\Http\Requests\UpdatePartnerRoleRequest;
use App\Http\Resources\PartnerRoleResource;
use App\Models\PartnerRole;
use App\Repositories\PartnerRoleRepository;
use App\Services\PartnerRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerRoleController extends Controller
{
    public function __construct(
        private readonly PartnerRoleRepository $partnerRoles,
        private readonly PartnerRoleService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('partner-roles/index', [
            'records' => PartnerRoleResource::collection($this->partnerRoles->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => [
                'title' => 'Partner Roles',
                'singular' => 'Partner Role',
                'route' => '/partner-roles',
                'searchPlaceholder' => 'Cari partner role',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('partner-roles.index');
    }

    public function store(StorePartnerRoleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('partner-roles.index')->with('success', 'Partner role berhasil dibuat.');
    }

    public function show(PartnerRole $partnerRole): RedirectResponse
    {
        return redirect()->route('partner-roles.index');
    }

    public function edit(PartnerRole $partnerRole): RedirectResponse
    {
        return redirect()->route('partner-roles.index');
    }

    public function update(UpdatePartnerRoleRequest $request, PartnerRole $partnerRole): RedirectResponse
    {
        $this->service->update($partnerRole, $request->validated());

        return redirect()->route('partner-roles.index')->with('success', 'Partner role berhasil diperbarui.');
    }

    public function destroy(PartnerRole $partnerRole): RedirectResponse
    {
        $this->service->delete($partnerRole);

        return redirect()->route('partner-roles.index')->with('success', 'Partner role berhasil dihapus.');
    }
}
