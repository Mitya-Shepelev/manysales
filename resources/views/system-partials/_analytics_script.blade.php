@if(isset($web_config['analytic_scripts']))
    @foreach($web_config['analytic_scripts'] as $analyticScript)
        @php
            // Sanitize script_id to prevent XSS - only allow alphanumeric, hyphens, and underscores
            $safeScriptId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $analyticScript['script_id'] ?? '');
        @endphp
        @if($safeScriptId && $analyticScript['type'] == 'meta_pixel')
            <!-- Meta Pixel -->
            <script>
                !function (f, b, e, v, n, t, s) {
                    if (f.fbq) return;
                    n = f.fbq = function () {
                        n.callMethod ?
                            n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                    };
                    if (!f._fbq) f._fbq = n;
                    n.push = n;
                    n.loaded = !0;
                    n.version = '2.0';
                    n.queue = [];
                    t = b.createElement(e);
                    t.async = !0;
                    t.src = v;
                    s = b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t, s)
                }(window, document, 'script',
                    'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ $safeScriptId }}');
                fbq('track', 'PageView');
            </script>
            <noscript>
                <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $safeScriptId }}&ev=PageView&noscript=1"/>
            </noscript>
            <!-- End Meta Pixel -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'linkedin_insight')
            <!-- LinkedIn Insight Tag -->
            <script type="text/javascript">
                _linkedin_partner_id = "{{ $safeScriptId }}";
                window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
                window._linkedin_data_partner_ids.push(_linkedin_partner_id);
                (function () {
                    var s = document.getElementsByTagName("script")[0];
                    var b = document.createElement("script");
                    b.type = "text/javascript";
                    b.async = true;
                    b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
                    s.parentNode.insertBefore(b, s);
                })();
            </script>
            <noscript>
                <img height="1" width="1" style="display:none;" alt=""
                     src="https://px.ads.linkedin.com/collect/?pid={{ $safeScriptId }}&fmt=gif"/>
            </noscript>
            <!-- End LinkedIn Insight Tag -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'tiktok_tag')
            <!-- TikTok Pixel -->
            <script>
                !function (w, d, t) {
                    w.TiktokAnalyticsObject = t;
                    var ttq = w[t] = w[t] || [];
                    ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie"];
                    ttq.setAndDefer = function (t, e) {
                        t[e] = function () {
                            t.push([e].concat(Array.prototype.slice.call(arguments, 0)))
                        }
                    };
                    for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
                    ttq.instance = function (t) {
                        for (var e = ttq._i[t] = [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]);
                        return e
                    };
                    ttq.load = function (e, n) {
                        var i = "https://analytics.tiktok.com/i18n/pixel/events.js";
                        ttq._i = ttq._i || {};
                        ttq._i[e] = [];
                        ttq._i[e]._u = i;
                        ttq._t = ttq._t || {};
                        ttq._t[e] = +new Date;
                        ttq._o = ttq._o || {};
                        ttq._o[e] = n || {};
                        var o = document.createElement("script");
                        o.type = "text/javascript";
                        o.async = !0;
                        o.src = i + "?sdkid=" + e + "&lib=" + t;
                        var a = document.getElementsByTagName("script")[0];
                        a.parentNode.insertBefore(o, a)
                    };
                    ttq.load('{{ $safeScriptId }}');
                    ttq.page();
                }(window, document, 'ttq');
            </script>
            <!-- End TikTok Pixel -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'snapchat_tag')
            <!-- Snapchat Pixel -->
            <script type='text/javascript'>
                (function (e, t, n) {
                    if (e.snaptr) return;
                    var a = e.snaptr = function () {
                        a.handleRequest ? a.handleRequest.apply(a, arguments) : a.queue.push(arguments)
                    };
                    a.queue = [];
                    var s = 'script';
                    r = t.createElement(s);
                    r.async = !0;
                    r.src = n;
                    var u = t.getElementsByTagName(s)[0];
                    u.parentNode.insertBefore(r, u);
                })(window, document,
                    'https://sc-static.net/scevent.min.js');
                snaptr('init', '{{ $safeScriptId }}');
                snaptr('track', 'PAGE_VIEW');
            </script>
            <noscript>
                <img height="1" width="1" style="display:none" alt=""
                     src="https://sc-static.net/scevent.min.js?id={{ $safeScriptId }}"/>
            </noscript>
            <!-- End Snapchat Pixel -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'twitter_tag')
            <!-- Twitter Universal Website Tag -->
            <script>
                !function (e, t, n, s, u, a) {
                    e.twq || (s = e.twq = function () {
                        s.exe ? s.exe.apply(s, arguments) : s.queue.push(arguments);
                    }, s.version = '1.1', s.queue = [], u = t.createElement(n), u.async = !0, u.src = 'https://static.ads-twitter.com/uwt.js',
                        a = t.getElementsByTagName(n)[0], a.parentNode.insertBefore(u, a))
                }(window, document, 'script');
                // Insert Twitter Pixel ID and Standard Event data below
                twq('init', '{{ $safeScriptId }}');
                twq('track', 'PageView');
            </script>
            <!-- End Twitter Universal Website Tag -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'pinterest_tag')
            <!-- Pinterest Tag -->
            <script>
                !function (e) {
                    if (!window.pintrk) {
                        window.pintrk = function () {
                            window.pintrk.queue.push(Array.prototype.slice.call(arguments))
                        };
                        var n = window.pintrk;
                        n.queue = [], n.version = "3.0";
                        var t = document.createElement("script");
                        t.async = !0, t.src = e;
                        var r = document.getElementsByTagName("script")[0];
                        r.parentNode.insertBefore(t, r)
                    }
                }("https://s.pinimg.com/ct/core.js");
                pintrk('load', '{{ $safeScriptId }}');
                pintrk('page');
            </script>
            <noscript>
                <img height="1" width="1" style="display:none;" alt=""
                     src="https://ct.pinterest.com/v3/?event=init&tid={{ $safeScriptId }}&noscript=1"/>
            </noscript>
            <!-- End Pinterest Tag -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'google_tag_manager')
            <!-- Google Tag Manager -->
            <script>(function (w, d, s, l, i) {
                    w[l] = w[l] || [];
                    w[l].push({
                        'gtm.start':
                            new Date().getTime(), event: 'gtm.js'
                    });
                    var f = d.getElementsByTagName(s)[0],
                        j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                    j.async = true;
                    j.src =
                        'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                    f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', '{{ $safeScriptId }}');</script>
            <noscript>
                <iframe src="https://www.googletagmanager.com/ns.html?id={{ $safeScriptId }}"
                        height="0" width="0" style="display:none;visibility:hidden"></iframe>
            </noscript>
            <!-- End Google Tag Manager -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'google_analytics')
            <!-- Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $safeScriptId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];

                function gtag() {
                    dataLayer.push(arguments);
                }

                gtag('js', new Date());
                gtag('config', '{{ $safeScriptId }}');
            </script>
            <!-- End Google Analytics -->
        @endif
        @if($safeScriptId && $analyticScript['type'] == 'vk_pixel')
            <!-- VK Pixel Code -->
            <script type="text/javascript">
                var _tmr = window._tmr || (window._tmr = []);
                _tmr.push({id: "{{ $safeScriptId }}", type: "pageView", start: (new Date()).getTime()});
                (function (d, w, id) {
                    if (d.getElementById(id)) return;
                    var ts = d.createElement("script"); ts.type = "text/javascript"; ts.async = true; ts.id = id;
                    ts.src = "https://top-fwz1.mail.ru/js/code.js";
                    var f = function () {var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ts, s);};
                    if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); }
                })(document, window, "tmr-code");
            </script>
            <noscript>
                <div><img src="https://top-fwz1.mail.ru/counter?id={{ $safeScriptId }};js=na" style="position:absolute;left:-9999px;" alt="" /></div>
            </noscript>
            <!-- /VK Pixel Code -->
        @endif

    @endforeach
@endif
