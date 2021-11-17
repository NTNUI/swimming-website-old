// Deprecated: Use fetch()
var getJSON = function(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.responseType = 'json';
    xhr.onload = function() {
        var status = xhr.status;
        if (status === 200) {
            callback(null, xhr.response);
        } else {
            callback(status, xhr.response);
        }
    };
    xhr.send();
};

// Add onLoad handlers to queue
function addLoadEvent(func) {
    var old_onload = window.onload;
    if (typeof window.onload != `function`) {
        window.onload = func;
    } else {
        window.onload = function() {
            if (old_onload) {
                old_onload();
            }
            func();
        }
    }
}

addLoadEvent(() => {
    document.getElementById("mobile_menu_button").addEventListener("click", function(e) {
        document.getElementById("menu_container").classList.add("visible");
    });
    document.getElementById("menu_container").addEventListener("click", function(e) {
        document.getElementById("menu_container").classList.remove("visible");
    });
});