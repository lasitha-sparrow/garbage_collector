<?php
//PHP 5 +

// database settings 
$db_username = 'root';
$db_password = '';
$db_name = 'maps';
$db_host = 'localhost';

//mysqli
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

if (mysqli_connect_errno()) {
    header('HTTP/1.1 500 Error: Could not connect to db!');
    exit();
}

################ Save & delete markers #################
if ($_POST) //run only if there's a post data
{
    //make sure request is comming from Ajax
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    if (!$xhr) {
        header('HTTP/1.1 500 Error: Request must come from Ajax!');
        exit();
    }

    // get marker position and split it for database
    $mLatLang = explode(',', $_POST["latlang"]);
    $mLat = filter_var($mLatLang[0], FILTER_VALIDATE_FLOAT);
    $mLng = filter_var($mLatLang[1], FILTER_VALIDATE_FLOAT);

    //Delete Marker
    if (isset($_POST["del"]) && $_POST["del"] == true) {
        $results = $mysqli->query("DELETE FROM markers WHERE lat=$mLat AND lng=$mLng");
        if (!$results) {
            header('HTTP/1.1 500 Error: Could not delete Markers!');
            exit();
        }
        exit("Done!");
    }

    $mName = filter_var($_POST["name"], FILTER_SANITIZE_STRING);
    $mAddress = filter_var($_POST["address"], FILTER_SANITIZE_STRING);
    $mType = filter_var($_POST["type"], FILTER_SANITIZE_STRING);

    $target_dir = "uploads/images/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
    if (isset($_POST["submit"])) {
        $check = getimagesize($_FILES["file"]["tmp_name"]);
        if ($check !== false) {
//            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
//            echo "File is not an image.";
            $uploadOk = 0;
        }
    }
// Check if file already exists
    if (file_exists($target_file)) {
//        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
// Check file size
//    if ($_FILES["fileToUpload"]["size"] > 500000) {
//        echo "Sorry, your file is too large.";
//        $uploadOk = 0;
//    }
// Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif") {
//        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
// Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
//        echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
//            echo "The file " . basename($_FILES["fileToUpload"]["name"]) . " has been uploaded.";
        } else {
//            echo "Sorry, there was an error uploading your file.";
        }
    }

//    $imgLoc = filter_var($_POST["imageLocation"]);
    $results = $mysqli->query("INSERT INTO markers (name, address, lat, lng, type, imgLocation) VALUES ('$mName','$mAddress',$mLat, $mLng, '$mType','$target_file')");
    if (!$results) {
        header('HTTP/1.1 500 Error: Could not create marker!');
        exit();
    }

    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
    $output = '<img width="200px" height="200px" src="' . $actual_link . $target_file . '"><br><h1 class="marker-heading">' . $mName . '</h1><p>' . $mAddress . '</p>';
    exit($output);
}


################ Continue generating Map XML #################

//Create a new DOMDocument object
$dom = new DOMDocument("1.0");
$node = $dom->createElement("markers"); //Create new element node
$parnode = $dom->appendChild($node); //make the node show up 

// Select all the rows in the markers table
$results = $mysqli->query("SELECT * FROM markers WHERE 1");
if (!$results) {
    header('HTTP/1.1 500 Error: Could not get markers!');
    exit();
}

//set document header to text/xml
header("Content-type: text/xml");

// Iterate through the rows, adding XML nodes for each
while ($obj = $results->fetch_object()) {
    $node = $dom->createElement("marker");
    $newnode = $parnode->appendChild($node);
    $newnode->setAttribute("name", $obj->name);
    $newnode->setAttribute("address", $obj->address);
    $newnode->setAttribute("lat", $obj->lat);
    $newnode->setAttribute("lng", $obj->lng);
    $newnode->setAttribute("type", $obj->type);
    $newnode->setAttribute("img", $obj->imgLocation);
}

echo $dom->saveXML();
