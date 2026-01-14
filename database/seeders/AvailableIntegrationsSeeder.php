<?php

namespace Database\Seeders;

use App\Models\AvailableIntegration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AvailableIntegrationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $integrations = [
            [
                'code' => 'gmail',
                'name' => 'Gmail',
                'category' => 'communication',
                'description' => 'Connect your Gmail account to send and receive project emails',
                'icon' => 'mail',
                'features' => ['Email sync', 'Automated responses', 'Thread tracking'],
                'is_active' => true,
            ],
            [
                'code' => 'slack',
                'name' => 'Slack',
                'category' => 'communication',
                'description' => 'Get real-time notifications in your Slack workspace',
                'icon' => 'message-square',
                'features' => ['Channel notifications', 'Direct messages', 'Status updates'],
                'is_active' => true,
            ],
            [
                'code' => 'google-drive',
                'name' => 'Google Drive',
                'category' => 'storage',
                'description' => 'Store and share project files with your team',
                'icon' => 'folder-kanban',
                'features' => ['File storage', 'Document collaboration', 'Automatic backups'],
                'is_active' => true,
            ],
            [
                'code' => 'dropbox',
                'name' => 'Dropbox',
                'category' => 'storage',
                'description' => 'Sync project files with Dropbox',
                'icon' => 'folder-kanban',
                'features' => ['File sync', 'Version history', 'Team folders'],
                'is_active' => true,
            ],
            [
                'code' => 'salesforce',
                'name' => 'Salesforce',
                'category' => 'crm',
                'description' => 'Connect your CRM for client management',
                'icon' => 'briefcase',
                'features' => ['Contact sync', 'Deal tracking', 'Activity logging'],
                'is_active' => true,
            ],
            [
                'code' => 'hubspot',
                'name' => 'HubSpot',
                'category' => 'crm',
                'description' => 'Sync contacts and deals from HubSpot',
                'icon' => 'briefcase',
                'features' => ['Contact management', 'Pipeline sync', 'Email integration'],
                'is_active' => true,
            ],
            [
                'code' => 'google-analytics',
                'name' => 'Google Analytics',
                'category' => 'analytics',
                'description' => 'Track project performance metrics',
                'icon' => 'trending-up',
                'features' => ['Usage analytics', 'Performance reports', 'Custom dashboards'],
                'is_active' => true,
            ],
            [
                'code' => 'zapier',
                'name' => 'Zapier',
                'category' => 'automation',
                'description' => 'Automate workflows with 1000+ apps',
                'icon' => 'bot',
                'features' => ['Custom workflows', 'App connections', 'Automated tasks'],
                'is_active' => true,
            ],
        ];

        foreach ($integrations as $integration) {
            AvailableIntegration::updateOrCreate(
                ['code' => $integration['code']],
                $integration
            );
        }
    }
}
