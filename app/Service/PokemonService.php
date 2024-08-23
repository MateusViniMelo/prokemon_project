<?php

namespace App\Service;

use GuzzleHttp\Client;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\WaitGroup;
use Swoole\Coroutine\Channel;

class PokemonService
{
    protected $client;
    private $concurrencyLimit = 5;
    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://pokeapi.co/api/v2/']);
    }

    public function getRandomPokemons(): array
    {
        $waitGroup = new WaitGroup();
        $pokemonDetails = [];

       
        $response = $this->client->get('pokemon?limit=10000');
        $pokemonListData = $response->getBody()->getContents();
        $pokemonList = json_decode($pokemonListData, true);
        $pokemonNames = array_column($pokemonList['results'], 'name');

        
        $selectedPokemons = array_intersect_key($pokemonNames, array_flip(array_rand($pokemonNames, 5)));

        foreach ($selectedPokemons as $pokemonName) {
            $waitGroup->add(1);

            Coroutine::create(function () use ($waitGroup, $pokemonName, &$pokemonDetails) {
                $response = $this->client->get("pokemon/{$pokemonName}");
                $pokemonData = $response->getBody()->getContents();
                $pokemon = json_decode($pokemonData, true);

                $pokemonImage = $pokemon['sprites']['front_default'];
                $moves = $pokemon['moves'];

                $moveDetails = [];
                foreach ($moves as $move) {
                    $moveUrl = $move['move']['url'];
                    $moveResponse = $this->client->get($moveUrl);
                    $moveData = $moveResponse->getBody()->getContents();
                    $moveInfo = json_decode($moveData, true);
                    $effect = isset($moveInfo['effect_entries'][0]['effect']) ? $moveInfo['effect_entries'][0]['effect'] : 'No effect description';
                    $moveDetails[] = [
                        'name' => $moveInfo['name'],
                        'effect' => $effect,
                    ];
                }

                $pokemonDetails[] = [
                    'name' => $pokemonName,
                    'image' => $pokemonImage,
                    'moves' => $moveDetails,
                ];

                $waitGroup->done();
            });
        }

        
        $waitGroup->wait();

        return $pokemonDetails;
    }
}
