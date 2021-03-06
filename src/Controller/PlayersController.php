<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Entity\Players;
use App\Entity\Accounts;
use App\Entity\PlayerSkill;
use App\Entity\PlayerKiller;
use App\Entity\PlayerDeaths;

use App\Utils\Strategy\StrategyClient;

use App\Utils\Configs;

class PlayersController extends Controller
{
    /**
     * @Route("/player", name="player_search")
     */
    public function playerSearch()
    {
        return $this->render('players/search.html.twig', [
            'controller_name' => 'PlayersController',
        ]);
    }


    /**
     * @Route("/player/{name}", name="player")
     */
    public function player($name, StrategyClient $strategy)
    {

        $player = $strategy->getPlayers()->getPlayerByName($name);

        return $this->render('players/player.html.twig', [
            'controller_name' => 'PlayersController',
            'player' => $player,
        ]);
    }


    /**
     * @Route("/players/online", name="player_online")
     */
    public function playerOnline(StrategyClient $strategy)
    {

        $onlines = $strategy->getPlayers()->getOnlinePlayers();

        return $this->render('players/online.html.twig', [
            'controller_name' => 'PlayersController',
            'onlines' => @$onlines,
        ]);
    }
}
