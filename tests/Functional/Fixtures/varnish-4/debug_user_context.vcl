sub vcl_deliver {
    set resp.http.X-HashCache = "MISS";

    if (resp.http.content-type ~ "application/vnd.fos.user-context-hash") {
        if (obj.hits > 0) {
            set req.http.X-HashCache = "HIT";
        }
    } elsif (req.http.X-HashCache) {
        set resp.http.X-HashCache = req.http.X-HashCache;
    }
}
