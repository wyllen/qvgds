<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\LaravelGame;
use App\Models\LaravelJoker;
use App\Models\LaravelSession;
use QVGDS\Game\Domain\Game;
use QVGDS\Game\Domain\GameId;
use QVGDS\Game\Domain\GamesRepository;
use QVGDS\Game\Domain\GameStatus;
use QVGDS\Game\Domain\Joker\AudienceHelp;
use QVGDS\Game\Domain\Joker\CallAFriend;
use QVGDS\Game\Domain\Joker\FiftyFifty;
use QVGDS\Game\Domain\Joker\Joker;
use QVGDS\Game\Domain\Joker\Jokers;
use QVGDS\Game\Domain\Joker\JokerStatus;
use QVGDS\Game\Domain\Joker\JokerType;
use QVGDS\Session\Domain\Session;
use Ramsey\Uuid\Uuid;

final class LaravelGamesRepository implements GamesRepository
{

    public function save(Game $game): void
    {
        LaravelGame::upsert(
            [
                "id" => $game->id()->get(),
                "laravel_session" => $game->session()->id()->get(),
                "player" => $game->player(),
                "step" => $game->step(),
                "status" => $game->status()->name,
            ],
            ["id"],
            ["laravel_session", "player", "step", "status",]
        );
        $this->upsertJokers($game->jokers()->all(), $game->id());
    }

    public function get(GameId $id): ?Game
    {
        $game = LaravelGame::find($id->get());
        if ($game === null) {
            return null;
        }
        return $this->toDomain($game);
    }

    /**
     * @param Joker[] $jokers
     */
    private function upsertJokers(array $jokers, GameId $gameId)
    {
        array_walk(
            $jokers,
            fn(Joker $joker) => LaravelJoker::upsert(
                [
                    "game" => $gameId->get(),
                    "type" => $joker->type(),
                    "status" => $joker->status(),
                ],
                ["game"],
                ["type", "status"]
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function list(): array
    {
        return LaravelGame::all()->map(fn(LaravelGame $laravelGame): Game => $this->toDomain($laravelGame))->toArray();
    }

    private function toDomain(LaravelGame $laravelGame): Game
    {
        return new Game(
            new GameId(Uuid::fromString($laravelGame->id)),
            $laravelGame->player,
            new Jokers(...$laravelGame->jokers()->get()->map(fn(LaravelJoker $joker) => $this->buildJoker($joker))->toArray()),
            $laravelGame->session()->get()->map(fn(LaravelSession $session): Session => LaravelSessionsRepository::toDomain($session))->first(),
            GameStatus::from($laravelGame->status),
            $laravelGame->step
        );
    }

    private function buildJoker(LaravelJoker $joker): Joker
    {
        return match ($joker->type) {
            JokerType::FIFTY_FIFTY->name => new FiftyFifty(JokerStatus::from($joker->status)),
            JokerType::AUDIENCE_HELP->name => new AudienceHelp(JokerStatus::from($joker->status)),
            JokerType::CALL_A_FRIEND->name => new CallAFriend(JokerStatus::from($joker->status)),
        };
    }
}
