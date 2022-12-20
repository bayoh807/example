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


    /**
     * 檢查五分鐘內訂單數
     * @test
     * @return void
     */
    public function getRecentOrderCount() : void 
    {
        //arrange
        $member_data = [
            'account' => $this->faker->mail
        ];
        $order_data = [
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(1)
            ],
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(6)
            ],
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(10)
            ]
        ];
        $timestamp = Carbon::now()->subMinutes(5);

        //act
        $member = Member::create($member_data);
        $member->toOrder()->createMany($order_data);

        $order = $member->toOrder()->firstWhere('created_at', $timestamp);
        
        $count = $this->orderProcessor->getRecentOrderCount($order);

        //assert
        $this->assertModelExists($member);
        $this->assertDatabaseHas('members', [
            'account' => $member_data['account'],
        ]);
        $this->assertModelExists($order);
        $this->assertDatabaseCount('orders', count($order_data));
        $this->assertDatabaseHas('orders', [
            'order_no' => $order->order_no
        ]);

        $this->assertEquals(1, $count);

    }

    /**
     * 建立新訂單
     * @test
     * @return void
     */
    public function toCreateOrder() : void 
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
        
        $order = $this->orderProcessor->toCreateOrder($member->id,$order_data['amount']);

        //assert
        $this->assertModelExists($member);
        $this->assertDatabaseHas('members', [
            'account' => $member_data['account'],
        ]);
        $this->assertModelExists($order);
        $this->assertDatabaseCount('orders', count($order_data));
        $this->assertDatabaseHas('orders', [
            'order_no' => $order->order_no
        ]);

    }

    /**
     * 五分鐘內重複下訂
     * @test
     * @return void
     */
    public function process_duplicate() : void 
    {
        //arrange
        $member_data = [
            'account' => $this->faker->mail
        ];
        $order_data = [
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(1)
            ],
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(6)
            ],
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(10)
            ]
        ];
        $member = Member::create($member_data);
        $member->toOrders->createMany($order_data);
        $order = $member->toOrders()->first();
 

        //act

        $this->orderProcessor->process($this->service,$order,rand(500,1000));

        //assert
        $this->assertModelExists($member);
        $this->assertDatabaseHas('members', [
            'account' => $member_data['account'],
        ]);
        $this->assertDatabaseCount('orders', count($order_data));
        $this->fail('Duplicate order likely.');

    }

    /**
     * 
     * @test
     * @return void
     */
    public function process_success() : void 
    {
        //arrange
        $member_data = [
            'account' => $this->faker->mail
        ];
        $order_data = 
            [
                'order_no' => Str::uuid(),
                'amount' => rand(500, 1000),
                'created_at' =>  Carbon::now()->subMinutes(10)
            ];
        $member = Member::create($member_data);
        $oldOrder  = $member->toOrders->create($order_data); 

        //act
        $order  = $this->orderProcessor->process($this->service,$oldOrder,rand(500,1000));

        //assert
        $this->assertModelExists($member);
        $this->assertDatabaseHas('members', [
            'account' => $member_data['account'],
        ]);
        $this->assertModelExists($order);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseHas('orders', [
            'order_no' => $order->order_no
        ]);


    }
}
