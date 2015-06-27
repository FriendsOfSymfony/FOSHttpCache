/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

sub fos_purge_recv {
    if (req.request == "PURGE") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed";
        }
        return (lookup);
    }
}

sub fos_purge_hit {
    if (req.request == "PURGE") {
        purge;
        error 204 "Purged";
    }
}

# The purge in vcl_miss is necessary to purge all variants in the cases where
# you hit an object, but miss a particular variant.
sub fos_purge_miss {
    if (req.request == "PURGE") {
        purge;
        error 204 "Purged (Not in cache)";
    }
}
