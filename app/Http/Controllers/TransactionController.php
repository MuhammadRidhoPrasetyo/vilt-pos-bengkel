<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionPaymentAttemptRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\ProductVariantResource;
use App\Http\Resources\ServiceOrderResource;
use App\Http\Resources\TransactionResource;
use App\Models\DiscountType;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Repositories\StoreRepository;
use App\Repositories\TransactionRepository;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepository $repository,
        private readonly TransactionService $service,
        private readonly StoreRepository $stores
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $paymentStatus = $request->string('payment_status')->toString();
        $storeId = $request->string('store_id')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $transactions = $this->repository->paginate($search, $type, $paymentStatus, $storeId, $startDate, $endDate);

        $summary = [
            'total_count' => Transaction::count(),
            'total_grand_total' => (float) Transaction::where('status', 'completed')->sum('grand_total'),
            'total_profit' => (float) Transaction::where('status', 'completed')->sum('total_profit'),
            'retail_count' => Transaction::where('type', 'retail')->count(),
            'service_count' => Transaction::where('type', 'service')->count(),
        ];

        return Inertia::render('transactions/index', [
            'transactions' => TransactionResource::collection($transactions),
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'payment_status' => $paymentStatus,
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'options' => [
                'stores' => $this->stores->options()->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $canFilterStore = $user && ($user->hasRole('owner') || $user->hasRole('super-admin') || $user->store_id === null);

        $storeOptions = $this->stores->options();
        $storeId = ! $canFilterStore
            ? $user?->store_id
            : ($request->string('store_id')->toString() ?: ($user?->store_id ?: $storeOptions->first()?->id));

        $payments = Payment::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['label' => $p->name, 'value' => $p->id, 'type' => $p->type]);

        $customers = Partner::query()
            ->with('vehicles')
            ->select(['id', 'name', 'phone', 'email'])
            ->orderBy('name')
            ->get();

        $discountTypes = DiscountType::query()
            ->select(['id', 'name', 'description'])
            ->orderBy('name')
            ->get();

        $readyServiceOrders = ServiceOrder::query()
            ->with(['store', 'customer', 'vehicle', 'items.productVariant.product', 'items.mechanic'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('status', ['ready', 'in_progress'])
            ->whereNull('transaction_id')
            ->orderBy('checkin_at', 'asc')
            ->get();

        $variants = ProductVariant::query()
            ->with(['product.category', 'product.brand', 'product.unit', 'media', 'product.media', 'stocks.warehouse', 'discounts.discountType'])
            ->latest()
            ->get();

        return Inertia::render('transactions/create', [
            'activeStoreId' => $storeId,
            'isStoreLocked' => ! $canFilterStore,
            'options' => [
                'stores' => $storeOptions->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]),
                'payments' => $payments,
                'customers' => $customers,
                'discountTypes' => $discountTypes,
            ],
            'readyServiceOrders' => ServiceOrderResource::collection($readyServiceOrders),
            'variants' => ProductVariantResource::collection($variants),
            'preselectedServiceOrderId' => $request->string('service_order_id')->toString(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $transaction = $this->service->create($request->validated(), $request->user()->id);

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Transaksi POS berhasil diproses dan disimpan.')
            ->with('print_transaction_id', $transaction->id);
    }

    public function show(string $id): Response
    {
        $transaction = $this->repository->findWithRelations($id);

        return Inertia::render('transactions/show', [
            'transaction' => new TransactionResource($transaction),
            'paymentOptions' => Payment::query()
                ->orderBy('name')
                ->get()
                ->map(fn ($payment) => [
                    'label' => $payment->name,
                    'value' => $payment->id,
                    'type' => $payment->type,
                ]),
        ]);
    }

    public function storePaymentAttempt(StoreTransactionPaymentAttemptRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->service->recordPaymentAttempt($transaction, $request->validated(), $request->user()->id);

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Pembayaran transaksi berhasil dicatat.');
    }

    public function print(string $id): Response
    {
        $transaction = $this->repository->findWithRelations($id);

        return Inertia::render('transactions/print', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $transaction = Transaction::findOrFail($id);
        $this->service->delete($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
    }
}
