<?php

namespace App\Http\Controllers;

use App\Models\SecurityAmount;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SecurityAmountController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function index(Request $request)
    {
        try {
            $query = SecurityAmount::with(['user', 'creator']);

            // Filter by user_id
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Filter by status
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            // Search by transaction_id
            if ($request->has('transaction_id') && $request->transaction_id) {
                $query->where('transaction_id', $request->transaction_id);
            }

            $securityAmounts = $query->orderBy('id', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $securityAmounts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security amounts: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        $authUser = Auth::user();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Security Deposit is not available for this role.');
        }
        $users = User::where('status', 1)->where('location_id', $authUser->location_id)->whereIn('role', ['Member', 'Non Member'])
            ->orderBy('first_name', 'Asc')->get();
        return view('security_amount.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created security amount in storage.
     */
  public function store(Request $request)
{
    // Validation rules
    $validator = Validator::make($request->all(), [
        'date' => 'required|date',
        'user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:0',
        'mode_of_collection' => 'required|in:Cash,UPI,Other',
        'total_security_amount' => 'required|numeric|min:0',
    ]);

    // Check if validation fails
    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    DB::beginTransaction();

    try {
        $authUser = Auth::user();

        // Check if user exists
        $user = User::find($request->user_id);
        if (!$user) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'User not found!')
                ->withInput();
        }

        // Get current financial year (FY)
        $currentYear = date('Y');
        $financialYear = $currentYear . '-' . ($currentYear + 1);
        $prefix = 'SEC/' . $financialYear . '/';
        
        // Get last transaction ID
        $lastTransaction = SecurityAmount::where('transaction_id', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        // Generate new number
        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_id, -5);
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }
        
        $transactionId = $prefix . $newNumber;

        // Create security amount
        $securityAmount = SecurityAmount::create([
            'transaction_id' => $transactionId,
            'date' => $request->date,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_security_amount,
            'mode_of_collection' => $request->mode_of_collection,
            'status' => $request->status ?? 1,
            'created_by' => $authUser->id,
        ]);

        User::where('id', $request->user_id)->update(['security_amount' => $request->total_security_amount]);

        DB::commit();

        return redirect()->back()
            ->with('success', 'Security amount added successfully! Transaction ID: ' . $transactionId);

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()
            ->with('error', 'Failed to add security amount: ' . $e->getMessage())
            ->withInput();
    }
}   

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $securityAmount = SecurityAmount::with(['user', 'creator'])->find($id);

            if (!$securityAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Security amount not found!'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $securityAmount
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security amount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'nullable|exists:transactions,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $securityAmount = SecurityAmount::find($id);

            if (!$securityAmount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Security amount not found!'
                ], 404);
            }

            $securityAmount->update([
                'transaction_id' => $request->transaction_id ?? $securityAmount->transaction_id,
                'date' => $request->date,
                'amount' => $request->amount,
                'status' => $request->status ?? $securityAmount->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Security amount updated successfully!',
                'data' => $securityAmount->load(['user', 'creator'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update security amount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $securityAmount = SecurityAmount::find($id);

            if (!$securityAmount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Security amount not found!'
                ], 404);
            }

            $securityAmount->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Security amount deleted successfully!'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete security amount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total security amount for a user.
     */
    public function getUserTotal($userId)
    {
        try {
            $latest = SecurityAmount::getLatestSecurityByUser($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'total_security_amount' => $latest,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user total: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of security amount.
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();

        try {
            $securityAmount = SecurityAmount::find($id);

            if (!$securityAmount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Security amount not found!'
                ], 404);
            }

            $securityAmount->status = !$securityAmount->status;
            $securityAmount->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status toggled successfully!',
                'data' => $securityAmount
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total security amount summary.
     */
    public function getSummary(Request $request)
    {
        try {
            $query = SecurityAmount::query();

            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $summary = [
                'total_amount' => $query->sum('amount'),
                'total_active' => $query->where('status', 1)->sum('amount'),
                'total_inactive' => $query->where('status', 0)->sum('amount'),
                'total_records' => $query->count(),
                'active_records' => $query->where('status', 1)->count(),
                'inactive_records' => $query->where('status', 0)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary: ' . $e->getMessage()
            ], 500);
        }
    }
}
