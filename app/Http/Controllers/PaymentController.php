<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Ledger;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\DayStatus;
use App\Models\Payment;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                    return '₹ ' . number_format($row->net_monthly_expenses, 0);
                })

                ->editColumn('per_diet_calculation', function ($row) {
                    return '₹ ' . number_format($row->per_diet_calculation, 0);
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
        // dd($bill);
        $billDetails = BillDetail::with('user')->where('bill_id', $id)->get();

        return view('payment.create', compact('bill', 'billDetails'));
    }

    // In your PaymentController.php

    public function getPaymentData(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Payment is not available for this role.');
        }
        $billId = $request->bill_id;
        if (!$billId) {
            return response()->json([
                'success' => false,
                'message' => 'Bill ID is required.'
            ], 400);
        }
        $bill = Bill::find($billId);
        if (!$bill) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found.'
            ], 404);
        }
        if($bill->location_id !== $authUser->location_id){
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this bill.'
            ], 403);
        }
        if ($request->ajax()) {
            $data = BillDetail::with('user')->where('bill_id', $request->bill_id)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return $row->user?->first_name ?? 'N/A';
                })
                ->addColumn('role', function ($row) {
                    return $row->role ?? 'N/A';
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

        DB::beginTransaction();

        try {
            $authUser = Auth::user();
            $billDetail = BillDetail::findOrFail($request->bill_detail_id);

            // Check if bill detail exists
            if (!$billDetail) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Bill detail not found!'
                ], 404);
            }

            // Check if already fully paid
            if ($billDetail->balance <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This bill is already fully paid!'
                ], 400);
            }

            $currentDate = Carbon::now()->format('Y-m-d');

            // Get bill_id from bill_detail
            $billId = $billDetail->bill_id;

            // Calculate amounts
            $payableAmount = $billDetail->bill_amount ?? 0;
            $receiveAmount = $request->receive_amount;

            // Check if receive amount is greater than balance
            // if ($receiveAmount > $billDetail->balance) {
            //     DB::rollBack();
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Receive amount cannot be greater than balance amount!'
            //     ], 400);
            // }

            $balanceAmount = $billDetail->balance - $receiveAmount;

            // Determine status
            $status = 1; // Paid

            // Create payment record
            $payment = Payment::create([
                'bill_id' => $billId,
                'bill_detail_id' => $request->bill_detail_id,
                'user_id' => $billDetail->user_id,
                'payable_amount' => $payableAmount,
                'receive_amount' => $receiveAmount,
                'balance_amount' => $balanceAmount,
                'amount' => $receiveAmount         ,
                'status' => $status,
                'payment_date' => $request->payment_date,
                'payment_time' => now()->format('H:i:s'),
            ]);

            if (!$payment) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment record!'
                ], 500);
            }

            // Update balance in bill_details
            $billDetail->payment_flag = 1;
            $billDetail->payment_amount = ($billDetail->payment_amount ?? 0) + $receiveAmount;
            $billDetail->save();

            // Get user
            $user = User::find($billDetail->user_id);

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User not found!'
                ], 404);
            }

            // Get calendar ID
            $calendarId = DayStatus::where('date', $currentDate)
                ->where('location_id', $user->location_id)
                ->value('id');

            // Get existing ledger for user
            $existingLedger = Ledger::where('user_id', $billDetail->user_id)
                ->where('location_id', $authUser->location_id)
                ->orderByDesc('id')
                ->first();

            if ($existingLedger) {
                $newBalance = $existingLedger->balance - $receiveAmount;
                $due = 0;
                $paid = $receiveAmount;
            } else {
                $newBalance = -$receiveAmount;
                $due = 0;
                $paid = $receiveAmount;
            }

            // Create ledger entry
            $ledger = Ledger::create([
                'user_id' => $billDetail->user_id,
                'calendar_id' => $calendarId,
                'location_id' => $authUser->location_id,
                'bill_id' => $billId,
                'date' => $currentDate,
                'transaction' => 'Monthly Bill Payment',
                'due' => $due,
                'paid' => $paid,
                'balance' => $newBalance,
                'created_by' => $authUser->id
            ]);

            if (!$ledger) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create ledger entry!'
                ], 500);
            }

            // Commit transaction if everything is successful
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment successful!',
                'data' => $payment,
                'balance_remaining' => $balanceAmount
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bill detail not found!'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed!',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error for debugging
            \Log::error('Payment Store Error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine() . ' in ' . $e->getFile());

            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function openingCreate()
    {
        $authUser = Auth::user();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Security Deposit is not available for this role.');
        }
        $users = User::where('status', 1)->where('location_id', $authUser->location_id)->whereIn('role', ['Member', 'Non Member'])
            ->orderBy('first_name', 'Asc')->get();
        return view('payment.opening_amount', compact('users'));
    }


    public function openingStore(Request $request)
    {
        $request->validate([
            'date'    => 'required|date',
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            $authUser = Auth::user();
            $user = User::findOrFail($request->user_id);

            // Get calendar ID based on selected date
            $calendarId = DayStatus::where('date', Carbon::parse($request->date)->format('Y-m-d'))
                ->where('location_id', $user->location_id)
                ->value('id');

            if (!$calendarId) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->with('error', 'Day status/calendar not found for the selected date.');
            }

            // Check if ledger already exists for this user
            $existingLedger = Ledger::where('user_id', $request->user_id)
                ->where('location_id', $authUser->location_id)
                ->orderByDesc('id')
                ->first();

            if ($existingLedger) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->with('error', 'Ledger entry already exists for this user.');
            }

            $amount = (float) $request->amount;

            // Opening balance
            $due = $amount;
            $paid = 0;
            $balance = $amount;

            // Create ledger entry
            $ledger = Ledger::create([
                'user_id'     => $request->user_id,
                'location_id' => $authUser->location_id,
                'calendar_id' => $calendarId,
                'date'        => Carbon::parse($request->date)->format('Y-m-d'),
                'transaction' => 'Opening Balance',
                'due'         => $due,
                'paid'        => $paid,
                'balance'     => $balance,
                'created_by'  => $authUser->id,
            ]);

            if (!$ledger) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->with('error', 'Failed to create opening balance.');
            }

            DB::commit();

            return back()
                ->with('success', 'Opening balance created successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Opening Balance Store Error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine() . ' in ' . $e->getFile());

            return back()
                ->withInput()
                ->with('error', 'Failed to create opening balance.');
        }
    }
}
