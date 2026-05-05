<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use Illuminate\Http\Request;

class DraftApiController extends Controller
{
    public function getDraft(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');
        $user = $request->user() ? $request->user()->id : 'admin'; // fallback for demo

        $draft = Draft::where('user_id', $user)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->first();

        return response()->json(['draft' => $draft]);
    }

    public function saveDraft(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'content' => 'required|array',
        ]);

        $user = $request->user() ? $request->user()->id : 'admin';
        
        $draft = Draft::updateOrCreate(
            [
                'user_id' => $user,
                'target_type' => $request->input('type'),
                'target_id' => $request->input('id'),
            ],
            [
                'content' => $request->input('content')
            ]
        );

        return response()->json(['message' => 'Draft saved', 'draft' => $draft]);
    }

    public function deleteDraft(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');
        $user = $request->user() ? $request->user()->id : 'admin';

        Draft::where('user_id', $user)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->delete();

        return response()->json(['message' => 'Draft deleted']);
    }
}
