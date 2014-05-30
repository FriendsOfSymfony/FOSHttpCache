sub vcl_recv {
    if (req.request == "PURGE") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed";
        }
        return (lookup);
    }
}

sub vcl_hit {
    if (req.request == "PURGE") {
        purge;
        error 204 "Purged";
    }
}

sub vcl_miss {
    if (req.request == "PURGE") {
        purge;
        error 204 "Purged (Not in cache)";
    }
}
