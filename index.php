<?php

    $show_search = isset($_GET['show_search'])? $_GET['show_search'] : true ;
    $show_search = $show_search == 'true' ? true : false;
    

    $show_rating = isset($_GET['show_rating'])? $_GET['show_rating'] : "" ;    
    $show_alexa_rank = isset($_GET['show_alexa_rank'])? $_GET['show_alexa_rank'] : "" ;
	
	$keyword= isset($_GET['keyword'])? $_GET['keyword'] : '$("#keyword").val()' ;
	$location= isset($_GET['location'])? $_GET['location'] : '$("#city").val()' ;
?>
<!DOCTYPE html>
<html lang="">

<head>
    <meta charset="utf-8">
    <title>Business Info</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="apple-touch-icon" href="">
    <link rel="shortcut icon" href="" type="image/x-icon">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">

    <!-- Font Awesome -->

    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />

    <!-- Link to your CSS file -->
    <link rel="stylesheet" href="assets/style/style.css">

</head>
<script>
function myfunc(){
	document.getElementById("city").value = '<?php echo $location; ?>';
	document.getElementById("keyword").value = '<?php echo $keyword; ?>';
	document.getElementById("btn").click();
}

</script>

<body onLoad="myfunc()">

    <div class="d-flex">
        <div class="w-75 m-auto">
           
            <div class="row place-search-form form-group mt-3" id="searchform">
                <input class="col-12 col-md-4 form-control" type="text" name="" id="city" value="Type City" placeholder="City" required>
                <input class="col-12 col-md-4 form-control" type="text" name="" id="keyword" value="Type Supplier" placeholder="Keyword" required>
                <input class="col-12 col-md-4 btn btn-primary" id="btn" type="button" value="search" />
            </div>
            <?php if(!$show_search) { ?>
			   <script>
			   var div = document.getElementById("searchform");
			   div.style.display = "none";
			   </script>
		   <?php } ?>
            <div id="accordion" class="row loader-contanier">

            </div>
            <nav aria-label="..." class="row mt-3">
                <ul class="pagination mx-auto">
                
                </ul>
            </nav>
        </div>
    </div>


    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>

    <script>
        let get_rating = <?php echo ($show_rating !== "" ? $show_rating : true) ?>;
        let get_alexa_rank = <?php echo ($show_alexa_rank !== "" ? $show_alexa_rank : true) ?>;
        let get_reviews = true;
        let get_news = true;
        
        let perpage = 5;
        let page = 1;

        $("#btn").click(function() {
            let city = $("#city").val();
            let keyword = $("#keyword").val();
            get_results(city, keyword, get_rating, perpage, page);
        });
        $(document).on("click", ".page-link", function() {
            let city = $(this).attr("data-city");
            let keyword = $(this).attr("data-keyword");
            let page = $(this).attr("data-page");
            get_results(city, keyword, get_rating, perpage, page);
        });
        
        function get_results(city, keyword, get_rating, perpage, page) {
            $("#accordion").html(`<img class="m-auto main-loading" src="assets/img/loading.gif">`);
            $.ajax({
                url: "./process/main.php",
                type: 'GET',
                dataType: 'json',
                data: {
                    city_name: city,
                    keyword: keyword,
                    get_rating: get_rating,
                    perpage: perpage,
                    page: page
                },
                success: function(res) {
                    let resultHtml = ``;
                    for (let i = 0; i < res['results'].length; i++) {
                        resultHtml +=
                            `
                            <div class="card col-12 px-0">
                                <div class="card-header" id="heading${i}">
                                  <h5 class="mb-0">
                                    <button class="btn btn-link w-100 text-left btn-expand" data-toggle="collapse" data-target="#collapse${i}" data-place-id="${ res['results'][i].place_id }" aria-expanded="false" aria-controls="collapse${i}"><img class="place-icon" src="${ res['results'][i].icon }">
                                      ${ res['results'][i].name.substring(0,50) }`;
                        if (res['results'][i].rating) {
                            resultHtml += `<span class="float-right">Rating: ${ getStars(res['results'][i].rating) }</span>`;
                        }
                        resultHtml +=
                            `</button>
                                  </h5>
                                </div>
                                <div id="collapse${i}" class="collapse" aria-labelledby="heading${i}" data-parent="#accordion">
                                    <div class="card-body"></div>
                                </div>
                            </div>`;
                    }
                    $("#accordion").html(resultHtml);
                    let paginHtml = ``;
                        if(page > 1) {
                            paginHtml += `<li class="page-item">
                        <a class="page-link" href="#" data-city="${city}" data-keyword="${keyword}" data-page="${parseInt(page) - 1}" tabindex="-1">&lt;&lt;&lt;</a>
                    </li>`;
                        }
                    let active;
                    for(let i = 1 ; i <= res['total_pages']; i++) {
                            active = i == page ? "active" : "";
                            paginHtml += `<li class="page-item ${active}"><a class="page-link" href="#" data-city="${city}" data-keyword="${keyword}" data-page="${i}">${i}</a></li>`;
                        }
                        if(page < res['total_pages']) {
                            paginHtml += `<li class="page-item">
                        <a class="page-link" href="#" data-city="${city}" data-keyword="${keyword}" data-page="${parseInt(page) + 1}">&gt;&gt;&gt;</a>
                    </li>`;
                        
                    }
                    $("ul.pagination").html(paginHtml);

                }
            });

        }

        $(document).on("click", ".btn-expand", function() {
            let card = $(this).parents(".card");
            let container = $(this).parents(".card").find(".card-body");
            if (container.html() === "") {
                container.html(`<div class="w-100 d-flex loader-contanier"><img class="mx-auto accordion-loading" src="assets/img/loading.gif"></div>`);
                let place_id = $(this).attr("data-place-id");
                $.ajax({
                    url: "./process/details.php",
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        place_id: place_id,
                        get_alexa_rank: get_alexa_rank,
                        get_rating: get_rating,
                        get_reviews: get_reviews,
                        get_news: get_news,
                    },
                    success: function(res) {
                        let ehtml = `
                        <h5>${res['name']} [${res['city_name']}]</h5>
                        <p>Address: ${res['address']}</p>`;
                        ehtml += (res['phone'] !== "" ? `<p>Phone: ${res['phone']}</p>` : ``);
                        ehtml += (res['website'] !== "" ? `<p>Website: <a href="${res['website']}" target="_blank">${res['website']}</a></p>` : ``);
                        ehtml += (res['alexa_rank'] !== "" && res['alexa_rank'] !== undefined ? `<p>Pooyam Rank: ${res['alexa_rank']}</p>` : ``);
                        if (res['reviews'] !== undefined && res['reviews'][0]) {
                            ehtml += `<div class="reviews">`;
                            ehtml += `<h5>Reviews</h5>`;
                            for (let i = 0; i < res['reviews'].length; i++) {
                                ehtml += `
                                <h6><img class="review_user_pic" src="${res['reviews'][i].profile_photo_url}"> ${res['reviews'][i].author_name}</h6>`;
                                ehtml += `<p>${getStars(res['reviews'][i].user_rating)}</p>`;
                                ehtml += `<p>${res['reviews'][i].text}</p>`;
                            }
                            ehtml += `</div>`;
                        }
                        if (res['news'] !== undefined && res['news'] !== []) {
                            ehtml += `<div class="news my-5">`;
                            ehtml += `<h5>News</h5>`;
                            for (let i = 0; i < res['news'].length; i++) {
                                ehtml += `<h6>${res['news'][i].title}</h6>`;
                                ehtml += (res['news'][i].image !== "" ? `<img class="news-img" src="${res['news'][i].image}" onerror='$(this).remove();'>` : ``);
                                ehtml += `<p>${res['news'][i].story}</p>`;
                                ehtml += `<a href="${res['news'][i].link}">${res['news'][i].site_title}</a>
                                <br><hr>
                                `;
                            }
                            ehtml += `</div>`;
                        }
                        container.html(ehtml);
                    }
                });
            }
        });


        function getStars(rating) {
            // Round to nearest half
            rating = Math.round(rating * 2) / 2;
            let output = [];
            // Append all the filled whole stars
            for (var i = rating; i >= 1; i--)
                output.push('<i class="fa fa-star" aria-hidden="true" style="color: gold;"></i>&nbsp;');
            // If there is a half a star, append it
            if (i == .5) output.push('<i class="fa fa-star-half-o" aria-hidden="true" style="color: gold;"></i>&nbsp;');
            // Fill the empty stars
            for (let i = (5 - rating); i >= 1; i--)
                output.push('<i class="fa fa-star-o" aria-hidden="true" style="color: gold;"></i>&nbsp;');
            return output.join('');

        }

        // on collapse prevent content bellow it to scroll up...
        $('#accordion').on('shown.bs.collapse', function(event) {
            $('html, body').animate({
                scrollTop: $(event.target).parent().offset().top
            }, 400);
        });

     
    </script>

	
	
	

</body>

</html>
