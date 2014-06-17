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

# The purge in vcl_miss is necessary to purge all variants in the cases where
# you hit an object, but miss a particular variant.
sub vcl_miss {
    if (req.method == "PURGE") {
        purge;
        return (synth(204, "Purged (Not in cache)"));
    }
}
