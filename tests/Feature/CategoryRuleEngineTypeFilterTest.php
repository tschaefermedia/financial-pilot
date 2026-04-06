<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Services\Categorization\CategoryRuleEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRuleEngineTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    private CategoryRuleEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CategoryRuleEngine;
    }

    public function test_expense_category_not_suggested_for_positive_amount(): void
    {
        $category = Category::create(['name' => 'Lebensmittel', 'type' => 'expense']);
        CategoryRule::create([
            'pattern' => 'rewe',
            'is_regex' => false,
            'target_category_id' => $category->id,
            'priority' => 0,
            'confidence' => 0.8,
            'hit_count' => 5,
        ]);

        $result = $this->engine->categorize('REWE Markt', null, 500.00);

        $this->assertNull($result->categoryId);
    }

    public function test_expense_category_matches_negative_amount(): void
    {
        $category = Category::create(['name' => 'Lebensmittel', 'type' => 'expense']);
        CategoryRule::create([
            'pattern' => 'rewe',
            'is_regex' => false,
            'target_category_id' => $category->id,
            'priority' => 0,
            'confidence' => 0.8,
            'hit_count' => 5,
        ]);

        $result = $this->engine->categorize('REWE Markt', null, -45.50);

        $this->assertEquals($category->id, $result->categoryId);
    }

    public function test_income_category_not_suggested_for_negative_amount(): void
    {
        $category = Category::create(['name' => 'Gehalt', 'type' => 'income']);
        CategoryRule::create([
            'pattern' => 'gehalt',
            'is_regex' => false,
            'target_category_id' => $category->id,
            'priority' => 0,
            'confidence' => 0.9,
            'hit_count' => 10,
        ]);

        $result = $this->engine->categorize('Gehalt März', null, -100.00);

        $this->assertNull($result->categoryId);
    }

    public function test_income_category_matches_positive_amount(): void
    {
        $category = Category::create(['name' => 'Gehalt', 'type' => 'income']);
        CategoryRule::create([
            'pattern' => 'gehalt',
            'is_regex' => false,
            'target_category_id' => $category->id,
            'priority' => 0,
            'confidence' => 0.9,
            'hit_count' => 10,
        ]);

        $result = $this->engine->categorize('Gehalt März', null, 3000.00);

        $this->assertEquals($category->id, $result->categoryId);
    }

    public function test_transfer_category_matches_both_positive_and_negative(): void
    {
        $category = Category::create(['name' => 'Umbuchung', 'type' => 'transfer']);
        CategoryRule::create([
            'pattern' => 'umbuchung',
            'is_regex' => false,
            'target_category_id' => $category->id,
            'priority' => 0,
            'confidence' => 0.7,
            'hit_count' => 3,
        ]);

        $positive = $this->engine->categorize('Umbuchung Sparkasse', null, 200.00);
        $negative = $this->engine->categorize('Umbuchung Sparkasse', null, -200.00);

        $this->assertEquals($category->id, $positive->categoryId);
        $this->assertEquals($category->id, $negative->categoryId);
    }

    public function test_categorize_without_amount_skips_type_check(): void
    {
        $category = Category::create(['name' => 'Lebensmittel', 'type' => 'expense']);
        CategoryRule::create([
            'pattern' => 'rewe',
            'is_regex' => false,
            'target_category_id' => $category->id,
            'priority' => 0,
            'confidence' => 0.8,
            'hit_count' => 5,
        ]);

        $result = $this->engine->categorize('REWE Markt');

        $this->assertEquals($category->id, $result->categoryId);
    }

    public function test_bulk_categorize_respects_category_type(): void
    {
        $expenseCat = Category::create(['name' => 'Lebensmittel', 'type' => 'expense']);
        $incomeCat = Category::create(['name' => 'Gehalt', 'type' => 'income']);

        CategoryRule::create([
            'pattern' => 'rewe',
            'is_regex' => false,
            'target_category_id' => $expenseCat->id,
            'priority' => 0,
            'confidence' => 0.8,
            'hit_count' => 5,
        ]);
        CategoryRule::create([
            'pattern' => 'gehalt',
            'is_regex' => false,
            'target_category_id' => $incomeCat->id,
            'priority' => 0,
            'confidence' => 0.9,
            'hit_count' => 10,
        ]);

        $results = $this->engine->categorizeBulk([
            ['description' => 'REWE Markt', 'counterparty' => null, 'amount' => 100.00],   // positive + expense = no match
            ['description' => 'REWE Markt', 'counterparty' => null, 'amount' => -45.00],    // negative + expense = match
            ['description' => 'Gehalt März', 'counterparty' => null, 'amount' => -500.00],  // negative + income = no match
            ['description' => 'Gehalt März', 'counterparty' => null, 'amount' => 3000.00],  // positive + income = match
        ]);

        $this->assertNull($results[0]->categoryId);
        $this->assertEquals($expenseCat->id, $results[1]->categoryId);
        $this->assertNull($results[2]->categoryId);
        $this->assertEquals($incomeCat->id, $results[3]->categoryId);
    }
}
