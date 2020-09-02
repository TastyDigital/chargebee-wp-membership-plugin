jQuery(document).ready(function () {
    var chargebeeInstanceH = Chargebee.init({
        site: cb_sub_button.cb_site
    });

    jQuery(".cb-subscribe").click(function (e) {
        e.preventDefault();
        var is_logged_in = cb_sub_button.is_logged_in;
        if (!is_logged_in) {
            alert("You need to be logged in, to proceed further");
            return;
        }
        
        var product_id = jQuery(this).data("cbproduct");
        chargebeeInstanceH.openCheckout({
            hostedPage: function () {

                var data = "action=hosted_page_object&plan=" + product_id;
                return axios.post(cb_sub_button.ajaxurl, data, {
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    }
                }).then(function (res) {
                    if (res.data.good_to_go == "yeah") {

                        return res.data.hosted_page;

                    } else {

                        if (res.data.msg == "no_cb_user_id") {
                            alert("No Chargebee Customer ID, Associated with your account. Contact Site Admin.");
                        } else {
                            alert("Something Went Wrong, Please try again");
                        }
                        chargebeeInstanceH.closeAll();
                    }
                });

            },
            loaded: function () {
                //called when loaded
            },
            success: function (hostedPageId) {
                //called on sucess with hosted page id

            },
            error: function (er) {

            },
            close: function () {

            }
        });
    });


});