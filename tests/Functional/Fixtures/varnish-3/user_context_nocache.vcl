include "debug.vcl";
include "debug_user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

include "user_context.vcl";
