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

# The purge in vcl_miss is necessary to purge all variants in the cases where
# you hit an object, but miss a particular variant.
sub vcl_miss {
    if (req.request == "PURGE") {
        purge;
        error 204 "Purged (Not in cache)";
    }
}
