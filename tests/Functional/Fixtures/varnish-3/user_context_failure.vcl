include "debug.vcl";
include "debug_user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_recv {
    if (req.restarts == 0
        && (req.http.cookie || req.http.authorization)
        && (req.request == "GET" || req.request == "HEAD")
    ) {
        set req.http.x-cache-hash = "failure";
    }
}

include "user_context.vcl";
