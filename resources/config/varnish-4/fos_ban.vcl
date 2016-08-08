/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

sub fos_ban_recv {

    if (req.method == "BAN") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }

        if (req.http.Cache-Tags) {
            ban("obj.http.Host ~ " + req.http.Host
                + " && obj.http.Url ~ " + req.http.Url
                + " && obj.http.content-type ~ " + req.http.Content-Type
                + " && obj.http.Cache-Tags ~ " + req.http.Cache-Tags
            );
        } else {
            ban("obj.http.Host ~ " + req.http.Host
                + " && obj.http.Url ~ " + req.http.Url
                + " && obj.http.content-type ~ " + req.http.Content-Type
            );
        }

        return (synth(200, "Banned"));
    }
}

sub fos_ban_backend_response {

    # Set ban-lurker friendly custom headers
    set beresp.http.Url = bereq.url;
    set beresp.http.Host = bereq.http.host;
}

sub fos_ban_deliver {

    # Keep ban-lurker headers only if debugging is enabled
    if (!resp.http.Cache-Debug) {
        # Remove ban-lurker friendly custom headers when delivering to client
        unset resp.http.Url;
        unset resp.http.Host;

        # Unset the tagged cache headers
        unset resp.http.Cache-Tags;
    }
}
