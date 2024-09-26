<?php
error_reporting(0);
$reff = readline("[?] Referral      ");
$list_query = array_filter(@explode("\n", str_replace(array("\r", " "), "", @file_get_contents(readline("[?] List Query       ")))));
echo "[*] Total Query : ".count($list_query)."\n";
for ($i = 0; $i < count($list_query); $i++) {
    $c = $i + 1;
    echo "\n[$c]\n";
    $exec = shell_exec("python3 generate_ton_wallet.py");
    $data = explode("\t", $exec);
    $wallet = $data[0];
    $mnemonic = $data[1];
    echo "[*] Wallet : $wallet\n";
    echo "[*] Mnemonic : $mnemonic\n";
    if(empty($reff)){
        $auth = get_auth($list_query[$i], false, $wallet);
    }
    else{
        $auth = get_auth($list_query[$i], $reff, $wallet);
    }
    echo "[*] Get Auth : ";
    if($auth){
        echo "success\n";
        echo "[*] Update Wallet : ".update_wallet($auth, $wallet)."\n";
        echo "[*] Claim New User : ".claim_new_user($auth)."\n";
        $profile = profile($auth);
        $id_tele = $profile['id_telegram'];
        $username = $profile['username'];
        $reff_code = $profile['referral_code'];
        @file_put_contents("redpocket_wallet.txt", "$id_tele\t$username\t$reff_code\t$wallet\t$mnemonic", FILE_APPEND);
        $task = get_task($auth);
        echo "[*] Get Task : ";
        if($task){
            echo "success\n\n";
            for ($a = 0; $a < count($task); $a++) {
                $ex = explode("|", $task[$a]);
                echo "[-] ".$ex[1]." => ".solve_task($ex[0], $auth)."\n";
            }
            echo "\n";
        }
        else{
            echo "failed\n\n";
        }
        echo "[*] Claim Task Referral : ".claim_task_refer($auth)."\n";
        Play:
        $check_scratch = profile($auth)['balance_scratch_card'];
        echo "[*] Play Scratch Count : $check_scratch\n";
        if($check_scratch > 0){
            echo "\n";
            for ($a = 0; $a < $check_scratch; $a++) {
                $c = $a + 1;
                $scratch = stratch_card($auth);
                if($scratch){
                    echo "[$c] ".$scratch['reward']." ".$scratch['typeReward']."\n";
                    sleep(12);
                }
                else{
                    goto Play;
                }
            }
        }
    }
    else{
        echo "failed\n\n";
    }
}








function get_auth($query, $reff = false, $wallet){
    if($reff){
        $curl = curl("auth/login", false, "{\"initData\":\"$query\",\"refCode\":\"$reff\",\"wallet\":\"$wallet\",\"chain\":\"-239\",\"network\":\"TON\"}")['data']['token']['access'];
    }
    else{
        $curl = curl("auth/login", false, "{\"initData\":\"$query\",\"refCode\":\"\",\"wallet\":\"$wallet\",\"chain\":\"-239\",\"network\":\"TON\"}")['data']['token']['access'];
    }
    return $curl;
}

function update_wallet($auth, $wallet){
    $curl = curl("user/update-wallet", $auth, "{\"wallet\":\"$wallet\",\"type_wallet\":\"tonkeeper\"}")['message'];
    return $curl;
}

function claim_new_user($auth){
    $curl = curl("user/claim-new-user", $auth, "{}")['message'];
    return $curl;
}

function claim_task_refer($auth){
    $curl = curl("task/claim-friend", $auth, "{\"task_id\":7}")['message'];
    return $curl;
}

function get_task($auth){
    $curl = curl("task/me", $auth)['data'];
    for ($i = 0; $i < count($curl); $i++) {
        $list[] = $curl[$i]['id']."|".$curl[$i]['name'];
    }
    return $list;
}

function solve_task($id, $auth){
    $curl = curl("task/claim", $auth, "{\"task_id\":$id}")['message'];
    return $curl;
}

function profile($auth){
    $curl = curl("user/me", $auth)['data'];
    return $curl;
}

function stratch_card($auth){
    $curl = curl("scratch-card/open", $auth, "{}")['data']['his'];
    return $curl;
}

function curl($path, $auth = false, $body = false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.redpocket.io/'.$path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($body){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $headers = array();
    $headers[] = 'Accept: application/json, text/plain, */*';
    $headers[] = 'Accept-Language: en-US,en;q=0.9';
    if($auth){
        $headers[] = 'Authorization: Bearer '.$auth;
    }
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Origin: https://app.redpocket.io';
    $headers[] = 'Referer: https://app.redpocket.io/';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $decode = json_decode($result, true);
    return $decode;
}