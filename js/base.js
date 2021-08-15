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

// Needs to be tested
var postJSON = function(url, data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
    xhr.send(JSON.stringify(data));
    xhr.onloadend = function() {
        if (status === 200) {
            callback(null, xhr.response);
        } else {
            callback(status, xhr.response);
        }
    };
}

function addLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != `function`) {
        window.onload = func;
    } else {
        window.onload = function() {
            if (oldonload) {
                oldonload();
            }
            func();
        }
    }
}

addLoadEvent(() => {
    document.getElementById("menu_show").addEventListener("click", function(e) {
        document.getElementById("menu_container").classList.add("visible");
    });
    document.getElementById("menu_container").addEventListener("click", function(e) {
        document.getElementById("menu_container").classList.remove("visible");
    });
});