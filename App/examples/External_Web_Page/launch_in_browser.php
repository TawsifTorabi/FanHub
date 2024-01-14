<?php

$github = "https://github.com/tawsiftorabi/fanhub";

if(isset($_GET['data'])){
    if($_GET['data'] == 'web'){
        $webLink = htmlspecialchars((isset($_SERVER['HTTPS']) ? "http" : "http") . "://$_SERVER[HTTP_HOST]")."/app.php?view=web";
        shell_exec('start "" "'.$webLink.'"');
    }
    if($_GET['data'] == 'github'){
        shell_exec('start "" "'.$github.'"');
    }
}
?>
