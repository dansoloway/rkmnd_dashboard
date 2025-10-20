<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackendApiService;
use Exception;

class TestBackendApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:backend-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to FastAPI backend and verify all endpoints work';

    protected $passedTests = 0;
    protected $totalTests = 0;
    protected $results = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║        FastAPI Backend Connection Test (via Laravel)          ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Display configuration
        $this->line('📍 Backend URL: ' . config('backend.api_url'));
        $this->line('🔑 API Key: ' . substr(config('backend.default_api_key'), 0, 10) . '...');
        $this->info('');

        // Create API service instance
        $api = new BackendApiService();

        $this->line('════════════════════════════════════════════════════════════════');
        $this->line('RUNNING TESTS');
        $this->line('════════════════════════════════════════════════════════════════');
        $this->info('');

        // Run tests
        $this->testHealthCheck($api);
        $this->testTenantInfo($api);
        $this->testWordPressStats($api);
        $this->testVideoList($api);
        $this->testVideoDetail($api);
        $this->testS3Info($api);
        $this->testSearch($api);

        // Display results
        $this->displayResults();

        return $this->passedTests === $this->totalTests ? 0 : 1;
    }

    protected function testEndpoint($name, $callback)
    {
        $this->totalTests++;
        $this->line("Testing: {$name}... ", false);

        try {
            $startTime = microtime(true);
            $result = $callback();
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ PASSED ({$duration}ms)");
            $this->passedTests++;
            $this->results[$name] = [
                'status' => 'passed',
                'duration' => $duration,
                'data' => $result
            ];

            return $result;

        } catch (Exception $e) {
            $this->error("❌ FAILED");
            $this->error("   Error: " . $e->getMessage());
            $this->results[$name] = [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];

            return null;
        }
    }

    protected function testHealthCheck($api)
    {
        $result = $this->testEndpoint('Health Check', function() use ($api) {
            return $api->healthCheck();
        });

        if ($result) {
            $this->line("   Status: {$result['status']}");
            $this->line("   Service: {$result['service']}");
            $this->info('');
        }
    }

    protected function testTenantInfo($api)
    {
        $result = $this->testEndpoint('Tenant Info', function() use ($api) {
            return $api->getTenantInfo();
        });

        if ($result) {
            $this->line("   Tenant: {$result['name']} ({$result['display_name']})");
            $this->line("   Plan: {$result['plan_type']}");
            $this->info('');
        }
    }

    protected function testWordPressStats($api)
    {
        $result = $this->testEndpoint('WordPress Stats', function() use ($api) {
            return $api->getWordPressStats();
        });

        if ($result) {
            $this->line("   Total Videos: {$result['total_videos']}");
            $this->line("   With Embeddings: {$result['videos_with_embeddings']}");
            $this->line("   With Audio: {$result['videos_with_audio_previews']}");
            $this->info('');
        }
    }

    protected function testVideoList($api)
    {
        $result = $this->testEndpoint('Video List', function() use ($api) {
            return $api->getVideos(['limit' => 10]);
        });

        if ($result && isset($result['videos'])) {
            $count = count($result['videos']);
            $this->line("   Retrieved: {$count} videos");
            
            if ($count > 0) {
                $first = $result['videos'][0];
                $this->line("   First Video: {$first['title']}");
            }
            
            $this->info('');
        }
    }

    protected function testVideoDetail($api)
    {
        // First get a video ID
        try {
            $videos = $api->getVideos(['limit' => 1]);
            if (isset($videos['videos'][0])) {
                $videoId = $videos['videos'][0]['id'];
                
                $result = $this->testEndpoint('Video Detail', function() use ($api, $videoId) {
                    return $api->getVideoById($videoId);
                });

                if ($result) {
                    $this->line("   Title: {$result['title']}");
                    $this->line("   Instructor: {$result['instructor']}");
                    $hasAudio = isset($result['has_audio_preview']) && $result['has_audio_preview'] ? 'Yes' : 'No';
                    $this->line("   Has Audio: {$hasAudio}");
                    $this->info('');
                }
            }
        } catch (Exception $e) {
            $this->error("Skipping video detail test: " . $e->getMessage());
        }
    }

    protected function testS3Info($api)
    {
        $result = $this->testEndpoint('S3 Storage Info', function() use ($api) {
            return $api->getS3Info();
        });

        if ($result) {
            $this->line("   Bucket: {$result['bucket']}");
            $this->line("   Total Files: {$result['total_files']}");
            $this->info('');
        }
    }

    protected function testSearch($api)
    {
        $result = $this->testEndpoint('Semantic Search', function() use ($api) {
            return $api->searchVideos('yoga stretching', 5);
        });

        if ($result && isset($result['results'])) {
            $count = count($result['results']);
            $this->line("   Results: {$count} videos found");
            $this->info('');
        }
    }

    protected function displayResults()
    {
        $this->line('════════════════════════════════════════════════════════════════');
        $this->line('RESULTS SUMMARY');
        $this->line('════════════════════════════════════════════════════════════════');
        $this->info('');

        $failed = $this->totalTests - $this->passedTests;
        $successRate = round(($this->passedTests / $this->totalTests) * 100, 1);

        $this->line("Total Tests: {$this->totalTests}");
        $this->line("Passed: {$this->passedTests} ✅");
        $this->line("Failed: {$failed} ❌");
        $this->line("Success Rate: {$successRate}%");
        $this->info('');

        if ($this->passedTests === $this->totalTests) {
            $this->info('╔════════════════════════════════════════════════════════════════╗');
            $this->info('║  🎉 ALL TESTS PASSED! Laravel can connect to FastAPI backend  ║');
            $this->info('╚════════════════════════════════════════════════════════════════╝');
            $this->info('');
            
            $this->line('✅ Next Steps:');
            $this->line('   1. Your Laravel dashboard can successfully communicate with FastAPI');
            $this->line('   2. You can now build the dashboard UI using BackendApiService');
            $this->line('   3. Test the actual dashboard pages in a browser');
            $this->info('');
        } else {
            $this->error('╔════════════════════════════════════════════════════════════════╗');
            $this->error('║  ⚠️  SOME TESTS FAILED - Check configuration                   ║');
            $this->error('╚════════════════════════════════════════════════════════════════╝');
            $this->info('');
            
            $this->line('🔧 Troubleshooting:');
            $this->line('   1. Verify BACKEND_API_URL in .env: ' . config('backend.api_url'));
            $this->line('   2. Verify TENANT_DEFAULT_API_KEY in .env');
            $this->line('   3. Ensure FastAPI server is running and accessible');
            $this->line('   4. Check firewall rules if on different servers');
            $this->info('');
        }
    }
}
