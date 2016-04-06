/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
C{
    #include <stdlib.h>
}C

/**
 * Read a custom TTL header for the time to live information, to be used
 * instead of s-maxage.
 */
sub fos_custom_ttl_backend_response {
    if (beresp.http.X-Reverse-Proxy-TTL) {
        /*
         * Note that there is a ``beresp.ttl`` field in VCL but unfortunately
         * it can only be set to absolute values and not dynamically. Thus we
         * have to resort to an inline C code fragment.
         *
         * As of Varnish 4.0, inline C is disabled by default. To use this
         * feature, you need to add `-p vcc_allow_inline_c=on` to your Varnish
         * startup command.
         */
        C{
            const char *ttl;
            const struct gethdr_s hdr = { HDR_BERESP, "\024X-Reverse-Proxy-TTL:" };
            ttl = VRT_GetHdr(ctx, &hdr);
            VRT_l_beresp_ttl(ctx, atoi(ttl));
        }C

        unset beresp.http.X-Reverse-Proxy-TTL;
    }
}
