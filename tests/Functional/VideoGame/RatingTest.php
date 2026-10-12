<?php

namespace App\Tests\Functional\VideoGame;

use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RatingTest extends FunctionalTestCase
{
    public function testShouldLetUserAddRating(): void
    {
        $this->login();
        $this->get('/jeu-video-21');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="review"]');
        $this->submit(
            'Poster',
            [
                'review[rating]' => 5,
                'review[comment]' => 'Commentaire',
            ]
        );
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->client->followRedirect();
        self::assertSelectorTextContains('div.list-group-item:last-child h3', 'user+0');
        self::assertSelectorTextContains('div.list-group-item:last-child p', 'Commentaire');
        self::assertSelectorTextContains('div.list-group-item:last-child span.value', '5');
        self::assertSelectorNotExists('form[name="review"]');
    }
}
