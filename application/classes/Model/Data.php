<?php defined( 'SYSPATH' ) or die( 'No direct script access.' );

class Model_Data {
    /**
     * @deprecated
     *
     * @param $idInstance
     * @return mixed
     */
    public function getLastData($idInstance) {
        $query = DB::select(
            DB::expr($this->getExprDatetime().'  AS datetime'), 'data'
        )->from('data')->where('id_instance', '=', $idInstance)->order_by('timestamp', 'DESC')->limit(1)->offset(0);
        return $query->execute()->current();
    }

    /**
     * @deprecated
     *
     * @param $idInstance
     * @return mixed
     */
    public function getData($idInstance) {
        $query = DB::query(Database::SELECT,
            "SELECT " . $this->getExprDatetime() .'  AS datetime'.
            ", data FROM ( SELECT * FROM data WHERE id_instance = ". $idInstance ." ORDER BY timestamp DESC LIMIT 40 ) sub ORDER BY datetime ASC"
        );

        return $query->execute()->as_array();
    }

    public function getParamData($idParam, $limit) {
        $query = DB::query(Database::SELECT,
            "SELECT datetime, data ".
            "FROM data ".
            "WHERE id_param = :id_param ".
            "ORDER BY datetime DESC ".
            "LIMIT :limit"
        );
        $query->param(':id_param', $idParam);
        $query->param(':limit', $limit);
        return $query->execute()->as_array();
    }

    // TODO Use this to get data average (Never used yet...)
    public function getDataAverageByDay($idInstance) {
        $query = DB::query(Database::SELECT,
            "SELECT DATE(". $this->getExprDatetime() .") AS `date`, ROUND(AVG(room_temperature), 1) AS avg_room_temp, ROUND(AVG(tank_temperature), 1) AS avg_tank_temp, ROUND(AVG(humidity), 1) AS avg_humidity ".
            "FROM data ".
            "WHERE DATE(". $this->getExprDatetime() .") <= DATE(SUBDATE(current_date, 2)) AND id_instance = ". $idInstance ." ".
            "GROUP BY `date`"
        );
        return $query->execute()->as_array();
    }

    function insertParamsData($idInstance, $data) {
        $mLog = new Model_Log();
        $mParam = new Model_Param();
        $return = array();
        $datetime = null;

        if(isset($data['datetime'])) {
            $datetime = date('Y-m-d H:i:s', $data['datetime']);
        } else {
            $datetime = date("Y-m-d H:i:s");
        }
        $params = $data['params'];
        if( ! is_array($params)) {
            // If not an array, maybe it's json
            $params = json_decode($data['params'], true);
        }
        foreach($params as $paramData) {
            $param = $mParam->getParamByAlias($idInstance, $paramData['a']);

            if(isset($param['id'])) {
                // TODO Validation
                $value = null;
                if($param['dataType'] == "boolean") {
                    $value = ($paramData['v'] == "1" ? 'true' : 'false');
                } else if($param['dataType'] == "int") {
                    $value = $paramData['v'];
                } else if($param['dataType'] == "float") {
                    $value = $paramData['v'];
                }
                $query = DB::insert('data', array(
                    'id',
                    'id_instance',
                    'id_param',
                    'datetime',
                    'data'
                ))->values( array(
                    DB::expr("UUID()"),
                    $idInstance,
                    $param['id'],
                    $datetime,
                    '{"type":"'.$param['dataType'].'","value":'. $value .'}'
                ));
                $query->execute();

                $return['success'] = true;
            } else {
                $mLog->log($idInstance, "error", "Unknown param: "+ $paramData['a']);
                $return['errors'][] = "Unknown param.";
            }
            // TODO Improve return..
        }
        return $return;
    }

    function insertData($idInstance, $data) {
        $return = array();
//        $validation = Validation::factory($data);
//        $validation->rule('datetime', 'decimal', array(':value', '1'));
        $datetime = null;

        var_dump($data);

        foreach ($data as $key => $value) {
            if ($key == "datetime") {
                $datetime = $value;
                break;
            }
        }
        foreach ($data as $key => $value) {
            if ($key != "datetime") {
                $datetime = $value;
                break;
            }
        }


        $validation->rule('roomTemperature', 'decimal', array(':value', '1'));
        $validation->rule('tankTemperature', 'decimal', array(':value', '1'));
        $validation->rule('humidity', 'decimal', array(':value', '1'));

        // FIXME Validate code
        // FIXME Validate datetime

        if( $validation->check() ) {
            if( ! isset($data['datetime']) ) {
                $data['datetime'] = null;
            }
            if( ! isset($data['humidity']) ) {
                $data['humidity'] = null;
            }

            if ( ! isset($data['id_param'])) {
                $data['id_param'] = 0;
            }

            $query = DB::insert('data', array(
                'id',
                'id_instance',
                'id_param',
                'datetime',
                'humidity',
                'room_temperature',
                'tank_temperature'
            ))->values( array(
                DB::expr("UUID()"),
                $idInstance,
                $data['id_param'],
                $data['datetime'],
                $data['humidity'],
                $data['roomTemperature'],
                $data['tankTemperature']
            ) );
            $query->execute();

            $return['success'] = true;
        } else {
            $return['success'] = false;
            $return['errors'] = $validation->errors('todo');
        }
        return $return;
    }

    private function getExprDatetime() {
        return "IF(datetime IS NOT NULL, CONVERT_TZ(datetime, '+00:00', '". $this->getOffset() ."'), timestamp)";
    }

    private function getOffset() {
        $time = new DateTime('now', new DateTimeZone(date_default_timezone_get()) );
        return $time->format('P');
    }
}