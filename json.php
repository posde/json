<?php
//Use this to tunnel a file through json for crossdomain access.

function pluto_mainJSON($table, $key="", $fields=array("Description"), $join =" where ") {
    // connect to the mysql database
    $link = mysqli_connect('localhost', 'root', '', 'pluto_main');
    mysqli_set_charset($link,'utf8');
    $sql = "SELECT PK_$table" . (count($fields)>0?",".implode(",", $fields):"") . " FROM " . $table;
    $sql .= " $join ";
    if ($key == "") {
        $key = " true ";
    } else {
        $key = "PK_$table = $key";
    }
    
    $sql .= $key;
    echo $sql;
    $result = mysqli_query($link,$sql);
    if ( ! $result) throw new Exception("No results");
    $content = "--";
    $JSONcontent = "";
    // for ($i=0;$i<mysqli_num_rows($result);$i++) {
    $content = "{\n";
    $first = 1;
    while ($row = mysqli_fetch_row($result)) {
        // $JSONcontent += ($i>0?',':'').json_encode(mysqli_fetch_object($result));
        if ($first) {
            $first = 0;
        } else {
            $content .= ",";
        }
        $content .= "   {\n";
        $content .= "      \"PK_$table\":" . $row[0];
        $column = 0;
        foreach($fields as $label) {
            $column++;
            $cell = $row[$column];
            $content .= ",\n";
            $content .= "      \"$label\": \"" . $cell . "\"";
            
        }
        $content .= "\n   }";
        $content .= "\n";
      }
    $content .= "}";
    
    return $content;
}
try {
    
    // $uri = $_GET['uri'];            // the URI that we're fetching
    $table = @$_GET['table'];
    $curl = 0;
    $callback = @$_GET['_callback']; // the callback that's used with jsonp (optional)
    $key = @$_GET['key'];

    $database = 0;
    if ($table == "now_playing" or $table == "playlist") {
        // now playing and playlist need to be provided via the JSON plugin in the router space.
        $curl = 1;
    } elseif ( $table == "ea" ) {
        $database = '1';
        $contents = pluto_mainJSON("EntertainArea",$key);
    } elseif ( $table == "rooms" ) {
        $database = '1';
        $contents = pluto_mainJSON("Room",$key);
    } elseif ( $table == "lights" ) {
        // All devices of device category lights except drapes and light sensors
        $database = '1';
        $contents = pluto_mainJSON("Device",$key,array("Device.Description","Status","State","FK_Room"),"JOIN DeviceTemplate ON Device.FK_DeviceTemplate = DeviceTemplate.PK_DeviceTemplate Where FK_DeviceCategory=73 AND FK_DeviceTemplate != 68 AND FK_DeviceTemplate != 1745 and ");
    } elseif ( $table == "drapes" ) {
        // All device of device template 68 (drapes)
        $database = '1';
        $contents = pluto_mainJSON("Device",$key,array("Device.Description","Status","State","FK_Room"),"JOIN DeviceTemplate ON Device.FK_DeviceTemplate = DeviceTemplate.PK_DeviceTemplate Where FK_DeviceTemplate = 68 and ");
    } elseif ( $table == "phones" ) {
        // All devices of device categories phones, hard phones, softphones etc.
        $database = '1';
        $contents = pluto_mainJSON("Device",$key,array("Device.Description","Status","State","FK_Room"),"JOIN DeviceTemplate ON Device.FK_DeviceTemplate = DeviceTemplate.PK_DeviceTemplate Where FK_DeviceCategory in (89,90,91,92,147) AND ");
    } elseif ( $table == "cameras" ) {
        // All devices of device categories phones, hard phones, softphones etc.
        $database = '1';
        $contents = pluto_mainJSON("Device",$key,array("Device.Description","Status","State","FK_Room"),"JOIN DeviceTemplate ON Device.FK_DeviceTemplate = DeviceTemplate.PK_DeviceTemplate Where FK_DeviceCategory in (93,140) AND ");
    } elseif ($table=="") {
        $database = 1;
        $contents = "{\n";
        $first = 1;
        foreach(array("now_playing","playlist","ea","rooms","lights","drapes","phones","cameras") as $table) { 
            if ($first) {
                $first = 0;
            } else {
                $contents .= ",\n";
            }
            $contents .= "   {\n      \"table\":\"$table\"\n";
            $contents .= "   }";
        }
        $contents .= "\n}";
        $curl = 0;
    }
    if (! $curl and ! $database) {
        throw new Exception("Incorrect table specified. Call script alone to get a list of tables");        
    }
    if ($curl) {
        $uri = "dcerouter:7230/$table?ea=$key";
        
        // if (is_null($_GET['uri'])) {
        //     throw new Exception('Bad URI');
        // }

        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $contents = curl_exec($ch);
        $info = curl_getinfo($ch);
        
        if ($info['http_code'] >= 400) {
            throw new Exception('Bad error code (' . $info['http_code'] . ')');
        }
    }
    // header shit.
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');

    // base64 encode the data
    $data = base64_encode($contents);
    
    $json_data = '{ "data" : "'. $data .'" }';
    
    if (is_null($callback)) {
        // if no callback was specified, then just send back the json data.
        echo $contents;
    } else {
        // if a callback was supplied (_callback GET var), then wrap the json data in the callback function
        echo $callback . '(' . $json_data . ');';
    }
    

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
    exit(1);
}



?>