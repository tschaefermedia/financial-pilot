<?php

namespace Tests\Feature;

use App\Models\RecurringTemplate;
use App\Models\ScheduledPayment;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_loads(): void
    {
        $response = $this->get('/calendar');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Calendar/Index', false));
    }

    public function test_recurring_templates_are_projected(): void
    {
        RecurringTemplate::create([
            'description' => 'Miete',
            'amount' => -800,
            'frequency' => 'monthly',
            'next_due_date' => now()->addDays(5)->format('Y-m-d'),
            'is_active' => true,
            'auto_generate' => false,
        ]);

        $response = $this->get('/calendar');

        $response->assertInertia(function ($page) {
            $events = $page->toArray()['props']['events'];
            $this->assertTrue(
                collect($events)->contains('description', 'Miete'),
                'Expected events to contain Miete'
            );
        });
    }

    public function test_scheduled_payment_crud(): void
    {
        // Create
        $response = $this->post('/calendar/payments', [
            'description' => 'Versicherung',
            'amount' => -120,
            'date' => now()->addDays(10)->format('Y-m-d'),
        ]);
        $response->assertRedirect();

        $payment = ScheduledPayment::first();
        $this->assertNotNull($payment);
        $this->assertEquals('Versicherung', $payment->description);

        // Update
        $this->put("/calendar/payments/{$payment->id}", [
            'description' => 'KFZ Versicherung',
            'amount' => -150,
            'date' => now()->addDays(10)->format('Y-m-d'),
        ])->assertRedirect();

        $payment->refresh();
        $this->assertEquals('KFZ Versicherung', $payment->description);

        // Delete
        $this->delete("/calendar/payments/{$payment->id}")->assertRedirect();
        $this->assertNull(ScheduledPayment::find($payment->id));
    }

    public function test_complete_creates_transaction(): void
    {
        $payment = ScheduledPayment::create([
            'description' => 'Zahnarzt',
            'amount' => -200,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->post("/calendar/payments/{$payment->id}/complete")->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->is_completed);

        $transaction = Transaction::where('description', 'Zahnarzt')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(-200, (float) $transaction->amount);
    }
}
