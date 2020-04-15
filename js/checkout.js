(function($) {
    $form_id = "CustomerDetailsForm_CustomerForm_";
    var check = document.getElementById($form_id+"DuplicateDelivery");

    if (check != null) {
        $(document).ready(function() {
            $('#CustomerDetailsForm_CustomerForm').validate();
        });

        check.onclick = switchDelivery;

        function switchDelivery() {
            if (check.checked == true) {
                if (document.getElementById($form_id+"DeliveryFields_Holder")) {
                    document.getElementById($form_id+"DeliveryFields_Holder").style.display = "none";
                }
                if(document.getElementById($form_id+"SavedShipping_Holder")) {
                    document.getElementById($form_id+"SavedShipping_Holder").style.display = "none";
                }
            } else {
                if (document.getElementById($form_id+"DeliveryFields_Holder")) {
                    document.getElementById($form_id+"DeliveryFields_Holder").style.display = "block";
                }
                if(document.getElementById($form_id+"SavedShipping_Holder")) {
                    document.getElementById($form_id+"SavedShipping_Holder").style.display = "block";
                }
            }
        }

        switchDelivery();
    }    

    var form = document.getElementById("Form_GatewayForm");
    if (form != null && form.length) {
        var button = document.getElementById("Form_GatewayForm_action_doContinue");   
        if (button != null) {
            button.style.position = "absolute";
            button.style.left = "-10000px";
        }
        var rad = form.PaymentMethodID;
        var prev = null;
        for(var i = 0; i < rad.length; i++) {
            if (rad[i].hasAttribute('checked')) {
                prev = rad[i];
            }
            rad[i].onclick = function() {
                if(this !== prev) {
                    form.submit();
                }
            };
        }
    }

    var payment_form = document.getElementById("Form_PaymentForm");
    if (payment_form != null && payment_form.length) {
        payment_form.addEventListener("submit", function(e) {
            var button = document.getElementById('Form_PaymentForm_action_doSubmit');
            button.disabled = 'disabled';
            var spinner = document.createElement("i");
            spinner.classList.add('fas');
            spinner.classList.add('fa-spinner');
            spinner.classList.add('fa-pulse');
            button.insertAdjacentElement(
                "beforeend",
                spinner
            );
            if (payment_form.classList.contains('disabled')) {
                e.preventdefault();
            } else {
                payment_form.classList.add('disabled');
            }
        });
    }

}(jQuery));