<?php

namespace Tests;


use  Tests\CreatesApplication;
use Tests\TestCase;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\OrderProcessor;
use App\Models\Order;

class OrderUnitTest extends TestCase
{
    use CreatesApplication,RefreshDatabase;
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->orderProcessor = $this->initMock(OrderProcessor::class);
        $this->service = $this->initMock(BillerService::class);
    }

   

    /**
     * @test
     * @return void
     */
    public function hasRecentOrder_true(): void
    {
        //arrange
        $member_data = [
            'account' => $this->faker->mail
        ];
        $order_data = [
            'order_no' => Str::uuid(),
            'amount' => rand(500, 1000)
        ];

        //act
        $member = Member::create($member_data);
        $order = $member->toOrder()->create($order_data);

        $result = $this->orderProcessor->hasRecentOrder($order);

        //assert
        $this->assertModelExists($member);
        $this->assertModelExists($order);
        $this->assertDatabaseHas('members', [
            'account' => $member_data['account'],
        ]);
        $this->assertDatabaseHas('orders', [
            'order_no' => $order_data['order_no'],
            'amount' => $order_data['amount']
        ]);
        $this->assertTrue($result);
        $this->assertDatabaseCount('orders', 1);
    }


    /**
     * 檢查五分鐘內是否有訂單
     * @test
     * @return void
     */
    public function hasRecentOrder_false(): void
    {
        //arrange
        $member_data = [
            'account' => $this->faker->mail
        ];
        $order_data = [
            'order_no' => Str::uuid(),
            'amount' => rand(500, 1000),
            'created_at' =>  Carbon::now()->subMinutes(5)
        ];

        //act
        $member = Member::create($member_data);
        $order = $member->toOrder()->create($order_data);

        $result = $this->orderProcessor->hasRecentOrder($order);

        //assert
        $this->assertModelExists($member);
        $this->assertDatabaseHas('members', [
            'account' => $member_data['account'],
        ]);
        $this->assertModelExists($order);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'order_no' => $order_data['order_no'],
            'amount' => $order_data['amount']
        ]);

        $this->assertTrue($result);
    }
}
