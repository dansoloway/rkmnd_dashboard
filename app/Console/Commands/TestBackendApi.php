<?php

namespace App\Console\Commands;

use App\Services\BackendApiService;
use Illuminate\Console\Command;

class TestBackendApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backend:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to the FastAPI backend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing Backend API Connection...');
        $this->newLine();

        $api = new BackendApiService();

        // Test 1: Health Check
        $this->info('1️⃣  Testing Health Check...');
        try {
            $health = $api->healthCheck();
            $this->line("   ✅ Status: {$health['status']}");
            $this->line("   📦 Database: " . ($health['database'] ?? 'N/A'));
            $this->line("   💾 Redis: " . ($health['redis'] ?? 'N/A'));
        } catch (\Exception $e) {
            $this->error('   ❌ Health check failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Test 2: Tenant Info
        $this->info('2️⃣  Testing Tenant Info...');
        try {
            $tenant = $api->getTenantInfo();
            $this->line("   ✅ Tenant: {$tenant['name']}");
            $this->line("   📛 Display Name: {$tenant['display_name']}");
            $this->line("   📋 Plan: {$tenant['plan_type']}");
            $this->line("   🟢 Active: " . ($tenant['is_active'] ? 'Yes' : 'No'));
        } catch (\Exception $e) {
            $this->error('   ❌ Tenant info failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Test 3: Video List
        $this->info('3️⃣  Testing Video List (first 5)...');
        try {
            $response = $api->getVideos(['limit' => 5]);
            $videos = $response['videos'] ?? [];
            $total = $response['total'] ?? 0;
            
            $this->line("   ✅ Total Videos: {$total}");
            
            if (count($videos) > 0) {
                $this->line("   📹 Sample videos:");
                foreach ($videos as $video) {
                    $this->line("      • {$video['title']} (ID: {$video['id']})");
                }
            } else {
                $this->warn("   ⚠️  No videos found (this may be normal for test accounts)");
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Video list endpoint not available: " . $e->getMessage());
        }

        $this->newLine();

        // Test 4: WordPress Stats
        $this->info('4️⃣  Testing WordPress Stats...');
        try {
            $stats = $api->getWordPressStats();
            $this->line("   ✅ Total Videos: " . ($stats['total_videos'] ?? 0));
            
            if (!empty($stats['categories'])) {
                $this->line("   📂 Categories: " . implode(', ', array_keys($stats['categories'])));
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Stats endpoint not available: " . $e->getMessage());
        }

        $this->newLine();

        // Test 5: Tenant Quota
        $this->info('5️⃣  Testing Tenant Quota...');
        try {
            $quota = $api->getTenantQuota();
            if (isset($quota['searches_used'])) {
                $this->line("   ✅ Search Queries: {$quota['searches_used']}/{$quota['searches_limit']}");
                $this->line("   ✅ Embeddings: {$quota['embeddings_used']}/{$quota['embeddings_limit']}");
            } else {
                $this->line("   ✅ Quota data received: " . json_encode($quota));
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Quota endpoint not available: " . $e->getMessage());
        }

        $this->newLine();

        // Test 6: S3 Info
        $this->info('6️⃣  Testing S3 Info...');
        try {
            $s3 = $api->getS3Info();
            $this->line("   ✅ Bucket: {$s3['bucket_name']}");
            $this->line("   📁 Total Files: " . ($s3['total_files'] ?? 'N/A'));
        } catch (\Exception $e) {
            $this->warn("   ⚠️  S3 info endpoint not available: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('🎉 Core tests passed! Backend API connection is working.');
        
        return 0;
    }
}
