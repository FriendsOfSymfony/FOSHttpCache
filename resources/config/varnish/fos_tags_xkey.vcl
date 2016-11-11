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

        // If tag invalidation api allows caller to specify expire/purge in the future,
        // then adjust this to be able to handle both headers in same request.
        if (req.http.xkey-purge) {
            set req.http.n-gone = xkey.purge(req.http.xkey-purge);
        } elseif (req.http.xkey-softpurge) {
            set req.http.n-gone = xkey.softpurge(req.http.xkey-softpurge);
        } else {
            return (synth(400));
        }

        return (synth(200, "Invalidated "+req.http.n-gone+" objects"));
    }
}
