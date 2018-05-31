<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Players;
use App\Entity\Accounts;
use App\Entity\PlayerSkill;
use App\Entity\PlayerKiller;
use App\Entity\PlayerDeaths;
use Doctrine\ORM\Query\ResultSetMapping;

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
    public function player($name)
    {
        //, requirements={"page"="\d+"}

        $player = $this->getDoctrine()
            ->getRepository(Players::class)
        ->findOneBy([
            'name' => $name,
        ]);
        
        if ($player !== NULL){
            //Player Skills
            $playerSkills = $this->getDoctrine()
            ->getRepository(PlayerSkill::class)
            ->findBy([
                'player' => $player->getId(),
            ]);
            //Player Kills
            $playerPK = $this->getDoctrine()
            ->getRepository(PlayerKiller::class)
            ->findBy([
                'killer' => $player->getId(),
            ]);
            $player->kills = count($playerPK);

            //Player Guild in playeer.guild
            if ( $player->getRankId() > 0 ){
                $rsm = new ResultSetMapping;
                $rsm->addScalarResult('guild_name', 'guildName');
                $rsm->addScalarResult('guildId', 'guildId');
                $rsm->addScalarResult('rank_name', 'rankName');

                $player->guild = $this->getDoctrine()->getManager()
                    ->createNativeQuery("SELECT guild_name,rank_name,guildId FROM players t3 INNER JOIN ( SELECT t2.name as guild_name, t2.id as guildId, t1.name as rank_name, t1.id as rankID FROM guild_ranks t1 INNER JOIN (SELECT * FROM guilds) t2 ON t1.guild_id = t2.id) t4 ON t3.rank_id = t4.rankID WHERE id = {$player->getId()}", $rsm)
                ->getScalarResult();
                
            }else{
                $player->guild = "No Membership";
            }

            //Deaths by Player
            $rsm = new ResultSetMapping;
            $rsm->addScalarResult('names', 'names');
            $rsm->addScalarResult('levels', 'level');
            $rsm->addScalarResult('date', 'date');

            //WHY getResult returns 1 element?
            //SELECT name,level,`date` from players WHERE id = t2.player_id (SELECT t2.player_id,level,`date` FROM (SELECT id,level,`date` FROM player_deaths WHERE player_id = {$player->getId()}) t1 INNER JOIN (SELECT kill_id, player_id FROM player_killers) t2 on t1.id = t2.kill_id
            $playerKillers = $this->getDoctrine()->getManager()
                ->createNativeQuery("SELECT GROUP_CONCAT(name SEPARATOR ',') as names,date, `death_id`,levels FROM players t5 RIGHT JOIN (SELECT t3.player_id, level as levels, date, `death_id` FROM player_killers t3 INNER JOIN (SELECT * FROM player_deaths t1 INNER JOIN (SELECT `id` as `killer_id`, `death_id` FROM `killers`) t2 on t1.id = t2.death_id WHERE `player_id`={$player->getId()}) t4 on t3.kill_id = t4.killer_id ) t6 on t5.id = t6.player_id GROUP BY death_id", $rsm)
            ->getArrayResult();
            
            //Deaths by Monsters
            $rsm = new ResultSetMapping;
            $rsm->addScalarResult('killers_name', 'killers');
            $rsm->addScalarResult('level', 'level');
            $rsm->addScalarResult('date', 'date');

            $monsterKillers = $this->getDoctrine()->getManager()
                ->createNativeQuery("SELECT GROUP_CONCAT(name SEPARATOR ', ') as killers_name, level, date FROM environment_killers t3 INNER JOIN (SELECT * FROM player_deaths t1 INNER JOIN (SELECT `id` as `killer_id`, `death_id` FROM `killers`) t2 on t1.id = t2.death_id WHERE `player_id`={$player->getId()}) t4 on t3.kill_id = t4.killer_id GROUP BY death_id", $rsm)
            ->getResult();

        }
            

        return $this->render('players/player.html.twig', [
            'controller_name' => 'PlayersController',
            'player' => @$player,
            'playerSkills' => @$playerSkills,
            'playerPK' => @$playerPK,
            'playerKillers' => @$playerKillers,
            'monsterKillers' => @$monsterKillers,
        ]);
    }


    /**
     * @Route("/players/online", name="player_online")
     */
    public function playerOnline()
    {
        $onlines = $this->getDoctrine()
            ->getRepository(Players::class)
        ->findBy([
            'online' => 1,
        ]);

        return $this->render('players/online.html.twig', [
            'controller_name' => 'PlayersController',
            'onlines' => @$onlines,
        ]);
    }
}
