<?php

namespace App\Http\Controllers;

use App\Models\OrderFeedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        return response()->json(OrderFeedback::create($data), 201);
    }

    public function index(Request $request)
    {
        return response()->json(OrderFeedback::latest()->paginate($request->input('per_page', 20)));
    }

    public function destroy($id)
    {
        $feedback = OrderFeedback::findOrFail($id);
        $feedback->delete();
        return response()->json(['message' => 'Feedback deleted.']);
    }
}

