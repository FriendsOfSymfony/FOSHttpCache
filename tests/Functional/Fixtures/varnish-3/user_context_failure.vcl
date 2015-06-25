include "debug.vcl";
include "debug_user_context.vcl";
include "../../../../config/varnish-3/fos_user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_recv {
    if (req.restarts == 0
        && (req.http.cookie || req.http.authorization)
        && (req.request == "GET" || req.request == "HEAD")
    ) {
        set req.http.X-Cache-Hash = "failure";
    }

    call fos_user_context_recv;
}

sub vcl_fetch {
    call fos_user_context_fetch;
}

sub vcl_deliver {
    call fos_user_context_deliver;
}