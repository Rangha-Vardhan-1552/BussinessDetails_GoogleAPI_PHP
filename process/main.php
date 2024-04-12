<?php
include('../config.php');
$city_name = $_GET["city_name"];
// echo($city_name);
$city = get_lat_lng($city_name);
// echo($city);
$keyword = $_GET["keyword"];
// echo($keyword);
$page_token = "";
$perpage = $_GET["perpage"];
// echo($perpage);
$page = $_GET["page"];
// echo($page);
$should_rating = $_GET["rating"];
// echo($should_rating);
$select_cols = "id, name, place_id, city_name, icon, location";
if($should_rating == "true") {
    $select_cols .= ", rating";
}

$response = array();

function get_lat_lng($city_name) {
    //get location coordinates for city name
    $city_name = rawurlencode($city_name);
    global $api_key;
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$city_name&key=AIzaSyCbNamYgr3kXyFUCiV20PDutr96QrZp4Ro";
    $json = getUrlContent($url);
    $result = json_decode($json, true);
    return $result['results'][0]['geometry']['location']['lat'] .",".$result['results'][0]['geometry']['location']['lng'];
}

function queryExists($city, $keyword) {
    global $conn;
    $sql = "SELECT count(*) FROM business_list WHERE city='$city' AND keyword='$keyword'";
    $result = $conn->prepare($sql); 
    $result->execute(); 
    $number_of_rows = $result->fetchColumn();
    return $number_of_rows;
}

function get_places($city,$business,$keyword,$page_token) {
    global $conn;
    global $api_key;
    global $city_name;
    $keyword = rawurlencode($keyword);
    //first getting place ids using nearby search for given coordinates.
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$city&city_name=$city_name&radius=20000&keyword=$keyword&place_id=&pagetoken=$page_token&key=AIzaSyCbNamYgr3kXyFUCiV20PDutr96QrZp4Ro";
    $json = getUrlContent($url);
    $result = json_decode($json, true);
    
    foreach ($result['results'] as $key => $value) {
        $name = $value['name'];
        $place_id = $value['place_id'];
        $icon = $value['icon'];
        $rating = (isset($value['rating']) ? $value['rating'] : "");
        $location = $value['geometry']['location']['lat'].",". $value['geometry']['location']['lng'];  
        
        $sql = "INSERT INTO business_list (name, place_id, city, city_name, keyword, rating, icon, location) VALUES (?,?,?,?,?,?,?,?)";
        $stmt= $conn->prepare($sql);
        $stmt->execute([$name, $place_id, $city, $city_name, $keyword, $rating, $icon, $location]);
    }

    if(isset($result['next_page_token'])) {
        $next_page_token = $result['next_page_token'];
        get_places($city,$business,$keyword,$next_page_token);
    }
}

function try_from_db($city,$keyword) {
    global $conn;
    global $response;
    global $select_cols;
    global $page;
    global $perpage;
    $start = ($page-1)*$perpage;
    $exists = queryExists($city, $keyword);
    if($exists) {
        $data = $conn->query("SELECT $select_cols FROM business_list WHERE city='$city' AND keyword='$keyword' ORDER BY id DESC LIMIT $start , $perpage")->fetchAll(PDO::FETCH_ASSOC);
        
        $response['results'] = $data;
        $response['total_pages'] = $exists/$perpage;
        
    } else {
        global $city, $business, $keyword, $page_token;
        //calling function get places to get data from google API.
        get_places($city,$business,$keyword,$page_token);
        //re calling this function to get data from db after retrieving data from Google.
        try_from_db($city,$keyword);
    }
}

try_from_db($city,$keyword);
print_r(json_encode($response));
$conn = null;
?>


