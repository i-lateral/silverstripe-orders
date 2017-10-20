
$check = document.getElementById("DuplicateDelivery");
    
$check.onclick = switchDelivery;

function switchDelivery() {
    if ($check.checked == true) {
        if (document.getElementById("DeliveryFields_Holder")) {
            document.getElementById("DeliveryFields_Holder").style.display = "none";
        }
        if(document.getElementById("SavedShipping_Holder")) {
            document.getElementById("SavedShipping_Holder").style.display = "none";
        }
    } else {
        if (document.getElementById("DeliveryFields_Holder")) {
            document.getElementById("DeliveryFields_Holder").style.display = "block";
        }
        if(document.getElementById("SavedShipping_Holder")) {
            document.getElementById("SavedShipping_Holder").style.display = "block";
        }
    }
}

switchDelivery();

