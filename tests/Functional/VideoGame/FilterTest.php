<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Doctrine\DataFixtures\VideoGameFixtures;
use App\Model\Entity\Tag;
use App\Tests\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    /**
     * @dataProvider tagsData
     */
    public function testShouldFilterVideoGamesByTag(array $tagNames): void
    {
        $crawler = $this->get('/');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Filtrer')->form();

        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tag = $this->getEntityManager()->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
            self::assertNotNull($tag, sprintf('Le tag "%s" n\'existe pas en base.', $tagName));
            $tagIds[] = (string) $tag->getId();
        }

        foreach ($form['filter']['tags'] as $checkbox) {
            if (in_array($checkbox->availableOptionValues()[0], $tagIds, true)) {
                $checkbox->tick();
            }
        }

        $crawler = $this->client->submit($form);
        self::assertResponseIsSuccessful();

        $cards = $crawler->filter('.game-card');
        self::assertGreaterThan(0, $cards->count(), 'Aucun jeu affiché après filtrage.');

        $cards->each(function ($card) use ($tagNames) {
            $displayedTags = $card->filter('span.tag')->each(
                static fn($span) => trim($span->text())
            );

            foreach ($tagNames as $expectedTag) {
                self::assertContains(
                    $expectedTag,
                    $displayedTags,
                    sprintf('Un jeu affiché ne possède pas le tag "%s".', $expectedTag)
                );
            }
        });
    }


    public function tagsData()
    {
        yield 'No tag' => [[]];
        yield 'One tag' => [[VideoGameFixtures::TAGS['action']]];
        yield 'Two tags' => [[VideoGameFixtures::TAGS['action']], VideoGameFixtures::TAGS['aventure']];
    }
}
