include "debug.vcl";
include "purge.vcl";
include "refresh.vcl";
include "ban.vcl";

backend default {
    .host = "localhost";
    .port = "8080";
}

acl invalidators {
    "localhost";
}