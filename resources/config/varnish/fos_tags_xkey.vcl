/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import xkey;

sub fos_tags_xkey_recv {
    if (req.method == "PURGEKEYS") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }

        # If neither of the headers are provided we return 400 to simplify detecting wrong configuration
        if (!req.http.xkey-purge && !req.http.xkey-softpurge) {
            return (synth(400, "Neither header XKey-Purge or XKey-SoftPurge set"));
        }

        # Based on provided header invalidate (purge) and/or expire (softpurge) the tagged content
        set req.http.n-gone = 0;
        set req.http.n-softgone = 0;
        if (req.http.xkey-purge) {
            set req.http.n-gone = xkey.purge(req.http.xkey-purge);
        }

        if (req.http.xkey-softpurge) {
            set req.http.n-softgone = xkey.softpurge(req.http.xkey-softpurge);
        }

        return (synth(200, "Purged "+req.http.n-gone+" objects, expired "+req.http.n-softgone+" objects"));
    }
}

sub fos_tags_xkey_deliver {
    if (!resp.http.X-Cache-Debug) {
        // Remove tag headers when delivering to non debug client
        unset resp.http.xkey;
    }
}
