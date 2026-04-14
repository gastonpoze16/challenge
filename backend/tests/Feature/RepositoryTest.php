<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Payment;
use App\Models\PaymentEventType;
use App\Models\User;
use App\Repositories\Eloquent\EloquentEventLogRepository;
use App\Repositories\Eloquent\EloquentPaymentRepository;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    private EloquentPaymentRepository $paymentRepo;
    private EloquentEventLogRepository $eventLogRepo;
    private int $typeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
        $this->paymentRepo = new EloquentPaymentRepository;
        $this->eventLogRepo = new EloquentEventLogRepository;
        $this->typeId = PaymentEventType::where('code', 'payment.created')->value('id');
    }

    private function paymentData(array $overrides = []): array
    {
        return array_merge([
            'payment_id' => 'pay_repo',
            'payment_event_type_id' => $this->typeId,
            'amount' => 100,
            'currency' => 'USD',
            'user_id' => 1,
            'last_event_id' => 'evt_1',
        ], $overrides);
    }

    private function eventLogData(array $overrides = []): array
    {
        return array_merge([
            'event_id' => 'evt_repo',
            'payment_id' => 'pay_repo',
            'payment_event_type_id' => $this->typeId,
            'amount' => 100,
            'currency' => 'USD',
            'user_id' => 1,
            'timestamp' => now(),
            'received_at' => now(),
        ], $overrides);
    }

    public function test_payment_upsert_creates_new_record(): void
    {
        $payment = $this->paymentRepo->upsert($this->paymentData());

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', ['payment_id' => 'pay_repo']);
    }

    public function test_payment_upsert_updates_existing_record(): void
    {
        $this->paymentRepo->upsert($this->paymentData());
        $completedTypeId = PaymentEventType::where('code', 'payment.completed')->value('id');
        $this->paymentRepo->upsert($this->paymentData([
            'payment_event_type_id' => $completedTypeId,
            'last_event_id' => 'evt_2',
        ]));

        $this->assertCount(1, Payment::where('payment_id', 'pay_repo')->get());
        $this->assertEquals('evt_2', Payment::where('payment_id', 'pay_repo')->value('last_event_id'));
    }

    public function test_payment_find_by_payment_id(): void
    {
        $this->paymentRepo->upsert($this->paymentData(['currency' => 'EUR']));

        $this->assertNotNull($this->paymentRepo->findByPaymentId('pay_repo'));
        $this->assertEquals('EUR', $this->paymentRepo->findByPaymentId('pay_repo')->currency);
        $this->assertNull($this->paymentRepo->findByPaymentId('nonexistent'));
    }

    public function test_payment_list_paginates_and_filters(): void
    {
        $user = User::factory()->create();

        $this->paymentRepo->upsert($this->paymentData(['payment_id' => 'pay_p1', 'user_id' => $user->id]));
        $this->paymentRepo->upsert($this->paymentData(['payment_id' => 'pay_p2', 'currency' => 'EUR', 'user_id' => $user->id, 'last_event_id' => 'evt_2']));

        $this->assertEquals(2, $this->paymentRepo->list(10, 1, ['user_id' => $user->id])->total());
        $this->assertEquals(1, $this->paymentRepo->list(10, 1, ['user_id' => $user->id, 'currency' => 'EUR'])->total());
    }

    public function test_event_log_store(): void
    {
        $log = $this->eventLogRepo->store($this->eventLogData());

        $this->assertInstanceOf(EventLog::class, $log);
        $this->assertDatabaseHas('event_logs', ['event_id' => 'evt_repo']);
    }

    public function test_event_log_find_by_payment_id(): void
    {
        $this->eventLogRepo->store($this->eventLogData(['event_id' => 'evt_1']));
        $this->eventLogRepo->store($this->eventLogData(['event_id' => 'evt_2']));

        $this->assertCount(2, $this->eventLogRepo->findByPaymentId('pay_repo'));
    }

    public function test_event_log_exists_for_payment_and_event_id(): void
    {
        $this->eventLogRepo->store($this->eventLogData(['event_id' => 'evt_exists', 'payment_id' => 'pay_exists']));

        $this->assertTrue($this->eventLogRepo->existsForPaymentAndEventId('pay_exists', 'evt_exists'));
        $this->assertFalse($this->eventLogRepo->existsForPaymentAndEventId('pay_exists', 'evt_nope'));
        $this->assertFalse($this->eventLogRepo->existsForPaymentAndEventId('pay_nope', 'evt_exists'));
    }
}
