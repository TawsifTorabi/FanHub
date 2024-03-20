
//Function To Launch Page in Host Browser Window
function launchExternal(page){
    if(page == 'web'){
        makeAjaxRequest("external_launcher.php?data=web ", function (response) {
            return response;
        });
    }
}

//JS Function for Responsive Navigation Bar
function resNav() {
    var x = document.getElementById("myTopnav");
    if (x.className === "topnav") {
      x.className += " responsive";
    } else {
      x.className = "topnav";
    }
  }




