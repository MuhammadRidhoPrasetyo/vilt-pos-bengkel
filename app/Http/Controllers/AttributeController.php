<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Repositories\AttributeRepository;
use App\Services\AttributeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttributeController extends Controller
{
    public function __construct(
        private readonly AttributeRepository $attributes,
        private readonly AttributeService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('attributes/index', [
            'records' => AttributeResource::collection($this->attributes->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => [
                'title' => 'Attributes',
                'singular' => 'Attribute',
                'route' => '/attributes',
                'searchPlaceholder' => 'Cari attribute',
                'defaults' => ['options' => []],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'options', 'label' => 'Opsi', 'type' => 'tags', 'table' => true],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('attributes.index');
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('attributes.index')->with('success', 'Attribute berhasil dibuat.');
    }

    public function show(Attribute $attribute): RedirectResponse
    {
        return redirect()->route('attributes.index');
    }

    public function edit(Attribute $attribute): RedirectResponse
    {
        return redirect()->route('attributes.index');
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $this->service->update($attribute, $request->validated());

        return redirect()->route('attributes.index')->with('success', 'Attribute berhasil diperbarui.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $this->service->delete($attribute);

        return redirect()->route('attributes.index')->with('success', 'Attribute berhasil dihapus.');
    }
}
