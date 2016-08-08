By default, the proxy server looks at the ``s-maxage`` instruction in the
``Cache-Control`` header to know for how long it should cache a page. But the
``Cache-Control`` header is also sent to the client. Any caches on the Internet,
for example the Internet provider or from a cooperate network might look at
``s-maxage`` and cache the page. This can be a problem, notably when you do
:doc:`explicit cache invalidation </cache-invalidator>`. In that
scenario, you want your proxy server to keep a page in cache for a long time,
but caches outside your control must not keep the page for a long duration.

One option could be to set a high ``s-maxage`` for the proxy and simply rewrite
the response to remove or reduce the ``s-maxage``. This is not a good solution
however, as you start to duplicate your caching rule definitions.

The solution to this issue provided here is to use a separate, different header
called ``X-Reverse-Proxy-TTL`` that controls the TTL of the proxy server to
keep ``s-maxage`` for other proxies. Because this is not a standard feature,
you need to add configuration to your proxy server.
