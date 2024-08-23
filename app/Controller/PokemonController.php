<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PokemonService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use App\Service\UserService;
use Psr\Http\Message\ResponseInterface;


class PokemonController
{

    private $pokemonService;


    public function __construct(PokemonService $pokemonService)
    {
        $this->pokemonService = $pokemonService;
    }

    // 
    public function getPokemons()
    {

        return $this->pokemonService->getRandomPokemons();
    }
}
