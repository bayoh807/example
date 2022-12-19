<?php

namespace Tests;


use  Tests\CreatesApplication;
use PHPUnit\Framework\TestCase;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

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
    }

    /**
     * 測試取得全部課程清單
     * @test
     * @return void
     */
    public function 取得課程清單(): void
    {
        // Arrange

        $courses = Course::where('state',StateType::RUN())->orderBy('sort','asc')->get();

        $resource = CourseResource::collection($courses);

        // Act
        $response = $this->json(
            'get',
            route('frontend.courses.index'),
        );


        // Assert
        $this->featureSuccess($response,$resource ,$courses->count());

    }

    /**
     * 測試取得熱門課程清單
     * @test
     * @return void
     * @dataProvider getPathProvider
     */
    public function 取得熱門或非熱門課程清單($value): void
    {
        // Arrange

        $courses = Course::where('state',StateType::RUN())
                ->has('toHotCourse',HotEnum::IsHot,0);

        if($value == HotEnum::getKey(HotEnum::IsHot)) {
            $courses = $courses->whereHas('toHotCourse',function($q) {
                return $q->where('type',CourseType::Obligation);
            });
        }

        $resource = CourseResource::collection($courses->get());

        // Act
        $response = $this->json(
            'get',
            route('frontend.courses.index',[
                'courseType' => $value
            ])
        );


        // Assert
        $this->featureSuccess($response,$resource ,$courses->count());

    }

    public function 取得非熱門課程清單($value): void
    {
        // Arrange

        $courses = Course::where('state',StateType::RUN())
            ->has('toHotCourse',HotEnum::Normal,0);

        if($value == HotEnum::getKey(HotEnum::IsHot)) {
            $courses = $courses->whereHas('toHotCourse',function($q) {
                return $q->where('type',CourseType::Obligation);
            });
        }

        $resource = CourseResource::collection($courses->get());

        // Act
        $response = $this->json(
            'get',
            route('frontend.courses.index',[
                'courseType' => $value
            ])
        );


        // Assert
        $this->featureSuccess($response,$resource ,$courses->count());

    }

//    public function getPathProvider() : array{
//
//        return  [
//            ...array_chunk(HotEnum::getKeys(),1)
//        ];
//
//    }
}
