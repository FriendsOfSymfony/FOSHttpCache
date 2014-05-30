sub vcl_deliver {
    # Add extra headers if debugging is enabled
    # In Varnish 4 the obj.hits counter behaviour has changed, so we use a
    # different method: if X-Varnish contains only 1 id, we have a miss, if it
    # contains more (and therefore a space), we have a hit.
    if (resp.http.x-varnish ~ " ") {
        set resp.http.x-cache = "HIT";
    } else {
        set resp.http.x-cache = "MISS";
    }
}
