<?php

namespace RRZE\UnivIS;

$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once $parse_uri[0] . 'wp-load.php';
include_once dirname(__FILE__) . '/includes/ICS.php';

$input = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);

if (!empty($input['v']) && !empty($input['h']) && (hash('sha256', $input['v']) == $input['h']) && wp_verify_nonce($input['ics_nonce'], 'createICS')) {

    $aProps = json_decode(openssl_decrypt(base64_decode($input['v']), 'AES-256-CBC', hash('sha256', AUTH_KEY), 0, substr(hash('sha256', AUTH_SALT), 0, 16)), true);

    // $aFreq = [
    //     "w1" => 'WEEKLY;INTERVAL=1',
    //     "w2" => 'WEEKLY;INTERVAL=2',
    //     "w3" => 'WEEKLY;INTERVAL=3',
    //     "w4" => 'WEEKLY;INTERVAL=4',
    //     "m1" => 'MONTHLY;INTERVAL=1',
    //     "m2" => 'MONTHLY;INTERVAL=2',
    //     "m3" => 'MONTHLY;INTERVAL=3',
    //     "m4" => 'MONTHLY;INTERVAL=4',
    // ];

    // $aDay = [
    //     '1' => 'MO',
    //     '2' => 'TU',
    //     '3' => 'WE',
    //     '4' => 'TH',
    //     '5' => 'FR',
    //     '6' => 'SA',
    //     '0' => 'SU',
    // ];

    // if (!empty($aProps['REPEATNR'])) {
    //     $aParts = explode(' ', $aProps['REPEATNR']);
    //     if (!empty($aFreq[$aParts[0]])){
    //         $aProps['FREQ'] = $aFreq[$aParts[0]];
    //         $aDays = explode(',', $aParts[1]);
    //         $aProps['REPEAT'] = '';
    //         foreach($aDay as $nr => $val){
    //             if (in_array($nr, $aDays)){
    //                 $aProps['REPEAT'] .= $val . ',';
    //             }
    //         }
    //         $aProps['REPEAT'] = rtrim($aProps['REPEAT'], ',');
    //     }
    //     unset($aProps['REPEATNR']);
    // }


    // echo '<pre>';
    // var_dump($aProps);
    // exit;

    $ics = new ICS($aProps);

    // Output ICS
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $aProps['FILENAME'] . '.ics');
    echo $ics->toString();
    // echo '<pre>';
    // var_dump($ics); // TEST
} else {
    // Output Forbidden
    header('HTTP/1.0 403 Forbidden');
    echo 'The computer says "no".';
}

exit;