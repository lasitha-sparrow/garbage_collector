<?php
session_start();
if (!isset($_SESSION["logSession"])) {
    header("Location:index.php");
}
$user= $_SESSION['name'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Map</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="assets/images/icons/favicon.ico"/>
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/animate/animate.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/css-hamburgers/hamburgers.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/select2/select2.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/css/util.css">
    <link rel="stylesheet" type="text/css" href="assets/css/main.css">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css"
          integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script type="text/javascript"
            src="http://maps.googleapis.com/maps/api/js?key=AIzaSyDo6Aeq3MlAfLTBqy-TyXKnlxAz_lwgxR4&sensor=false"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            var mapCenter = new google.maps.LatLng(6.029559, 80.216064); //Google map Coordinates
            var map;
            map_initialize(); // initialize google map
            //############### Google Map Initialize ##############
            function map_initialize() {
                var googleMapOptions =
                    {
                        center: mapCenter, // map center
                        zoom: 10, //zoom level, 0 = earth view to higher value
                        maxZoom: 100,
                        minZoom: 0,
                        zoomControlOptions: {
                            style: google.maps.ZoomControlStyle.SMALL //zoom control size
                        },
                        scaleControl: true, // enable scale control
                        mapTypeId: google.maps.MapTypeId.ROADMAP // google map type
                    };
                map = new google.maps.Map(document.getElementById("google_map"), googleMapOptions);
                //Load Markers from the XML File, Check (map_process.php)
                $.get("map_process.php", function (data) {
                    $(data).find("marker").each(function () {
                        var name = $(this).attr('name');
                        var address = '<p>' + $(this).attr('address') + '</p>';
                        var type = $(this).attr('type');
                        var point = new google.maps.LatLng(parseFloat($(this).attr('lat')), parseFloat($(this).attr('lng')));
                        var img = $(this).attr('img');
                        create_marker2(point, name, address, false, false, false, "icons/pin_blue.png", img);
                    });
                });
                //Right Click to Drop a New Marker
                google.maps.event.addListener(map, 'rightclick', function (event) {
                    //Edit form to be displayed with new marker
                    var EditForm = '<p><div class="marker-edit">' +
                        '<form action="ajax-save.php" method="POST" name="SaveMarker" id="SaveMarker">' +
                        '<label><span>Image :</span><input class="fileUp" id="sortpicture" type="file" name="imgUpload" accept="image/*"></label>' +
                        '<label for="pName"><span>Place Name :</span><input type="text" name="pName" class="save-name" placeholder="Enter Title" maxlength="40" /></label>' +
                        '<label for="pDesc"><span>Description :</span><textarea name="pDesc" class="save-desc" placeholder="Enter Address" maxlength="150"></textarea></label>' +
                        '<label for="pType"><span>Type :</span> <select name="pType" class="save-type">' +
                        '<option value="restaurant">Rastaurant</option><option value="bar">Bar</option>' +
                        '<option value="house">House</option></select></label>' +
                        '<label><input type="hidden" value="' + event.latLng + '" name="geoLocation"></label>' +
                        '</form>' +
                        '</div></p><button type="submit" name="save-marker" class="save-marker">Save Marker Details</button>';
                    //Drop a new Marker with our Edit Form
                    create_marker1(event.latLng, 'New Marker', EditForm, true, true, true, "icons/pin_green.png");
                });
            }
            //############### Create Marker Function ##############
            function create_marker1(MapPos, MapTitle, MapDesc, InfoOpenDefault, DragAble, Removable, iconPath, img) {
                //new marker
                var marker = new google.maps.Marker({
                    position: MapPos,
                    map: map,
                    draggable: DragAble,
                    animation: google.maps.Animation.DROP,
                    title: "Hello World!",
                    icon: iconPath
                });
                //Content structure of info Window for the Markers
                var contentString = $('<div class="marker-info-win">' +
                    '<div class="marker-inner-win"><span class="info-content">' +
                    '<h1 class="marker-heading">' + MapTitle + '</h1>' +
                    MapDesc +
                    '</span><button name="remove-marker" class="remove-marker" title="Remove Marker">Remove Marker</button>' +
                    '</div></div>');
                //Create an infoWindow
                var infowindow = new google.maps.InfoWindow();
                //set the content of infoWindow
                infowindow.setContent(contentString[0]);
                //Find remove button in infoWindow
                var removeBtn = contentString.find('button.remove-marker')[0];
                var saveBtn = contentString.find('button.save-marker')[0];
                //add click listner to remove marker button
                google.maps.event.addDomListener(removeBtn, "click", function (event) {
                    remove_marker(marker);
                });
                if (typeof saveBtn !== 'undefined') //continue only when save button is present
                {
                    //add click listner to save marker button
                    google.maps.event.addDomListener(saveBtn, "click", function (event) {
                        var mReplace = contentString.find('span.info-content'); //html to be replaced after success
                        var mName = contentString.find('input.save-name')[0].value; //name input field value
                        var mDesc = contentString.find('textarea.save-desc')[0].value; //description input field value
                        var mType = contentString.find('select.save-type')[0].value; //type of marker
                        var mImg = contentString.find('input.fileUp')[0].value.split('\\').pop(); //type of marker
                        // console.log(mImg)
                        if (mName == '' || mDesc == '') {
                            alert("Please enter Name and Description!");
                        } else {
                            save_marker(marker, mName, mDesc, mType, mReplace, mImg); //call save marker function
                        }
                    });
                }
                //add click listner to save marker button
                google.maps.event.addListener(marker, 'click', function () {
                    infowindow.open(map, marker); // click on marker opens info window
                });
                if (InfoOpenDefault) //whether info window should be open by default
                {
                    infowindow.open(map, marker);
                }
            }
            //############### Create Marker Function ##############
            function create_marker2(MapPos, MapTitle, MapDesc, InfoOpenDefault, DragAble, Removable, iconPath, img) {
                //new marker
                var marker = new google.maps.Marker({
                    position: MapPos,
                    map: map,
                    draggable: DragAble,
                    animation: google.maps.Animation.DROP,
                    title: "Hello World!",
                    icon: iconPath
                });
                //Content structure of info Window for the Markers
                var contentString = $('<div class="marker-info-win">' +
                    '<div class="marker-inner-win"><span class="info-content">' +
                    '<img width="200px" height="200px" src="' + window.location.origin + '/' + window.location.pathname.split('/')[1] + '/' + img + '" ' +
                    '</div>' +
                    '<div class="marker-inner-win"><span class="info-content">' +
                    '<h1 class="marker-heading">' + MapTitle + '</h1>' +
                    MapDesc +
                    '</span><button name="remove-marker" class="remove-marker" title="Remove Marker">Remove Marker</button>' +
                    '</div></div>');
                //Create an infoWindow
                var infowindow = new google.maps.InfoWindow();
                //set the content of infoWindow
                infowindow.setContent(contentString[0]);
                //Find remove button in infoWindow
                var removeBtn = contentString.find('button.remove-marker')[0];
                var saveBtn = contentString.find('button.save-marker')[0];
                //add click listner to remove marker button
                google.maps.event.addDomListener(removeBtn, "click", function (event) {
                    remove_marker(marker);
                });
                if (typeof saveBtn !== 'undefined') //continue only when save button is present
                {
                    //add click listner to save marker button
                    google.maps.event.addDomListener(saveBtn, "click", function (event) {
                        var mReplace = contentString.find('span.info-content'); //html to be replaced after success
                        var mName = contentString.find('input.save-name')[0].value; //name input field value
                        var mDesc = contentString.find('textarea.save-desc')[0].value; //description input field value
                        var mType = contentString.find('select.save-type')[0].value; //type of marker
                        var mImg = contentString.find('input.fileUp')[0].value.split('\\').pop(); //type of marker
                        // console.log(mImg)
                        if (mName == '' || mDesc == '') {
                            alert("Please enter Name and Description!");
                        } else {
                            save_marker(marker, mName, mDesc, mType, mReplace, mImg); //call save marker function
                        }
                    });
                }
                //add click listner to save marker button
                google.maps.event.addListener(marker, 'click', function () {
                    infowindow.open(map, marker); // click on marker opens info window
                });
                if (InfoOpenDefault) //whether info window should be open by default
                {
                    infowindow.open(map, marker);
                }
            }
            //############### Remove Marker Function ##############
            function remove_marker(Marker) {
                /* determine whether marker is draggable
                new markers are draggable and saved markers are fixed */
                if (Marker.getDraggable()) {
                    Marker.setMap(null); //just remove new marker
                }
                else {
                    //Remove saved marker from DB and map using jQuery Ajax
                    var mLatLang = Marker.getPosition().toUrlValue(); //get marker position
                    var myData = {del: 'true', latlang: mLatLang}; //post variables
                    $.ajax({
                        type: "POST",
                        url: "map_process.php",
                        data: myData,
                        success: function (data) {
                            Marker.setMap(null);
                            alert(data);
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            alert(thrownError); //throw any errors
                        }
                    });
                }
            }
            //############### Save Marker Function ##############
            function save_marker(Marker, mName, mAddress, mType, replaceWin, imgUpload) {
                //Save new marker using jQuery Ajax
                var mLatLang = Marker.getPosition().toUrlValue(); //get marker position
                var file_data = $('#sortpicture').prop('files')[0];
                console.log(file_data)
                var form_data = new FormData(this);
                form_data.append('file', file_data);
                form_data.append('name', mName);
                form_data.append('address', mAddress);
                form_data.append('latlang', mLatLang);
                form_data.append('type', mType);
                var myData = {name: mName, address: mAddress, latlang: mLatLang, type: mType, file: form_data}; //post variables
                console.log(replaceWin);
                $.ajax({
                    type: "POST",
                    url: "map_process.php",
                    dataType: 'text',  // what to expect back from the PHP script, if anything
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    success: function (data) {
                        replaceWin.html(data); //replace info window with new html
                        Marker.setDraggable(false); //set marker to fixed
                        Marker.setIcon('icons/pin_blue.png'); //replace icon
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(thrownError); //throw any errors
                    }
                });
            }
        });
    </script>

    <style type="text/css">
        h1.heading {
            padding: 0px;
            margin: 0px 0px 10px 0px;
            text-align: center;
            font: 18px Georgia, "Times New Roman", Times, serif;
        }
        /* width and height of google map */
        #google_map {
            width: 90%;
            height: 500px;
            margin-top: 0px;
            margin-left: auto;
            margin-right: auto;
        }
        /* Marker Edit form */
        .marker-edit label {
            display: block;
            margin-bottom: 5px;
        }
        .marker-edit label span {
            width: 100px;
            float: left;
        }
        .marker-edit label input, .marker-edit label select {
            height: 24px;
        }
        .marker-edit label textarea {
            height: 60px;
        }
        .marker-edit label input, .marker-edit label select, .marker-edit label textarea {
            width: 60%;
            margin: 0px;
            padding-left: 5px;
            border: 1px solid #DDD;
            border-radius: 3px;
        }
        /* Marker Info Window */
        h1.marker-heading {
            color: #585858;
            margin: 1px;
            padding: 0px;
            font: 18px "Trebuchet MS", Arial;
            border-bottom: 1px dotted #D8D8D8;
        }
        div.marker-info-win {
            max-width: 300px;
            margin-right: -20px;
        }
        div.marker-info-win p {
            padding: 0px;
            margin: 12px 0px 10px 0;
        }
        div.marker-inner-win {
            padding: 5px;
        }
        button.save-marker, button.remove-marker {
            border: none;
            background: rgba(0, 0, 0, 0);
            color: #00F;
            padding: 0px;
            text-decoration: underline;
            margin-right: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="limiter">
    <div class="container-login100">
        
<div class="row">
    <div class="col-sm-12">
    <div> 
    <p align="center">logged in as: <?php echo $user ?>.</p>
        <form action="logout.php" method="post">
            <button class="login100-form-btn" type="submit">Logout</button>
        </form>
        </div>
    </div>
</div>

            <span class="login100-form-title">
                        Smart Garbage collection system
            </span>
<p>Right Click to Drop a New Marker</p>

<div id="google_map"></div>

</div>
</div>

</body>
</html>