<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller_Template {

    public $template = 'template'; // Default template

    public function before() {
        parent::before();
        $this->template->title = "Garduinoponics";

        $hStatus = new Helper_Status();

        $mDay = new Model_Day();
        $day = $mDay->getCurrentDay();
        if( ! $day ) {
            $date = new DateTime();
            $date->sub(new DateInterval('P1D'));

            $day = $mDay->getDayByDate($date->format('Y-m-d'));
        }
        $this->template->sun_status = $hStatus->getSunStatus($day);

        $mLive = new Model_Live();
        $last_communication = $mLive->getLastCommunication();
        $this->template->communication_status = $hStatus->getCommunicationStatus($last_communication);
    }

    public function after() {
        if( $this->auto_render ) {
            $styles = array(
                "assets/css/main.css" => "screen",
                "assets/css/normalize.css" => "screen"
            );
            $scripts  = array(
                "assets/js/plugins.js",
                "assets/js/main.js",
                "http://code.highcharts.com/modules/exporting.js",
                "http://code.highcharts.com/highcharts.js"
            );

            $this->template->styles = array_reverse(
                $styles // array_merge( $this->template->styles, $styles )
            );
            $this->template->scripts = array_reverse(
                $scripts // array_merge( $this->template->scripts, $scripts )
            );
        }
        parent::after();
    }

    public function action_index() {
        $mHour = new Model_Hour();
        $temparatureData = $mHour->getTemperatureData();
        $roomTemperature = array();
        $tankTemperature = array();
        foreach($temparatureData as $temp) {
            $datetime = strtotime($temp['datetime']. ' GMT') * 1000; //
            $roomTemperature[] = array( $datetime, floatval($temp['room_temperature']) );
            $tankTemperature[] = array( $datetime, floatval($temp['tank_temperature']) );
        }
        $mQuarterHour = new Model_QuarterHour();
        $sunlightData = $mQuarterHour->getSunlightData();
        $sunlight = array();
        foreach($sunlightData as $sun) {
            $datetime = strtotime($sun['datetime']. ' GMT') * 1000; //
            $sunlight[] = array( $datetime, intval($sun['sunlight']) * 100 / 1024 );
        }

        $view = View::factory( "index" )->set(array(
            'roomTemperatureData' => $roomTemperature,
            'tankTemperatureData' => $tankTemperature,
            'sunlightData' => $sunlight
        ));
        $this->template->content = $view->render();
    }

} // End Welcome
