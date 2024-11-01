window.addEventListener('message', function (e) {
    var $iframe = jQuery('#takIframeId');
    var eventName = e.data[0];
    var data = e.data[1];
    //console.log(e.data);
    switch (eventName) {
        case 'FrameHeight':
            $iframe.height(data);
            //var iFrame = document.getElementById('takIframeId');
            //if (iFrame) {
            //    iFrame.contentWindow.postMessage({ "sendHeighttoContainer": "sendCssOverRides", d: tak_data.styleoverrides }, "*");
            //}
            document.getElementById("comments").style.height = data + "px";
            break;
        case 'PostMetaData':
            updatePostMetaData(e);
    }
}, false);
function updatePostMetaData(e) {
    if (e.data[1] != tak_data._wc_review_count_client) {
        jQuery.ajax({
            url: tak_data.tak_ajax_url,
            type: 'post',
            data: {
                action: 'tak_ajax_meta_update',
                tak_update_nonce: tak_data.tak_update_nonce,
                tak_pid: tak_data.tak_pid,
                _wc_review_count_client: e.data[1],
                _wc_rating_count_client: JSON.stringify(e.data[2]),
                _wc_average_rating_client: e.data[3]
            },
            success: function (response) {
            },
            error: function (response) {
            }
        });
    }
}
document.addEventListener('touchmove', scrollHandler, true);
var wait = false;
function scrollHandler(e) {
    if (!e === 'undefined') {
        e.preventDefault();
    }
    if (!wait) {
        wait = true;
        setTimeout(function () {
            wait = false;
            var iFrame = document.getElementById('takIframeId');
            if (iFrame) {
                iFrame.contentWindow.postMessage({ "sendHeighttoContainer": "sendHeighttoContainer" }, "*");
            }
        }, 1000);
    }
};
window.addEventListener('load', function () {
    let takWoocommerceReviewLink = document.getElementById('tak-woocommerce-review-link');
    takWoocommerceReviewLink.addEventListener('click', function (e) {
        e.stopPropagation();
        jQuery('#tab-title-reviews a').trigger("click");
    });
});