<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Ajax extends Controller {

    public function before() {
        //if(!$this->request->is_ajax()) // && Request::$client_ip != '255.255.255.255'
        //    throw new HTTP_Exception_403;
        parent::before();
        header('Content-Type: application/json');
    }

    public function after() {
        parent::after();
    }

    public function action_chartLiveData() {
        $idInstance = $this->request->param('id');
        $mHour = new Model_Hour();
        $tempData = $mHour->getLastTemperatureData($idInstance);
        $tempDatetime = strtotime($tempData['datetime']) * 1000;

        echo json_encode(array(
            'roomTemperature' => array($tempDatetime, floatval($tempData['room_temperature'])),
            'tankTemperature' => array($tempDatetime, floatval($tempData['tank_temperature']))
        ));
    }

    public function action_getLiveData() {
        $idInstance = $this->request->param('id');
        $mInstance = new Model_Instance();
        $liveData = $mInstance->getLiveData($idInstance);

        if( $idInstance ) {
            echo json_encode( $this->prepareLiveData($idInstance, $liveData) );
        } else {
            $returnData = array();
            foreach($liveData as $instance) {
                $returnData[] = $this->prepareLiveData($instance['id_instance'], $instance);
            }
            echo json_encode( $returnData );
        }
    }

    private function prepareLiveData($idInstance, $instance) {
        $stillAliveStatus = $instance['still_alive'] ? "still-alive" : "dead";
        $pumpStatus = $instance['pump_on'] ? "on" : "off";
        $lightStatus = $instance['light_on'] ? "on" : "off";
        $fanStatus = $instance['fan_on'] ? "on" : "off";
        $heaterStatus = $instance['heater_on'] ? "on" : "off";

        return array(
            'idInstance' => $idInstance,
            'stillAliveStatus' => $stillAliveStatus,
            'lastCommunication' => $instance['last_communication'],
            'pumpStatus' => $pumpStatus,
            'lightStatus' => $lightStatus,
            'fanStatus' => $fanStatus,
            'heaterStatus' => $heaterStatus
        );
    }
    
    public function action_updateToDo() {
        $post = json_decode( file_get_contents('php://input') );
        if ( isset($post->id) ) {
            $id = Kohana::sanitize( $post->id );
            $done = Kohana::sanitize( $post->done );

            $mToDo = new Model_Todo();
            $mToDo->updateTodo($id, $done);

            echo json_encode( array('success' => true) );
        }
    }

    public function action_postData() {
        if ( isset($_POST['pass']) && isset($_POST['action']) ) {
            $idInstance = $this->getInstanceId(filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_SPECIAL_CHARS));
            $datetime = null;

            if ( isset($_POST['datetime']) ) {
                $datetime = filter_input(INPUT_POST, 'datetime', FILTER_SANITIZE_SPECIAL_CHARS);

    //            if( ! date('I', time()) ) {
    //                $datetime = new DateTime( gmdate("Y-m-d H:i:s", $datetime) );
    ////                $datetime = new DateTime( gmdate("Y-m-d H:i:s", $datetime) );
    //
    ////                  $mLog = new Model_Log();
    ////                 $mLog->log( "error", $idInstance . " => before : " . $datetime2->format("Y-m-d H:i:s") );
    ////
    //                $datetime->sub( new DateInterval('PT1H') );
    //                $datetime = $datetime->format("Y-m-d H:i:s");
    ////
    ////                 $mLog->log( "error", $idInstance . " => after : " . $datetime2->format("Y-m-d H:i:s") );
    //            } else {
    //                $datetime = gmdate("Y-m-d H:i:s", $datetime);
    //            }
                $datetime = gmdate("Y-m-d H:i:s", $datetime);
            }

            switch ($_POST['action']) {
                case 'still-alive':
                    // Do nothing here!
                    break;
                case 'heaterAndFanStatus':
                    $this->saveHeaterAndFanStatus($idInstance);
                    break;
                case 'temperature':
                    $this->saveTemperatureData($idInstance, $datetime);
                    break;
                case 'sunlight':
                    $this->saveSunlightData($idInstance, $datetime);
                    break;
                case 'lightState':
                    $this->saveLightState($idInstance);
                    break;
                case 'pumpState':
                    $this->savePumpState($idInstance);
                    break;
            }
            $this->stillAlive($idInstance);
        } else {
            throw new HTTP_Exception_403;
        }
    }

    private function getInstanceId($code) {
        $mInstance = new Model_Instance();
        return $mInstance->getInstanceId($code);
    }

    private function stillAlive($idInstance) {
        $mInstance = new Model_Instance();
        $mInstance->updateStillAlive($idInstance);
    }

    private function saveLightState($idInstance) {
        if (isset($_POST['lightState'])) {
            $mInstance = new Model_Instance();

            $lightState = filter_input(INPUT_POST, 'lightState', FILTER_SANITIZE_SPECIAL_CHARS);
            $mInstance->updateLightState($lightState, $idInstance);
        } else {
            throw new HTTP_Exception_403;
        }
    }

    private function savePumpState($idInstance) {
        if (isset($_POST['pumpState'])) {
            $mInstance = new Model_Instance();

            $pumpState = filter_input(INPUT_POST, 'pumpState', FILTER_SANITIZE_SPECIAL_CHARS);
            $mInstance->updatePumpState($pumpState, $idInstance);
        } else {
            throw new HTTP_Exception_403;
        }
    }

    private function saveHeaterAndFanStatus($idInstance) {
        if (isset($_POST['fanStatus']) && isset($_POST['heaterStatus'])) {
            $mInstance = new Model_Instance();

            $fanStatus = filter_input(INPUT_POST, 'fanStatus', FILTER_SANITIZE_SPECIAL_CHARS);
            $heaterStatus = filter_input(INPUT_POST, 'heaterStatus', FILTER_SANITIZE_SPECIAL_CHARS);
            $mInstance->updateFanAndHeaterStatus($fanStatus, $heaterStatus);
        } else {
            throw new HTTP_Exception_403;
        }
    }

    private function saveTemperatureData($idInstance, $datetime) {
        if (isset($_POST['roomTemperature']) && isset($_POST['tankTemperature'])) {
            $mHour = new Model_Hour();
            $idCurrentDay = $this->getCurrentDayId($idInstance);

            $humidity = '0.0';
            if( isset($_POST['humidity']) ) {
                $humidity = filter_input(INPUT_POST, 'humidity', FILTER_SANITIZE_SPECIAL_CHARS);
            }

            $roomTemperature = filter_input(INPUT_POST, 'roomTemperature', FILTER_SANITIZE_SPECIAL_CHARS);
            $tankTemperature = filter_input(INPUT_POST, 'tankTemperature', FILTER_SANITIZE_SPECIAL_CHARS);
            $mHour->insertHour($idInstance, $idCurrentDay, $datetime, $humidity, $roomTemperature, $tankTemperature);
        } else {
            throw new HTTP_Exception_403;
        }
    }

    private function saveSunlightData($idInstance, $datetime) {
        if (isset($_POST['sunlight'])) {
            $mQuarterHour = new Model_QuarterHour();
            $idCurrentDay = $this->getCurrentDayId($idInstance);

            $sunlight = filter_input(INPUT_POST, 'sunlight', FILTER_SANITIZE_SPECIAL_CHARS);
            $mQuarterHour->insertQuarterHour($idInstance, $idCurrentDay, $datetime, $sunlight);
        } else {
            throw new HTTP_Exception_403;
        }
    }

    private function getCurrentDayId($idInstance) {
        $model_day = new Model_Day();
        return $model_day->getCurrentDayId($idInstance);
    }
}
