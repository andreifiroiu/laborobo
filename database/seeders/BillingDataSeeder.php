<?php

namespace Database\Seeders;

use App\Models\BillingInfo;
use App\Models\Invoice;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillingDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * NOTE: This is for development/demo purposes only.
     * In production, billing data comes from payment processor.
     */
    public function run(): void
    {
        // Get all teams and seed billing data for each
        Team::all()->each(function ($team) {
            // Create billing info
            BillingInfo::updateOrCreate(
                ['team_id' => $team->id],
                [
                    'plan_name' => 'Pro',
                    'plan_price' => 49.00,
                    'billing_cycle' => 'monthly',
                    'billing_period_start' => now()->startOfMonth(),
                    'billing_period_end' => now()->endOfMonth(),
                    'next_billing_date' => now()->addMonth()->startOfMonth(),
                    'users_included' => 10,
                    'users_current' => $team->allUsers()->count(),
                    'projects_included' => 50,
                    'projects_current' => 12,
                    'storage_gb_included' => 100.00,
                    'storage_gb_current' => 23.45,
                    'ai_requests_included' => 10000,
                    'ai_requests_current' => 3247,
                    'payment_method' => 'card',
                    'card_brand' => 'Visa',
                    'card_last4' => '4242',
                    'card_expiry' => now()->addYears(2)->endOfMonth(),
                    'status' => 'active',
                    'trial_ends_at' => null,
                ]
            );

            // Create sample invoices
            $invoices = [
                [
                    'invoice_number' => 'INV-' . now()->format('Y') . '-001',
                    'invoice_date' => now()->subMonths(2)->startOfMonth(),
                    'due_date' => now()->subMonths(2)->addDays(15),
                    'amount' => 49.00,
                    'status' => 'paid',
                    'paid_at' => now()->subMonths(2)->addDays(3),
                    'description' => 'Pro Plan - Monthly Subscription',
                    'pdf_url' => '/invoices/sample-001.pdf',
                ],
                [
                    'invoice_number' => 'INV-' . now()->format('Y') . '-002',
                    'invoice_date' => now()->subMonth()->startOfMonth(),
                    'due_date' => now()->subMonth()->addDays(15),
                    'amount' => 49.00,
                    'status' => 'paid',
                    'paid_at' => now()->subMonth()->addDays(5),
                    'description' => 'Pro Plan - Monthly Subscription',
                    'pdf_url' => '/invoices/sample-002.pdf',
                ],
                [
                    'invoice_number' => 'INV-' . now()->format('Y') . '-003',
                    'invoice_date' => now()->startOfMonth(),
                    'due_date' => now()->addDays(15),
                    'amount' => 49.00,
                    'status' => 'pending',
                    'paid_at' => null,
                    'description' => 'Pro Plan - Monthly Subscription',
                    'pdf_url' => '/invoices/sample-003.pdf',
                ],
            ];

            foreach ($invoices as $invoiceData) {
                Invoice::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'invoice_number' => $invoiceData['invoice_number'],
                    ],
                    array_merge($invoiceData, ['team_id' => $team->id])
                );
            }
        });
    }
}
