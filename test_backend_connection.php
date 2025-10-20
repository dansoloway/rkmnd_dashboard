#!/usr/bin/env php
<?php
/**
 * Test Backend API Connection
 * Run this script to verify Laravel can communicate with FastAPI backend
 * 
 * Usage: php test_backend_connection.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration
$baseUrl = $_ENV['BACKEND_API_URL'] ?? 'https://fitform100.com';
$apiKey = $_ENV['TENANT_DEFAULT_API_KEY'] ?? '';
$timeout = 30;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        FastAPI Backend Connection Test                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "📍 Backend URL: {$baseUrl}\n";
echo "🔑 API Key: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . "\n";
echo "\n";

$results = [];
$totalTests = 0;
$passedTests = 0;

/**
 * Helper function to test an endpoint
 */
function testEndpoint($name, $method, $endpoint, $data = null, $expectJson = true) {
    global $baseUrl, $apiKey, $timeout, $totalTests, $passedTests, $results;
    
    $totalTests++;
    $url = $baseUrl . $endpoint;
    
    echo "Testing: {$name}... ";
    
    try {
        $startTime = microtime(true);
        
        // Make request using cURL (since we're not in Laravel context)
        $ch = curl_init($url);
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($error) {
            echo "❌ FAILED\n";
            echo "   Error: {$error}\n\n";
            $results[$name] = ['status' => 'failed', 'error' => $error, 'duration' => $duration];
            return null;
        }
        
        if ($httpCode !== 200) {
            echo "❌ FAILED\n";
            echo "   HTTP {$httpCode}\n";
            echo "   Response: " . substr($response, 0, 200) . "\n\n";
            $results[$name] = ['status' => 'failed', 'http_code' => $httpCode, 'duration' => $duration];
            return null;
        }
        
        $json = $expectJson ? json_decode($response, true) : null;
        
        if ($expectJson && json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ FAILED\n";
            echo "   Invalid JSON response\n\n";
            $results[$name] = ['status' => 'failed', 'error' => 'Invalid JSON', 'duration' => $duration];
            return null;
        }
        
        echo "✅ PASSED ({$duration}ms)\n";
        $passedTests++;
        $results[$name] = ['status' => 'passed', 'duration' => $duration, 'data' => $json];
        return $json;
        
    } catch (Exception $e) {
        echo "❌ FAILED\n";
        echo "   Exception: " . $e->getMessage() . "\n\n";
        $results[$name] = ['status' => 'failed', 'error' => $e->getMessage()];
        return null;
    }
}

// ============================================================================
// Run Tests
// ============================================================================

echo "════════════════════════════════════════════════════════════════\n";
echo "RUNNING TESTS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Test 1: Health Check
$health = testEndpoint('Health Check', 'GET', '/health/detailed', null, true);
if ($health) {
    echo "   Status: {$health['status']}\n";
    echo "   Service: {$health['service']}\n\n";
}

// Test 2: Tenant Info
$tenant = testEndpoint('Tenant Info', 'GET', '/api/v1/tenant/info', null, true);
if ($tenant) {
    echo "   Tenant: {$tenant['name']} ({$tenant['display_name']})\n";
    echo "   Plan: {$tenant['plan_type']}\n\n";
}

// Test 3: WordPress Stats
$stats = testEndpoint('WordPress Stats', 'GET', '/api/v1/wordpress/stats', null, true);
if ($stats) {
    echo "   Total Videos: {$stats['total_videos']}\n";
    echo "   With Embeddings: {$stats['videos_with_embeddings']}\n";
    echo "   With Audio: {$stats['videos_with_audio_previews']}\n\n";
}

// Test 4: Video List (first 10)
$videos = testEndpoint('Video List', 'GET', '/api/v1/wordpress/videos?limit=10', null, true);
if ($videos && isset($videos['videos'])) {
    echo "   Retrieved: " . count($videos['videos']) . " videos\n";
    if (count($videos['videos']) > 0) {
        $first = $videos['videos'][0];
        echo "   First Video: {$first['title']}\n";
    }
    echo "\n";
}

// Test 5: Single Video Details
if ($videos && isset($videos['videos'][0])) {
    $videoId = $videos['videos'][0]['id'];
    $video = testEndpoint('Video Detail', 'GET', "/api/v1/wordpress/videos/{$videoId}", null, true);
    if ($video) {
        echo "   Title: {$video['title']}\n";
        echo "   Instructor: {$video['instructor']}\n";
        echo "   Has Audio: " . ($video['has_audio_preview'] ? 'Yes' : 'No') . "\n\n";
    }
}

// Test 6: Tenant Analytics
$analytics = testEndpoint('Tenant Analytics', 'GET', '/api/v1/tenant/analytics?days=30', null, true);
if ($analytics) {
    echo "   Period: Last 30 days\n\n";
}

// Test 7: S3 Info
$s3 = testEndpoint('S3 Storage Info', 'GET', '/api/v1/s3/info', null, true);
if ($s3) {
    echo "   Bucket: {$s3['bucket']}\n";
    echo "   Total Files: {$s3['total_files']}\n\n";
}

// Test 8: Search (if any videos exist)
if ($videos && isset($videos['videos'][0])) {
    $search = testEndpoint('Semantic Search', 'POST', '/api/v1/search/semantic', [
        'query' => 'yoga stretching',
        'limit' => 5
    ], true);
    if ($search && isset($search['results'])) {
        echo "   Results: " . count($search['results']) . " videos found\n\n";
    }
}

// ============================================================================
// Results Summary
// ============================================================================

echo "════════════════════════════════════════════════════════════════\n";
echo "RESULTS SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests} ✅\n";
echo "Failed: " . ($totalTests - $passedTests) . " ❌\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

if ($passedTests === $totalTests) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL TESTS PASSED! Laravel can connect to FastAPI backend  ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✅ Next Steps:\n";
    echo "   1. Your Laravel dashboard can successfully communicate with FastAPI\n";
    echo "   2. You can now build the dashboard UI using BackendApiService\n";
    echo "   3. Test the actual dashboard pages in a browser\n\n";
    
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️  SOME TESTS FAILED - Check configuration                   ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "🔧 Troubleshooting:\n";
    echo "   1. Verify BACKEND_API_URL in .env: {$baseUrl}\n";
    echo "   2. Verify TENANT_DEFAULT_API_KEY in .env\n";
    echo "   3. Ensure FastAPI server is running and accessible\n";
    echo "   4. Check firewall rules if on different servers\n\n";
    
    exit(1);
}

