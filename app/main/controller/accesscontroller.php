<?php
/**
 * Created by PhpStorm.
 * User: exodus4d
 * Date: 09.02.15
 * Time: 23:30
 */

namespace Controller;


use Model\Pathfinder;

class AccessController extends Controller {

    /**
     * event handler
     * @param \Base $f3
     * @param $params
     * @return bool
     * @throws \Exception
     */
    function beforeroute(\Base $f3, $params) : bool {
        if($return = parent::beforeroute($f3, $params)){
            // Any route/endpoint of a child class of this one,
            // requires a valid logged in user!
            if($this->isLoggedIn($f3) !== 'OK'){
                // no character found or login timer expired
                $this->logoutCharacter($f3);
                // skip route handler and afterroute()
                $return = false;
            }
        }

        return $return;
    }

    /**
     * get current character and check if it is a valid character
     * @param \Base $f3
     * @return string
     * @throws \Exception
     */
    protected function isLoggedIn(\Base $f3) : string {
        $loginStatus = 'UNKNOWN';
        if($character = $this->getCharacter()){
            if($character->checkLoginTimer()){
                if(( $authStatus = $character->isAuthorized()) === 'OK'){
                    $loginStatus = 'OK';
                }else{
                    $loginStatus = $authStatus;
                }
            }else{
                $loginStatus = 'MAX LOGIN TIME EXCEEDED';
            }
        }else{
            $loginStatus = 'NO SESSION FOUND';
        }

        // log character access status in debug mode
        if(
            $loginStatus !== 'OK' &&
            $f3->get('DEBUG') === 3
        ){
            self::getLogger('CHARACTER_ACCESS')->write(
                sprintf(Pathfinder\CharacterModel::LOG_ACCESS,
                    $character->_id ,
                    $loginStatus,
                    $character->name
                )
            );
        }

        return $loginStatus;
    }

    /**
     * broadcast map data to clients
     * -> send over TCP Socket
     * @param Pathfinder\MapModel $map
     * @throws \Exception
     */
    protected function broadcastMapData(Pathfinder\MapModel $map) : void {
        $mapData = $this->getFormattedMapData($map);
        $this->getF3()->webSocket()->write('mapUpdate', $mapData);
    }

    /**
     * get formatted Map Data
     * @param Pathfinder\MapModel $map
     * @return array
     * @throws \Exception
     */
    protected function getFormattedMapData(Pathfinder\MapModel $map) : array {
        $mapData = $map->getData();

        return [
            'config' => $mapData->mapData,
            'data' => [
                'systems' => $mapData->systems,
                'connections' => $mapData->connections,
            ]
        ];
    }

}