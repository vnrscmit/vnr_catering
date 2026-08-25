<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\Payment;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function index()
    {
        $authUser = Auth::User();

        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill Payment is not available for this role.');
        }

        if ($request->ajax()) {
            $bills = Bill::where('type', 'Individual')->latest();

            return DataTables::of($bills)
                ->addIndexColumn()

                // Add user name column
                ->addColumn('user_name', function ($row) {
                    return $row->user ? $row->user->first_name : 'N/A';
                })

                ->editColumn('generate_month', function ($row) {
                    return Carbon::parse($row->generate_month . '-01')->format('M-Y');
                })
                ->editColumn('bill_date', function ($row) {
                    return Carbon::parse($row->bill_date)->format('d-m-Y');
                })

                ->editColumn('total_diets', function ($row) {
                    return number_format($row->total_diets);
                })

                ->editColumn('net_chargeable_diet', function ($row) {
                    return '₹ ' . number_format($row->net_monthly_expenses, 2);
                })

                ->editColumn('per_diet_calculation', function ($row) {
                    return '₹ ' . number_format($row->per_diet_calculation, 2);
                })

                ->addColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge badge-primary">Submitted</span>';
                    } elseif ($row->status == 2) {
                        return '<span class="badge badge-warning">Pending</span>';
                    }
                })

                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<a href="' . route('bill-generate.individual.show', $row->id) . '"
        class="btn btn-sm btn-info me-1"
        title="View">
        <i class="fa fa-eye"></i>
    </a>';

                    if ($row->status == 2 || $row->status == 0) {
                        $buttons .= '<a href="' . route('bill-generate.individual.edit', $row->id) . '"
        class="btn btn-sm btn-warning me-1"
        title="Edit">
        <i class="fa fa-edit"></i>
    </a>';


                        $buttons .= '<a href="' . route('bill-generate.individual.delete', $row->id) . '"
        class="btn btn-sm btn-danger me-1"
        title="Delete">
         <i class="fa fa-trash"></i>
    </a>';
                    }

                    return '<div class="d-flex" style="gap: 2px;">' . $buttons . '</div>';
                })->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('bill.individual.index');
    }
    public function create($id)
    {
        $bill = Bill::findOrFail($id);
        $billDetails = BillDetail::with('user')->where('bill_id', $id)->get();

        return view('payment.create', compact('bill', 'billDetails'));
    }

    // In your PaymentController.php

    public function getPaymentData(Request $request)
    {
        if ($request->ajax()) {
            $data = BillDetail::with('user')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return $row->user?->first_name ?? 'N/A';
                })
                ->addColumn('role', function ($row) {
                    return $row->user?->role ?? 'N/A';
                })
                ->addColumn('user_diets', function ($row) {
                    return $row->user_diets;
                })
                ->addColumn('rate_per_diet', function ($row) {
                    return $row->rate_per_diet;
                })
                ->addColumn('bill_amount', function ($row) {
                    return $row->bill_amount;
                })
                ->addColumn('previous_balance', function ($row) {
                    return $row->pre_balance;
                })
                ->addColumn('total_amount_due', function ($row) {
                    return ($row->bill_amount + $row->pre_balance);
                })
                ->addColumn('payment_amount', function ($row) {
                    return $row->payment_amount;
                })

                ->addColumn('action', function ($row) {
                    // Only show Pay button if status is not 'Paid'
                    if ($row->payment_flag == 0) {
                        $btn = '<button type="button" class="btn btn-warning btn-sm pay-btn" 
            data-id="' . $row->id . '" 
            data-name="' . ($row->user?->first_name ?? 'N/A') . '" 
            data-amount="' . ($row->bill_amount ?? 0) . '"
            data-bs-toggle="modal" 
            data-bs-target="#paymentModal">
          <i class="fas fa-hand-holding-usd"></i> Pay
        </button>';
                        return $btn;
                    }

                    // If status is 'Paid', show a badge or empty
                    return '<span class="badge bg-primary">Paid</span>';
                })
                ->rawColumns(['name', 'role', 'action'])

                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'bill_detail_id' => 'required|exists:bill_details,id',
            'receive_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_note' => 'nullable|string|max:500',
        ]);

        try {
            $billDetail = BillDetail::findOrFail($request->bill_detail_id);

            // Get bill_id from bill_detail
            $billId = $billDetail->bill_id;

            // Calculate amounts
            $payableAmount = $billDetail->bill_amount ?? 0;
            $receiveAmount = $request->receive_amount;
            $balanceAmount =  $payableAmount - $receiveAmount;

            // Determine status
            $status = 'Paid';

            // Create payment record
            $payment = Payment::create([
                'bill_id' => $billId,
                'bill_detail_id' => $request->bill_detail_id,
                'user_id' => $billDetail->user_id,
                'payable_amount' => $payableAmount,
                'receive_amount' => $receiveAmount,
                'balance_amount' => $balanceAmount,
                'amount' => $receiveAmount,
                'status' => $status,
                'payment_date' => $request->payment_date,
                'payment_time' => now()->format('H:i:s'),
            ]);

            // Update balance in bill_details
            $billDetail->balance = $balanceAmount;
            $billDetail->payment_flag = 1;
            $billDetail->payment_amount = $receiveAmount;  // Add to existing payment amount
            $billDetail->save();


            return response()->json([
                'success' => true,
                'message' => 'Payment successful!',
                'data' => $payment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
