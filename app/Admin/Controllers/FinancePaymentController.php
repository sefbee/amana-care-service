<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\FinanceInvoice;
use App\Admin\Models\FinancePayment;
use App\Admin\Models\Household;
use App\Admin\Services\FinanceLedgerService;
use App\Admin\Services\FinanceMoney;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class FinancePaymentController extends Controller
{
    private function authorizeFinance(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['fn', 'om', 'platform-superuser'], true), 403);
    }

    private function authorizeFinanceWrite(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['fn', 'platform-superuser'], true), 403);
    }

    public function index(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeFinance($request, $access);
        $canReconcile = in_array($access->roleSlug($request->user()), ['fn', 'platform-superuser'], true);
        $visibleHouseholds = $access->households(Household::query(), $request->user())->select('households.id');
        $query = FinancePayment::query()->whereIn('household_id', $visibleHouseholds)->with('household')->latest('id');
        if ($request->wantsJson()) {
            activity('finance_payment_access')->causedBy($request->user())
                ->withProperties(['payment_ids' => (clone $query)->pluck('id')->all()])
                ->log('Finance payment queue accessed');
            return DataTables::of($query)
                ->addColumn('household_name', fn (FinancePayment $payment) => e($payment->household->display_name))
                ->editColumn('amount', fn (FinancePayment $payment) => 'BDT '.FinanceMoney::decimal(FinanceMoney::cents($payment->getRawOriginal('amount'))))
                ->editColumn('paid_at', fn (FinancePayment $payment) => $payment->paid_at->format('d M Y'))
                ->addColumn('action', function (FinancePayment $payment) use ($canReconcile): string {
                    $receipt = '<a class="btn btn-sm btn-light-primary" href="'.e(route('finance.payments.receipt', $payment)).'">Receipt</a>';
                    if (! $canReconcile || $payment->status !== 'Unreconciled') {
                        return $receipt;
                    }

                    return $receipt.' <form class="d-inline-flex gap-1" method="POST" action="'.e(route('finance.payments.reconcile', $payment)).'">'
                        .csrf_field().'<input class="form-control form-control-sm" name="bank_statement_reference" maxlength="150" placeholder="Bank statement ref" required>'
                        .'<button class="btn btn-sm btn-success" type="submit">Reconcile</button></form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        activity('finance_payment_access')->causedBy($request->user())
            ->withProperties(['payment_count' => (clone $query)->count()])
            ->log('Finance payment queue viewed');

        return view('admin.finance.payments.index', compact('canReconcile'));
    }

    public function create(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeFinanceWrite($request, $access);
        $visibleHouseholds = $access->households(Household::query(), $request->user())->select('households.id');
        $invoices = FinanceInvoice::query()->whereIn('household_id', $visibleHouseholds)->with('household')
            ->whereIn('status', ['Issued', 'Partially Paid', 'Overdue'])->orderBy('household_id')->orderBy('id')->get()
            ->filter(fn (FinanceInvoice $invoice) => FinanceMoney::cents($invoice->balance) > 0)->values();
        activity('finance_invoice_access')->causedBy($request->user())
            ->withProperties(['invoice_count' => $invoices->count()])
            ->log('Open invoice allocation choices viewed');

        return view('admin.finance.payments.create', [
            'invoices' => $invoices,
            'methods' => FinancePayment::METHODS,
        ]);
    }

    public function store(Request $request, CrmRecordAccess $access, FinanceLedgerService $ledger)
    {
        $this->authorizeFinanceWrite($request, $access);
        try {
            $data = $request->validate([
                'method' => ['required', Rule::in(FinancePayment::METHODS)],
                'reference_id' => ['required', 'string', 'max:150'],
                'paid_at' => ['required', 'date', 'before_or_equal:today'],
                'allocations' => ['required', 'array', 'min:1', 'max:50'],
                'allocations.*.invoice_id' => ['required', 'integer', 'distinct', 'exists:finance_invoices,id'],
                'allocations.*.amount' => ['required', 'regex:/^\\d{1,10}(?:\\.\\d{1,2})?$/'],
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())
                ->withInput($request->except(['reference_id']));
        }

        $visibleHouseholds = $access->households(Household::query(), $request->user())->select('households.id');
        $allowedInvoiceIds = FinanceInvoice::query()->whereIn('household_id', $visibleHouseholds)
            ->whereIn('id', collect($data['allocations'])->pluck('invoice_id'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $requestedInvoiceIds = collect($data['allocations'])->pluck('invoice_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (array_diff($requestedInvoiceIds, $allowedInvoiceIds) !== []) {
            throw ValidationException::withMessages(['allocations' => 'One or more invoices are unavailable to this account.']);
        }

        $payment = $ledger->recordPayment($data, (int) $request->user()->id);

        return redirect()->route('finance.payments.receipt', $payment)->with('success', 'Payment recorded for Amanah. Receipt generated; payment remains unreconciled.');
    }

    public function reconcile(Request $request, FinancePayment $payment, CrmRecordAccess $access, FinanceLedgerService $ledger)
    {
        $this->authorizeFinanceWrite($request, $access);
        $payment->loadMissing('household');
        $access->assertHousehold($request->user(), $payment->household);
        $data = $request->validate(['bank_statement_reference' => ['required', 'string', 'max:150']]);
        $ledger->reconcile($payment, $data['bank_statement_reference'], (int) $request->user()->id);

        return redirect()->route('finance.payments.index')->with('success', 'Payment reconciled and locked.');
    }

    public function receipt(Request $request, FinancePayment $payment, CrmRecordAccess $access)
    {
        $this->authorizeFinance($request, $access);
        $payment->loadMissing('household');
        $access->assertHousehold($request->user(), $payment->household);
        $payment->load(['household', 'recorder', 'reconciler', 'allocations.invoice']);
        activity('finance_receipt_access')->causedBy($request->user())->performedOn($payment)
            ->withProperties(['receipt_number' => $payment->receipt_number, 'amount' => $payment->amount, 'currency' => $payment->currency])
            ->log('Payment receipt generated');

        return Pdf::loadView('admin.finance.payments.receipt', ['payment' => $payment])
            ->setPaper('a4')->download($payment->receipt_number.'.pdf');
    }
}
