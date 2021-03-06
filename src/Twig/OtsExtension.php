<?php

namespace App\Twig;

use Extension\AbstractExtension;
use Twig\TwigFilter;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bridge\Doctrine\RegistryInterface;

use \Twig_Function;
use \Twig_Filter;

class OtsExtension extends \Twig_Extension
{
    protected $doctrine;
    // Retrieve doctrine from the constructor
    //SELECT id,(t1.experience - expBefore) as expDiff FROM players t1 INNER JOIN (SELECT player_id, exp as expBefore FROM today_exp) t2 ON t1.id = t2.player_id ORDER BY expDiff DESC
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    public function getTop5()
    {

        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('level', 'level');

        //WHY getResult returns 1 element?
        //SELECT name,level,`date` from players WHERE id = t2.player_id (SELECT t2.player_id,level,`date` FROM (SELECT id,level,`date` FROM player_deaths WHERE player_id = {$result->getId()}) t1 INNER JOIN (SELECT kill_id, player_id FROM player_killers) t2 on t1.id = t2.kill_id
        $top5 = $this->doctrine->getManager()
            ->createNativeQuery("SELECT name, level FROM players WHERE group_id <= 3 AND id > 1 ORDER BY level DESC, experience DESC LIMIT 5", $rsm)
            ->getResult();


        return $top5;
    }


    ///FUNCTION FROM NICAW AAC EDITED BY GHOST ornot
    public function getServerStatus()
    {

        $cfg['status_update_interval'] = 5 * 60; //SECONDS
        $cfg['server_ip'] = '127.0.0.1';
        $cfg['server_port'] = 7171;
        $cfg['statusDir'] = 'status.xml';

        $socket = @fsockopen($cfg['server_ip'], 7172, $errorCode, $errorString, 0.3);
        if ( $socket == false )
        {
            return null;
        }
        fclose($socket);
        $a = function ($host = '127.0.0.1', $port = 7171) {
            // connects to server
            $errorCode;
            $errorString;
            $socket = @fsockopen($host, $port, $errorCode, $errorString, 0.3);
            //var_dump($errorString);
            $data = '';
            // if connected then checking statistics
            //echo '1function ';
            //var_dump($socket);
            if ( $socket )
            {//echo 'socket ';
                // sets 1 second timeout for reading and writing
                stream_set_timeout($socket, 1);

                // sends packet with request
                // 06 - length of packet, 255, 255 is the comamnd identifier, 'info' is a request
                fwrite($socket, chr(6) . chr(0) . chr(255) . chr(255) . 'info');

                // reads respond
                while (!feof($socket))
                {
                    $data .= fread($socket, 128);
                }

                // closing connection to current server
                fclose($socket);
                return $data;
            }
            //var_dump($data);

            return null;

        };

        $modtime = filemtime($cfg['statusDir']);

        $test = (time() - $modtime);
        //echo "{$test} > {$cfg['status_update_interval']} ";
        $info = '';
        if ( (time() - $modtime) > $cfg['status_update_interval'] )
        {
            $info = $a($cfg['server_ip'], $cfg['server_port']);
            //var_dump($info);
            if ( $info === null )
            {
                return null;
            }

            //echo 'ccccccccccccccccccccccc '.(time() - $modtime).'<br>'.$modtime.'<br>';
            //var_dump($info);
            if ( !empty($info) )
            {
                //echo 'file put ';
                file_put_contents($cfg['statusDir'], $info);
            }

        } else
        {
            $info = file_get_contents($cfg['statusDir']);
            //echo 'file_get_contents ';
        }
        $infooo = file_get_contents($cfg['statusDir']);

        if ( !empty($infooo) )
        {
            //echo 'aaaaaas';
            $infoXML = simplexml_load_string($infooo);

            $up = (int)$infoXML->serverinfo['uptime'];
            $online = (int)$infoXML->players['online'];
            $max = (int)$infoXML->players['max'];
            $ip = $infoXML->serverinfo['ip'];

            $h = floor($up / 3600);
            $up = $up - $h * 3600;
            $m = floor($up / 60);
            $up = $up - $m * 60;

            $h = substr(("0" . $h), -2);
            $m = substr(("0" . $m), -2);
            $array = [
                'uptime' => "{$h}:{$m}",
                'online' => "$online",
                'max' => $max,
                'ip' => $ip,
            ];

            return $array;
        }
        $array = [
            'uptime' => "NaN",
            'online' => "NaN",
            'max' => "NaN",
            'ip' => "NaS",
        ];

        return null;
    }


    public function getFunctions()
    {
        return [
            'getTop5' => new Twig_Function('getTop5', [$this, 'getTop5']),
            'getServerStatus' => new Twig_Function('getServerStatus', [$this, 'getServerStatus']),
            'getPowerGamers' => new Twig_Function('getPowerGamers', [$this, 'getPowerGamers']),
        ];
    }


//     FILTERS

    public function truncateHTML(string $html, int $length)
    {
        $truncatedText = substr($html, $length);
        $pos = strpos($truncatedText, ">");
        if ( $pos !== false )
        {
            $html = substr($html, 0, $length + $pos + 1);
        } else
        {
            $html = substr($html, 0, $length);
        }

        preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];

        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closedtags = $result[1];

        $len_opened = count($openedtags);

        if ( count($closedtags) == $len_opened )
        {
            return $html;
        }

        $openedtags = array_reverse($openedtags);
        for ($i = 0; $i < $len_opened; $i++)
        {
            if ( !in_array($openedtags[$i], $closedtags) )
            {
                $html .= '</' . $openedtags[$i] . '>';
            } else
            {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }


        return $html;
    }


    public function ucwords(string $string)
    {
        return ucwords($string);
    }


    public function getFilters()
    {

        return [
            'truncateHTML' => new Twig_Filter('truncateHTML', [$this, 'truncateHTML']),
            'ucwords' => new Twig_Filter('ucwords', [$this, 'ucwords']),
        ];


    }

}