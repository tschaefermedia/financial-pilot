<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_trends_page_loads(): void
    {
        $response = $this->get('/trends');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Trends/Index', false));
    }

    public function test_trends_page_returns_monthly_data(): void
    {
        Transaction::create(['date' => now()->format('Y-m-d'), 'amount' => 3000, 'description' => 'Gehalt']);
        Transaction::create(['date' => now()->format('Y-m-d'), 'amount' => -500, 'description' => 'Miete']);

        $response = $this->get('/trends');

        $response->assertInertia(fn ($page) => $page
            ->has('monthlyData', 1)
            ->has('categoryTrends')
            ->has('currentMonth')
        );
    }

    public function test_anomaly_detection(): void
    {
        $category = Category::create(['name' => 'Essen', 'type' => 'expense']);

        // 3 previous months: ~100 each
        for ($i = 1; $i <= 3; $i++) {
            Transaction::create([
                'date' => now()->subMonths($i)->format('Y-m-d'),
                'amount' => -100,
                'description' => 'Normal',
                'category_id' => $category->id,
            ]);
        }

        // Current month: 200 (> 130% of 100 average)
        Transaction::create([
            'date' => now()->format('Y-m-d'),
            'amount' => -200,
            'description' => 'Teuer',
            'category_id' => $category->id,
        ]);

        $response = $this->get('/trends');

        $response->assertInertia(fn ($page) => $page
            ->has('categoryTrends', 1)
            ->where('categoryTrends.0.isAnomaly', true)
        );
    }
}
