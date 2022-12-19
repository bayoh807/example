<?php

namespace Tests;


use  Tests\CreatesApplication;
use Tests\TestCase;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\OrderProcessor;

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
     * test setBiller of OrderProcessor
     * @test
     * @return void
     */
    public function setBiller(): void
    {
        //arrange
        $service = $this->initMock(BillerService::class);
                
        //act
        $this->orderProcessor->setBiller($service);

        //assert
        $this->assertTrue($this->orderProcessor->setBiller($this->service));
    }


}
