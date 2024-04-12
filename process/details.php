    <?php

    $place_id = isset($_GET['place_id']) ? $_GET['place_id'] : null;
    $name = isset($_GET['name']) ? $_GET['name'] : null;
    $should_alexa_rank = isset($_GET['get_alexa_rank']) ? $_GET['get_alexa_rank'] : false;
    $should_rating = isset($_GET['get_rating']) ? $_GET['get_rating'] : false;
    $should_reviews = isset($_GET['get_reviews']) ? $_GET['get_reviews'] : false;
    $should_news = isset($_GET['get_news']) ? $_GET['get_news'] : false;
    $select_cols = "id, name, place_id, city, city_name, keyword, address, phone, website, icon, location";
    if($should_alexa_rank == "true") {
        $select_cols .= ", alexa_rank";
    }
    $reviews_select_cols = "id, place_id, author_name, profile_photo_url, text";
    if($should_rating == "true") {
        $reviews_select_cols .= ", user_rating";
    }
    include('../config.php');
    $response;

    //function for alexa traffic rank 

    function alexa_rank($url){
        $xml = getUrlContent("https://data.alexa.com/data?cli=10&url=".$url);
        $xml = simplexml_load_string($xml);
        if(isset($xml->SD)):
            //return $xml->SD->REACH->attributes();
            return $xml->SD->REACH['RANK'];
        endif;
    }

    function get_news($business_name) {
        global $response;
        $business_name = rawurlencode($business_name);
        $news = getUrlContent('https://news.google.com/news/feeds?hl=en&q='.$business_name.'&ie=utf-8&num=10&output=rss');
        $news = simplexml_load_string($news);
        // print_r($news);
        $feeds = array();
        $i = -1;

        if (isset($news->channel) && isset($news->channel->item) ) {
            foreach ($news->channel->item as $item) {
                preg_match('@src="([^"]+)"@', $item->description, $match);
                $parts = explode('<font size="-1">', $item->description);
                $title = isset($item->title) ? (string) $item->title : "";
                $link = isset($item->link) ? (string) $item->link : "";
                $img = isset($match[1]) ? $match[1] : "";
                $site_title = isset($parts[1]) ? strip_tags($parts[1]) : "";
                $story = isset($parts[2]) ? strip_tags($parts[2]) : "";
                similar_text($title, $business_name, $in_title);
                similar_text($story, $business_name, $in_story);
                
                if ($in_title > 15 || $in_story > 10) {
                    $feeds[$i]['title'] = $title;
                    $feeds[$i]['link'] = $link;
                    $feeds[$i]['image'] = $img;
                    $feeds[$i]['site_title'] = $site_title;
                    $feeds[$i]['story'] = $story;
                    $i++;
                }
            }
        
            $response['news'] = $feeds;
        } else {
            // Handle the case where $news, $news->channel, or $news->channel->item is not in the expected format
            $response['error'] = "Invalid news data format";
        }
        
    }

    function details_exist($place_id)
    {
        global $conn;
        $data = $conn->query("SELECT * FROM business_list WHERE place_id='$place_id'")->fetch(PDO::FETCH_ASSOC);
        if($data['address'] == "")
        {
            return false;
        }
        else
        {
            return true;
        }

    }

    function get_details($place_id) {
        global $conn;
        global $api_key;
        $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=$place_id&fields=name,icon,rating,formatted_address,formatted_phone_number,website,opening_hours,reviews,geometry,url&key=$api_key";
        $json = getUrlContent($url);
        $result = json_decode($json, true);
            $name = is_null($result['result']['name']? "No Name":$result['result']['name']);
            $icon = is_null($result['result']['icon'])? "No icon": $result['result']['icon'];
            $rating = (isset($result['result']['rating']) ? $result['result']['rating'] : false);
            $location = $result['result']['geometry']['location']['lat'].",". $result['result']['geometry']['location']['lng'];
            $address = $result['result']['formatted_address'];
            $phone = (isset($result['result']['formatted_phone_number']) ? $result['result']['formatted_phone_number'] : false);
            $website = (isset($result['result']['website']) ? $result['result']['website'] : "");
            $alexa_rank = (isset($result['result']['alexa_rank']) ? alexa_rank($result['result']['alexa_rank']) : null);
            $week = (isset($result['result']['opening_hours']['weekday_text']) ? serialize($result['result']['opening_hours']['weekday_text']) : null);
            
            $sql = "UPDATE business_list SET name=?, rating=?, address=?, phone=?, website=?, alexa_rank=?, icon=?, location=?, week=? WHERE place_id='$place_id'";
            $stmt= $conn->prepare($sql);
            $stmt->execute([$name, $rating, $address, $phone, $website, $alexa_rank, $icon, $location, $week]);
            if(isset($result['result']['reviews'])) {            
                foreach($result['result']['reviews'] as $key) {
                    $author_name = $key['author_name'];
                    $profile_photo_url = $key['profile_photo_url'];
                    $text = $key['text'];
                    $user_rating = $key['rating'];
                    $sql = "INSERT INTO reviews (place_id, author_name, profile_photo_url, text, user_rating) VALUES (?,?,?,?,?)";
                    $stmt= $conn->prepare($sql);
                    $stmt->execute([$place_id, $author_name, $profile_photo_url, $text, $user_rating]);
                }
            }
    }

    function try_details_from_db($place_id) {
        global $conn;
        global $response;
        global $select_cols;
        global $reviews_select_cols;
        global $should_reviews;
        global $should_news;
        $exists = details_exist($place_id);
        if($exists) {
            $data = $conn->query("SELECT $select_cols FROM business_list WHERE place_id='$place_id'")->fetch(PDO::FETCH_ASSOC);
            $response = $data;
            if($should_reviews == "true") {
                $response['reviews'] = $conn->query("SELECT $reviews_select_cols FROM reviews WHERE place_id='$place_id'")->fetchAll(PDO::FETCH_ASSOC);            
            }
            if($should_news == "true") {
                get_news($data['name']);            
            }

            
        } else {
            get_details($place_id);
            try_details_from_db($place_id);

        }
    }

    try_details_from_db($place_id);
    print_r(json_encode($response));


