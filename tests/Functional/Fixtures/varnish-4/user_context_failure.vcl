vcl 4.0;

include "debug.vcl";
include "debug_user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_recv {
    if (req.restarts == 0
        && (req.http.cookie || req.http.authorization)
        && (req.method == "GET" || req.method == "HEAD")
    ) {
        set req.http.x-cache-hash = "failure";
    }
}

include "user_context.vcl";
