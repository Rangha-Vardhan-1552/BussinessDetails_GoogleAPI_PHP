<?php
//*****************
//// Google API key
$api_key = "AIzaSyCbNamYgr3kXyFUCiV20PDutr96QrZp4Ro";
// $api_key= "AIzaSyCnOnMzpSmcH1A6KU93yqIFapEI_pGlVVU";
// $api_key= "AIzaSyCzX6fw1SsBeRNifX6XAMKxquNbr8xJK-k";
//$news_apiKey="2117e73f3abc43ee8fb4167fb1654138"

//**************
////DB Connection
$servername = "localhost";
$username = "pooyam_talk";
$password = "Asdqwe@123";
$dbname = "pooyam_ai";

// Create connection
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
// set the PDO error mode to exception
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//*********************************
///function to get content from urls

function getUrlContent($url) {
    fopen("cookies.txt", "w");
    $parts = parse_url($url);
    $host = $parts['host'];
    $ch = curl_init();
    $header = array('GET /1575051 HTTP/1.1',
        "Host: {$host}",
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language:en-US,en;q=0.8',
        'Cache-Control:max-age=0',
        'Connection:keep-alive',
        'Host:adfoc.us',
        'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
    );

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);

    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

