<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Transaction;
use App\Services\AI\FinancialSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSnapshotAnonymizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_names_are_anonymized_in_prompt_context(): void
    {
        Transaction::create([
            'date' => now()->format('Y-m-d'),
            'amount' => 3000,
            'description' => 'Gehalt',
        ]);
        Transaction::create([
            'date' => now()->format('Y-m-d'),
            'amount' => -500,
            'description' => 'Miete',
        ]);

        Loan::create([
            'name' => 'Christopher',
            'type' => 'informal',
            'principal' => 1000,
            'interest_rate' => 0,
            'start_date' => now()->subMonths(6),
            'direction' => 'owed_to_me',
        ]);
        Loan::create([
            'name' => 'Sparkasse Privatkredit',
            'type' => 'bank',
            'principal' => 5000,
            'interest_rate' => 3.5,
            'start_date' => now()->subYear(),
            'direction' => 'owed_by_me',
        ]);

        $snapshot = FinancialSnapshot::capture();
        $context = $snapshot->toPromptContext();

        $this->assertStringNotContainsString('Christopher', $context);
        $this->assertStringNotContainsString('Sparkasse', $context);
        $this->assertStringContainsString('Kredit A', $context);
        $this->assertStringContainsString('Kredit B', $context);
    }

    public function test_loan_names_are_anonymized_in_snapshot_data(): void
    {
        Loan::create([
            'name' => 'Sophie',
            'type' => 'informal',
            'principal' => 200,
            'interest_rate' => 0,
            'start_date' => now()->subMonths(3),
            'direction' => 'owed_by_me',
        ]);

        Transaction::create([
            'date' => now()->format('Y-m-d'),
            'amount' => 1000,
            'description' => 'Test',
        ]);

        $snapshot = FinancialSnapshot::capture();

        $this->assertNotEmpty($snapshot->loanSummary);
        $this->assertEquals('Kredit A', $snapshot->loanSummary['loans'][0]['name']);
    }
}
