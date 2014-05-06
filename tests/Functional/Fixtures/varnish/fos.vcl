include "debug.vcl";
include "purge.vcl";
include "refresh.vcl";
include "ban.vcl";
include "user_context.vcl";

backend default {
    .host = "localhost";
    .port = "8080";
}

acl invalidators {
    "localhost";
}