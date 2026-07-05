<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly PaymentService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('payments/index', [
            'records' => PaymentResource::collection($this->payments->paginate($request->string('search')->toString())),
            'filters' => ['search' => $request->string('search')->toString()],
            'config' => [
                'title' => 'Payments',
                'singular' => 'Payment',
                'route' => '/payments',
                'searchPlaceholder' => 'Cari payment',
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'required' => true, 'table' => true],
                    ['name' => 'type', 'label' => 'Tipe', 'table' => true],
                    ['name' => 'account_number', 'label' => 'No. Akun', 'table' => true],
                    ['name' => 'account_name', 'label' => 'Nama Akun', 'table' => true],
                    ['name' => 'provider_code', 'label' => 'Kode Provider'],
                ],
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('payments.index');
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('payments.index')->with('success', 'Payment berhasil dibuat.');
    }

    public function show(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index');
    }

    public function edit(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->service->update($payment, $request->validated());

        return redirect()->route('payments.index')->with('success', 'Payment berhasil diperbarui.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->service->delete($payment);

        return redirect()->route('payments.index')->with('success', 'Payment berhasil dihapus.');
    }
}
