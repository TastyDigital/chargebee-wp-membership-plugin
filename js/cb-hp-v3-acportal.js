jQuery(document).ready(function () {

    var chargebeeInstance = Chargebee.init({
        site: cb_ac_button.cb_site


    });

    chargebeeInstance.setPortalSession(function () {


        var data = "action=portal_session_object";
        return axios.post(cb_ac_button.ajaxurl, data, {
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            }
        }).then(function (res) {

            if (res.data.good_to_go == "yeah") {
                return res.data.portal_obj;
            } else {

                if (res.data.msg == "no_cb_user_id") {
                    alert("No Chargebee Customer ID, Associated with your account. Contact Site Admin.");
                } else {
                    alert("Something Went Wrong, Please try again");
                }
                chargebeeInstance.closeAll();
            }


        });

    });

    jQuery("#cb-account-portal").click(function (e) {
        e.preventDefault();
        var is_logged_in = cb_ac_button.is_logged_in;
        if (!is_logged_in) {
            alert("You need to be logged in, to proceed further");
            return;
        }

        var cbPortal = chargebeeInstance.createChargebeePortal();
        cbPortal.open({
            // Called when customer portal is closed
            "close": function () {

            }
        });
    });


});