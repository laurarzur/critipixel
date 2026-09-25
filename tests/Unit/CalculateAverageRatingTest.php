<?php

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

class CalculateAverageRatingTest extends TestCase
{
    /**
     * @dataProvider videoGamesData
     */
    public function testCalculateAverageRating(VideoGame $videoGame, ?int $expectedAverageRating)
    {
        $ratingHandler = new RatingHandler();
        $ratingHandler->calculateAverage($videoGame);

        self::assertSame($expectedAverageRating, $videoGame->getAverageRating());
    }

    /**
     * @return iterable<array{VideoGame, ?int}>
     */
    public static function videoGamesData(): iterable
    {
        yield 'No review' => [new VideoGame(), null,];

        yield 'One review' => [self::createVideoGameWithRatings(2), 2,];

        yield 'A lot of reviews' => [
            self::createVideoGameWithRatings(1, 2, 2, 3, 4, 4, 4, 4, 5, 5),
            4,
        ];
    }

    public static function createVideoGameWithRatings(int ...$ratings): VideoGame
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rating) {
            $videoGame->getReviews()->add((new Review())->setRating($rating));
        }

        return $videoGame;
    }
}
