sub vcl_recv {

    if (req.request == "BAN") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed.";
        }

        if (req.http.x-cache-tags) {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        error 200 "Banned";
    }
}

sub vcl_fetch {

    # Set ban-lurker friendly custom headers
    set beresp.http.x-url = req.url;
    set beresp.http.x-host = req.http.host;
}

sub vcl_deliver {

    # Keep ban-lurker headers only if debugging is enabled
    if (!resp.http.x-cache-debug) {
        # Remove ban-lurker friendly custom headers when delivering to client
        unset resp.http.x-url;
        unset resp.http.x-host;
    }
}
