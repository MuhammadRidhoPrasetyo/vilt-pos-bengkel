<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrinterRequest;
use App\Http\Requests\UpdatePrinterRequest;
use App\Http\Resources\PrinterResource;
use App\Models\Printer;
use App\Repositories\PrinterRepository;
use App\Repositories\StoreRepository;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrinterController extends Controller
{
    public function __construct(
        private readonly PrinterRepository $repository,
        private readonly PrinterService $service,
        private readonly StoreRepository $stores
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $storeId = $request->string('store_id')->toString();

        $printers = $this->repository->paginate($search, $storeId);

        $summary = [
            'total_count' => Printer::count(),
            'default_count' => Printer::where('is_default', true)->count(),
            'network_count' => Printer::where('connection_type', 'network')->count(),
            'usb_count' => Printer::where('connection_type', 'usb')->count(),
        ];

        return Inertia::render('printers/index', [
            'printers' => PrinterResource::collection($printers),
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'store_id' => $storeId,
            ],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
            ],
        ]);
    }

    public function store(StorePrinterRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Konfigurasi printer berhasil ditambahkan.');
    }

    public function update(UpdatePrinterRequest $request, Printer $printer): RedirectResponse
    {
        $this->service->update($printer, $request->validated());

        return redirect()->back()->with('success', 'Konfigurasi printer berhasil diperbarui.');
    }

    public function destroy(Printer $printer): RedirectResponse
    {
        $this->service->delete($printer);

        return redirect()->back()->with('success', 'Konfigurasi printer berhasil dihapus.');
    }

    public function test(Printer $printer): JsonResponse
    {
        $printer->load('store');

        return response()->json([
            'status' => 'success',
            'message' => 'Uji coba cetak berhasil disiapkan.',
            'test_data' => [
                'printer_name' => $printer->name,
                'connection_type' => strtoupper($printer->connection_type),
                'address' => $printer->address,
                'store_name' => $printer->store?->name ?? 'POS Bengkel System',
                'store_address' => $printer->store?->address ?? '-',
                'store_phone' => $printer->store?->phone ?? '-',
                'timestamp' => now()->format('d/m/Y H:i:s'),
            ],
        ]);
    }
}
