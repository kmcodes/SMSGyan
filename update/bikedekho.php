<?php

set_time_limit(0);
include 'configdb.php';

//$query = "DELETE FROM `bikedekho`";
//if (mysql_query($query)) {
//    echo "Recode Deleted<br>";
//}

$url = "http://www.bikedekho.com/";
$data = file_get_contents($url);

$start = strpos($data, '<span class="brand-drop-div">');
$end = strpos($data, '<span class="select-model-div">');
$data = substr($data, $start, $end - $start);
$out = '';

if (preg_match_all("~<option value=\"(.+)\">(.+)</option>~Uis", $data, $matchOrg, PREG_SET_ORDER)) {
    foreach ($matchOrg as $stp) {
        if ($stp[2] != '--Select Brand--') {
            $brand[]['id'] = $stp[1];
            $brand[count($brand) - 1]['name'] = $stp[2];
        }
    }
    $i = 0;
    foreach ($brand as $make) {
        $make_name = $make['name'];
        $make_id = $make['id'];

        $url = "http://www.bikedekho.com/ajaxcall/";
        $fields_string = 'modelid=' . urlencode($make_id);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: www.bikedekho.com", "Content-Type: application/x-www-form-urlencoded; charset=UTF-8", "X-Requested-With:XMLHttpRequest", "Referer:http://www.bikedekho.com/", "Content-length: " . strlen($fields_string)));
        $result = curl_exec($ch);
        curl_close($ch);
        $array = json_decode($result);

        foreach ($array as $model) {
            $model_id = $model->modelid;
            $model_name = $model->modelname;
            $model_name = trim(str_replace($make_name, "", $model_name));
            $srch = $model->modelname;
//            $srch = preg_replace("~[^\w\d\s]~", " ", $model_id);
//            $srch = preg_replace("~\bcar|price\b~i", "", $srch);
//            $srch = trim(preg_replace("~[\s]+~", "", $srch));

            $query = "insert into bikedekho (make_id,model_id,make,model,srch) values ('" . mysql_real_escape_string($make_id) . "','" . mysql_real_escape_string($model_id) . "','" . mysql_real_escape_string($make_name) . "','" . mysql_real_escape_string($model_name) . "','" . mysql_real_escape_string($srch) . "')";
            $query .= " ON DUPLICATE KEY UPDATE make=VALUES(make),model=VALUES(model),srch=VALUES(srch)";
            if (mysql_query($query)) {
                echo "Recode inserted<br>";
            }
        }
    }
}
?>