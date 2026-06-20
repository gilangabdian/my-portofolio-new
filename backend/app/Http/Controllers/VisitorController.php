<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class VisitorController extends Controller
{
    /**
     * Store a newly created resource in storage it doesn't already exist.
     */
    public function store(Request $request)
    {
        // No request validation as per user request
        if (!$request->device_id) {
            return response()->json(['message' => 'device_id is required'], 400);
        }

        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        if ($agent->isRobot()) {
            return response()->json(['message' => 'Bots are not logged.'], 200);
        }

        $existingVisitor = Visitor::where('device_id', $request->device_id)->first();
        
        $locationData = [
            'ip_address' => null,
            'city'       => null,
            'region'     => null,
            'country'    => null,
            'isp'        => null,
        ];

        // Fetch location only if new visitor
        if (!$existingVisitor) {
            $ip = $request->ip();
            if ($ip !== '127.0.0.1' && $ip !== '::1') {
                try {
                    $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp");
                    if ($response->successful() && $response->json('status') === 'success') {
                        $locationData['ip_address'] = $ip;
                        $locationData['city']       = $response->json('city');
                        $locationData['region']     = $response->json('regionName');
                        $locationData['country']    = $response->json('country');
                        $locationData['isp']        = $response->json('isp');
                    }
                } catch (\Exception $e) {
                    // Ignore API failure silently to not block tracking
                }
            }
        }

        $deviceType = 'desktop';
        if ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        }

        $updateData = [
            'device_type' => $deviceType,
            'os'          => $agent->platform() ?: null,
            'browser'     => $agent->browser() ?: null,
            'device_name' => $agent->device() ?: null,
        ];

        if (!$existingVisitor) {
            $updateData = array_merge($updateData, $locationData);
        }

        $visitor = Visitor::updateOrCreate(
            ['device_id' => $request->device_id],
            $updateData
        );

        return response()->json([
            'message' => 'Visitor logged successfully.',
            'data' => $visitor
        ], 201);
    }

    /**
     * Get all visitors (Admin Only)
     */
    public function index()
    {
        $visitors = Visitor::orderBy('updated_at', 'desc')->paginate(10);
        return response()->json($visitors);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Visitor $visitor)
    {
        $visitor->delete();
        return response()->json(['message' => 'Visitor record deleted successfully']);
    }

    /**
     * Remove all resources from storage.
     */
    public function clearAll()
    {
        Visitor::truncate();
        return response()->json(['message' => 'All visitor records cleared successfully']);
    }

    /**
     * Get the total count of visitors.
     */
    public function count()
    {
        $count = Visitor::count();

        return response()->json([
            'message' => 'Visitor count retrieved successfully',
            'data' => [
                'total_visitors' => $count
            ]
        ], 200);
    }
}
