<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class FailedJobController extends Controller
{
    /**
     * List all failed jobs.
     */
    public function index()
    {
        $jobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate(20);

        return response()->json($jobs);
    }

    /**
     * Retry a specific failed job.
     */
    public function retry($uuid)
    {
        $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        Artisan::call('queue:retry', ['id' => $uuid]);

        return response()->json(['message' => 'Job retry queued successfully']);
    }

    /**
     * Delete a specific failed job.
     */
    public function destroy($uuid)
    {
        $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        Artisan::call('queue:forget', ['id' => $uuid]);

        return response()->json(['message' => 'Job removed successfully']);
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll()
    {
        Artisan::call('queue:retry', ['id' => 'all']);

        return response()->json(['message' => 'All failed jobs queued for retry']);
    }
    
    /**
     * Delete all failed jobs.
     */
    public function flush()
    {
        Artisan::call('queue:flush');

        return response()->json(['message' => 'All failed jobs flushed']);
    }
}
