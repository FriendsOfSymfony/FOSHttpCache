sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }
        return (hash);
    }
}

sub vcl_hit {
    if (req.method == "PURGE") {
        purge;
        return (synth(204, "Purged"));
    }
}

sub vcl_miss {
    if (req.method == "PURGE") {
        purge;
        return (synth(204, "Purged (Not in cache)"));
    }
}
