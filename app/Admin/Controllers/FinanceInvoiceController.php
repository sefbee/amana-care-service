<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\FinanceInvoice;
use App\Admin\Models\Household;
use App\Admin\Services\FinanceMoney;
use App\Admin\Services\FinanceLedgerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FinanceInvoiceController extends Controller
{
    private function authorizeFinance(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['fn', 'om', 'co', 'platform-superuser'], true), 403);
    }

    private function authorizeFinanceWrite(Request $request, CrmRecordAccess $access): void
    {
        abort_unless(in_array($access->roleSlug($request->user()), ['fn', 'platform-superuser'], true), 403);
    }

    public function index(Request $request, CrmRecordAccess $access)
    {
        $this->authorizeFinance($request, $access);
        $visibleHouseholds = $access->households(Household::query(), $request->user())->select('households.id');
        $query = FinanceInvoice::query()->whereIn('household_id', $visibleHouseholds)->with('household')->latest('id');
        if ($request->wantsJson()) {
            activity('finance_invoice_access')->causedBy($request->user())
                ->withProperties(['invoice_ids' => (clone $query)->pluck('id')->all()])
                ->log('Finance invoice queue accessed');

            return DataTables::of($query)
                ->addColumn('household_name', fn (FinanceInvoice $invoice) => e($invoice->household->display_name))
                ->editColumn('total', fn (FinanceInvoice $invoice) => 'BDT '.FinanceMoney::decimal(FinanceMoney::cents($invoice->getRawOriginal('total'))))
                ->addColumn('balance', fn (FinanceInvoice $invoice) => 'BDT '.$invoice->balance)
                ->editColumn('period_start', fn (FinanceInvoice $invoice) => $invoice->period_start->format('d M Y').' – '.$invoice->period_end->format('d M Y'))
                ->addColumn('action', fn (FinanceInvoice $invoice) => '<a class="btn btn-sm btn-light-primary" href="'.e(route('finance.invoices.show', $invoice)).'">Open</a>')
                ->rawColumns(['action'])
                ->make(true);
        }

        activity('finance_invoice_access')->causedBy($request->user())
            ->withProperties(['invoice_count' => (clone $query)->count()])
            ->log('Finance invoice queue viewed');

        return view('admin.finance.invoices.index');
    }

    public function show(Request $request, FinanceInvoice $invoice, CrmRecordAccess $access)
    {
        $this->authorizeFinance($request, $access);
        $canIssue = in_array($access->roleSlug($request->user()), ['fn', 'platform-superuser'], true);
        $canViewPayments = in_array($access->roleSlug($request->user()), ['fn', 'om', 'platform-superuser'], true);
        $invoice->loadMissing('household');
        $access->assertHousehold($request->user(), $invoice->household);
        $invoice->load(['household', 'billingContact', 'placement', 'lines.priceVersion']);
        if ($canViewPayments) {
            $invoice->load('payments');
        }
        activity('finance_invoice_access')->causedBy($request->user())->performedOn($invoice)
            ->withProperties(['invoice_number' => $invoice->invoice_number, 'result' => 'allowed'])
            ->log('Finance invoice detail viewed');

        return view('admin.finance.invoices.show', compact('invoice', 'canIssue', 'canViewPayments'));
    }

    public function issue(Request $request, FinanceInvoice $invoice, CrmRecordAccess $access, FinanceLedgerService $ledger)
    {
        $this->authorizeFinanceWrite($request, $access);
        $invoice->loadMissing('household');
        $access->assertHousehold($request->user(), $invoice->household);
        $data = $request->validate(['due_date' => ['required', 'date', 'after_or_equal:today']]);
        $ledger->issue($invoice, $data['due_date'], (int) $request->user()->id);

        return redirect()->route('finance.invoices.show', $invoice)->with('success', 'Invoice issued to the recorded Billing Contact.');
    }
}
