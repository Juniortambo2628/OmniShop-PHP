<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class MaintenanceController extends Controller
{
    /**
     * Run artisan commands via HTTP when terminal access is lost.
     * URL: /api/maintenance/run?key=OMNI_RECOVERY_2026&action=migrate
     */
    public function run(Request $request)
    {
        $key = $request->query('key');
        $action = $request->query('action', 'migrate');

        // Simple security check
        if ($key !== 'OMNI_RECOVERY_2026') {
            return response()->json(['message' => 'Unauthorized maintenance request.'], 403);
        }

        try {
            $output = '';
            
            switch ($action) {
                case 'migrate':
                    Artisan::call('migrate', ['--force' => true]);
                    $output = Artisan::output();
                    break;
                case 'seed':
                    Artisan::call('db:seed', ['--force' => true]);
                    $output = Artisan::output();
                    break;
                case 'clear':
                    Artisan::call('config:clear');
                    Artisan::call('cache:clear');
                    $output = "Caches cleared.";
                    break;
                case 'optimize':
                    Artisan::call('optimize');
                    $output = Artisan::output();
                    break;
                default:
                    return response()->json(['message' => 'Invalid action.'], 400);
            }

            return response()->json([
                'message' => "Action '$action' executed successfully.",
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error("Maintenance Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Maintenance failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
