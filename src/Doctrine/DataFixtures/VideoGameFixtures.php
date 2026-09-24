<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_map;
use function array_rand;
use function array_walk;
use function count;
use function min;
use function random_int;
use function range;
use function sprintf;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var array<string, string> code => nom
     */
    private const TAGS = [
        'action' => 'Action',
        'aventure' => 'Aventure',
        'rpg' => 'RPG',
        'fps' => 'FPS',
        'plateforme' => 'Plateforme',
        'strategie' => 'Stratégie',
        'simulation' => 'Simulation',
        'sport' => 'Sport',
        'course' => 'Course',
        'combat' => 'Combat',
        'puzzle' => 'Puzzle',
        'horreur' => 'Horreur',
    ];

    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();

        $tags = [];
        foreach (self::TAGS as $code => $name) {
            $tag = (new Tag())
                ->setCode($code)
                ->setName($name);

            $manager->persist($tag);
            $tags[] = $tag;
        }

        $videoGames = array_map(
            fn(int $index): VideoGame => (new VideoGame())
                ->setTitle(sprintf('Jeu vidéo %d', $index))
                ->setDescription($this->faker->paragraphs(10, true))
                ->setReleaseDate(new DateTimeImmutable())
                ->setTest($this->faker->paragraphs(6, true))
                ->setRating(($index % 5) + 1)
                ->setImageName(sprintf('video_game_%d.png', $index))
                ->setImageSize(2_098_872),
            range(0, 49)
        );

        /** @var VideoGame $videoGame */
        foreach ($videoGames as $videoGame) {
            $tagIndexes = (array) array_rand($tags, random_int(1, 4));

            foreach ($tagIndexes as $tagIndex) {
                $videoGame->addTag($tags[$tagIndex]);
            }

            if ($users !== []) {
                $numberOfReviewers = min(count($users), random_int(5, 20));
                $userIndexes = (array) array_rand($users, $numberOfReviewers);

                foreach ($userIndexes as $userIndex) {
                    $review = (new Review())
                        ->setVideoGame($videoGame)
                        ->setUser($users[$userIndex])
                        ->setRating(random_int(1, 5))
                        ->setComment($this->faker->optional(0.7)->paragraph());

                    $manager->persist($review);
                    $videoGame->getReviews()->add($review);
                }
            }

            $this->calculateAverageRating->calculateAverage($videoGame);
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);
        }

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
