<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductImageController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'product_code' => 'required|string',
            'color_id' => 'nullable|string',
        ]);

        $file = $request->file('image');
        $code = strtoupper($request->input('product_code'));
        $colorId = $request->input('color_id');

        $fileName = $code;
        if ($colorId) {
            $fileName .= '-' . str_pad($colorId, 2, '0', STR_PAD_LEFT);
        }
        $fileName .= '.' . $file->getClientOriginalExtension();

        // Save to Laravel public path
        $laravelPath = public_path('static/images/products');
        if (!File::isDirectory($laravelPath)) {
            File::makeDirectory($laravelPath, 0755, true);
        }
        $file->move($laravelPath, $fileName);

        // Also copy to Next.js public path if it exists (for dev convenience)
        $nextPath = base_path('frontend/public/static/images/products');
        if (File::isDirectory($nextPath)) {
            File::copy($laravelPath . '/' . $fileName, $nextPath . '/' . $fileName);
        }

        return response()->json([
            'success' => true,
            'file_name' => $fileName,
            'url' => '/static/images/products/' . $fileName
        ]);
    }

    public function listByCode($code)
    {
        $code = strtoupper($code);
        $imagePath = public_path('static/images/products');
        
        if (!File::isDirectory($imagePath)) {
            return response()->json([]);
        }

        $files = File::glob($imagePath . '/' . $code . '*.jpg');
        $images = array_map(function ($f) {
            return [
                'name' => basename($f),
                'url' => '/static/images/products/' . basename($f)
            ];
        }, $files);

        return response()->json($images);
    }

    public function delete(Request $request)
    {
        $fileName = $request->input('file_name');
        if (!$fileName) return response()->json(['success' => false]);

        $laravelPath = public_path('static/images/products/' . $fileName);
        if (File::exists($laravelPath)) {
            File::delete($laravelPath);
        }

        $nextPath = base_path('frontend/public/static/images/products/' . $fileName);
        if (File::exists($nextPath)) {
            File::delete($nextPath);
        }

        return response()->json(['success' => true]);
    }
}
