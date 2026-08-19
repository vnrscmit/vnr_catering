<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class FeedbackController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    /**
     * Display a listing of feedbacks
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {

            $feedbacks = Feedback::with(['user', 'location']);

            // Role Wise Feedback Filter
            if ($user->role == 'Member' || $user->role == 'Non Member') {
                $feedbacks->where('created_by', $user->id);
            } elseif ($user->role == 'Canteen Administrator') {
                $feedbacks->where('location_id', $user->location_id);
            }

            return DataTables::of($feedbacks)
                ->addIndexColumn()

                ->addColumn('user_name', function ($row) {
                    return $row->user->first_name . ' - ' . ucfirst($row->user->role);
                })

                ->addColumn('location_name', function ($row) {
                    return $row->location->name ?? 'N/A';
                })

                ->editColumn('description', function ($row) {

                    return $row->description;
                })

                ->editColumn('status', function ($row) {

                    if ($row->status == 1) {
                        return '<span class="badge bg-primary">Active</span>';
                    }

                    return '<span class="badge bg-danger">Inactive</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('d-m-Y h:i A')
                        : 'N/A';
                })

                ->addColumn('action', function ($row) {

                    $delete = route('feedback.destroy', $row->id);

                    return '
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal"
                            data-id="' . $row->id . '">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('feedback.index');
    }

    /**
     * Show the form for creating a new feedback
     */
    public function create()
    {
        $user = Auth::user();
        if ($user->role !== 'Member' && $user->role !== 'Non Member') {
            return redirect()->back()->with(
                'error',
                'You do not have permission to add feedback.'
            );
        }
        return view('feedback.create');
    }

    /**
     * Store a newly created feedback
     */
    public function store(Request $request)
    {

        $user = Auth::user();

        if ($user->role !== 'Member' && $user->role !== 'Non Member') {
            return redirect()->back()->with(
                'error',
                'You do not have permission to delete bills.'
            );
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:255|min:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $feedback = Feedback::create([
            'subject' => $request->subject,
            'description' => $request->description,
            'created_by' => $user->id,
            'location_id' => $user->location_id,
        ]);

        return redirect()->route('feedback.index')
            ->with('success', 'Feedback created successfully!');
    }

    /**
     * Display the specified feedback
     */
    public function show($id)
    {
        $feedback = Feedback::with('user')->findOrFail($id);

        return view('feedback.show', compact('feedback'));
    }

    /**
     * Show the form for editing the specified feedback
     */
    public function edit($id)
    {
        $feedback = Feedback::findOrFail($id);

        return view('feedback.edit', compact('feedback'));
    }

    /**
     * Update the specified feedback
     */
    public function update(Request $request, $id)
    {
        $feedback = Feedback::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:10',
            'status' => 'sometimes|integer|in:0,1,2,3'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $feedback->update($request->only(['subject', 'description', 'status']));

        return redirect()->route('feedback.index')
            ->with('success', 'Feedback updated successfully!');
    }

    /**
     * Remove the specified feedback
     */
    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Feedback deleted successfully'
            ]);
        }

        return redirect()->route('feedback.index')
            ->with('success', 'Feedback deleted successfully!');
    }

    /**
     * Update feedback status
     */
    public function updateStatus(Request $request, $id)
    {
        $feedback = Feedback::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|in:0,1,2,3'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator);
        }

        $feedback->update(['status' => $request->status]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $feedback
            ]);
        }

        return redirect()->route('feedback.index')
            ->with('success', 'Status updated successfully!');
    }

    /**
     * Get feedback statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Feedback::count(),
            'pending' => Feedback::pending()->count(),
            'in_progress' => Feedback::inProgress()->count(),
            'resolved' => Feedback::resolved()->count(),
            'closed' => Feedback::closed()->count(),
            'by_subject' => Feedback::select('subject', \DB::raw('count(*) as total'))
                ->groupBy('subject')
                ->get(),
            'last_7_days' => Feedback::where('created_at', '>=', now()->subDays(7))
                ->count(),
            'labels' => [
                0 => 'Pending',
                1 => 'In Progress',
                2 => 'Resolved',
                3 => 'Closed'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get feedbacks by specific user
     */
    public function getUserFeedbacks($userId, Request $request)
    {
        $query = Feedback::where('created_by', $userId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $feedbacks = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $feedbacks
        ]);
    }

    /**
     * Export feedbacks to CSV
     */
    public function exportCSV(Request $request)
    {
        $query = Feedback::with('user');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $feedbacks = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="feedbacks_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($feedbacks) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Subject', 'Description', 'Status', 'Created By', 'Created At']);

            foreach ($feedbacks as $feedback) {
                $statusLabels = [
                    0 => 'Pending',
                    1 => 'In Progress',
                    2 => 'Resolved',
                    3 => 'Closed'
                ];

                fputcsv($file, [
                    $feedback->id,
                    $feedback->subject,
                    $feedback->description,
                    $statusLabels[$feedback->status] ?? 'Unknown',
                    $feedback->user->name ?? 'N/A',
                    $feedback->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export feedbacks to PDF
     */
    public function exportPDF(Request $request)
    {
        // You would need to install a PDF package like DomPDF
        // composer require barryvdh/laravel-dompdf

        $feedbacks = Feedback::with('user')->get();
        $pdf = \PDF::loadView('feedback.pdf', compact('feedbacks'));

        return $pdf->download('feedbacks_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Bulk delete feedbacks
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:feedbacks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        Feedback::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Selected feedbacks deleted successfully'
        ]);
    }

    /**
     * Bulk status update
     */
    public function bulkStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:feedbacks,id',
            'status' => 'required|integer|in:0,1,2,3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        Feedback::whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated for selected feedbacks'
        ]);
    }
}
