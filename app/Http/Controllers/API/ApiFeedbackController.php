<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ApiFeedbackController extends Controller
{
    /**
     * Display a listing of feedbacks
     */

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated user.',
                'data' => [],
            ], 401);
        }

        // Allowed roles
        if (!in_array($user->role, ['Canteen Administrator', 'Member', 'Non Member'])) {
            return response()->json([
                'status' => false,
                'message' => 'Does not have permission to access feedback.',
                'data' => [],
            ], 403);
        }

        $query = Feedback::with([
            'location:id,name',
            'user:id,first_name'
        ])->where('location_id', $user->location_id);

        // Members can only see their own feedback
        if (in_array($user->role, ['Member', 'Non Member'])) {
            $query->where('created_by', $user->id);
        }

        $feedbacks = $query->latest('created_at')
            ->get()
            ->map(function ($feedback) {

                return [
                    'id'            => $feedback->id,
                    'location_id'   => $feedback->location_id,
                    'subject'       => $feedback->subject,
                    'description'   => $feedback->description,
                    'created_by'    => $feedback->created_by,

                    'user_name'     => $feedback->user?->first_name,
                    'location_name' => $feedback->location?->name,

                    'created_date'  => $feedback->created_at?->format('d-m-Y'),
                    'created_time'  => $feedback->created_at?->format('h:i A'),
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Feedback list fetched successfully!',
            'data' => $feedbacks
        ], 200);
    }



    /**
     * Store a newly created feedback
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback = Feedback::create([
            'location_id' => $request->location_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'created_by' =>  $user->id ?? null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feedback created successfully',
            'data' => $feedback
        ], 201);
    }

    /**
     * Display the specified feedback
     */
    public function show($id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $feedback
        ]);
    }

    /**
     * Update the specified feedback
     */
    public function update(Request $request, $id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:pending,in_progress,resolved,closed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback->update($request->only(['subject', 'description', 'status']));

        return response()->json([
            'status' => true,
            'message' => 'Feedback updated successfully',
            'data' => $feedback
        ]);
    }

    /**
     * Remove the specified feedback
     */
    public function destroy($id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        $feedback->delete();

        return response()->json([
            'status' => true,
            'message' => 'Feedback deleted successfully'
        ]);
    }

    /**
     * Update feedback status
     */
    public function updateStatus(Request $request, $id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,resolved,closed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback->update(['status' => $request->status]);

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully',
            'data' => $feedback
        ]);
    }

    /**
     * Get feedback statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Feedback::count(),
            'pending' => Feedback::pending()->count(),
            'in_progress' => Feedback::status('in_progress')->count(),
            'resolved' => Feedback::status('resolved')->count(),
            'closed' => Feedback::status('closed')->count()
        ];

        return response()->json([
            'status' => true,
            'data' => $stats
        ]);
    }
}
