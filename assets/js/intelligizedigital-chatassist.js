/**
 * Intelligize ChatAssist Front-end JavaScript - Extended Features
 */
(function($) {
    'use strict';

    let $container, $button, $popup, $closeBtn, $iframe, $loading;
    let isWidgetOpen = false;
    let hasLoaded = false;
    let widgetVisible = false;
    let scrollTriggered = false;
    let exitIntentTriggered = false;
    let proactiveShown = false;
    let unreadCount = 0;

    function getData() {
        return typeof intelligizedigitalChatAssistData !== 'undefined' ? intelligizedigitalChatAssistData : {};
    }

    function initChatWidget() {
        $container = $('#intelligizedigital-chatassist-container');
        $button = $('#intelligizedigital-chatassist-button');
        $popup = $('#intelligizedigital-chatassist-popup');
        $closeBtn = $('#intelligizedigital-chatassist-close');
        $iframe = $('#intelligizedigital-chatassist-iframe');
        $loading = $('#intelligizedigital-chatassist-loading');

        if (!$container.length) return;

        const data = getData();

        // Apply theme
        if (data.theme === 'dark') {
            $container.addClass('intelligizedigital-chatassist-theme-dark');
        }

        // Initially hide button if delay/scroll/exit-intent
        if (data.delaySeconds > 0 || data.scrollDepth > 0 || data.exitIntent) {
            $container.addClass('intelligizedigital-chatassist-delayed');
            $button.hide();
        }

        // Delay before showing button
        if (data.delaySeconds > 0) {
            setTimeout(function() {
                showButton();
            }, data.delaySeconds * 1000);
        } else if (data.scrollDepth === 0 && !data.exitIntent) {
            showButton();
        }

        // Scroll depth trigger
        if (data.scrollDepth > 0) {
            $(window).on('scroll', function() {
                if (scrollTriggered) return;
                const scrollPercent = ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100;
                if (scrollPercent >= data.scrollDepth) {
                    scrollTriggered = true;
                    showButton();
                }
            });
        }

        // Exit intent trigger
        if (data.exitIntent) {
            $(document).on('mouseout', function(e) {
                if (exitIntentTriggered) return;
                if (e.clientY < 10 && e.relatedTarget === null) {
                    exitIntentTriggered = true;
                    showButton();
                }
            });
        }

        // Proactive message
        if (data.proactiveMessage) {
            setTimeout(function() {
                if (!proactiveShown && !isWidgetOpen) {
                    proactiveShown = true;
                    showProactiveMessage(data.proactiveMessage);
                }
            }, (data.proactiveDelay || 10) * 1000);
        }

        // GDPR consent
        if (data.gdprConsent) {
            $('#intelligizedigital-chatassist-consent-accept').on('click', function() {
                document.cookie = 'intelligizedigital_chatassist_consent=accepted; path=/; max-age=31536000';
                $('#intelligizedigital-chatassist-consent-banner').fadeOut(300);
                location.reload();
            });
            $('#intelligizedigital-chatassist-consent-decline').on('click', function() {
                document.cookie = 'intelligizedigital_chatassist_consent=declined; path=/; max-age=86400';
                $('#intelligizedigital-chatassist-consent-banner').fadeOut(300);
            });
        }

        $button.on('click', function(e) {
            if (data.preChatForm && $('#intelligizedigital-chatassist-prechat-wrapper').length && !$('#intelligizedigital-chatassist-prechat').hasClass('submitted')) {
                e.preventDefault();
                isWidgetOpen = true;
                $popup.css('display', 'flex');
                $container.addClass('intelligizedigital-chatassist-open');
                $popup.addClass('intelligizedigital-chatassist-popup-open');
                $popup.find('.intelligizedigital-chatassist-frame-container').addClass('intelligizedigital-chatassist-hidden').hide();
                $popup.find('.intelligizedigital-chatassist-prechat-wrapper').removeClass('intelligizedigital-chatassist-hidden').show();
                return;
            }
            toggleChatWidget();
        });

        $closeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeChatWidget();
        });

        $(document).on('click', '#intelligizedigital-chatassist-prechat-submit', function() {
            const $form = $('#intelligizedigital-chatassist-prechat-form');
            if ($form.length && $form[0].checkValidity()) {
                const formData = { name: $form.find('[name="name"]').val() || '', email: $form.find('[name="email"]').val() || '', phone: $form.find('[name="phone"]').val() || '' };
                $('#intelligizedigital-chatassist-prechat').addClass('submitted');
                $popup.find('.intelligizedigital-chatassist-prechat-wrapper').addClass('intelligizedigital-chatassist-hidden').hide();
                $popup.find('.intelligizedigital-chatassist-frame-container').removeClass('intelligizedigital-chatassist-hidden').show();
                hasLoaded = false;
                let chatUrl = $iframe.attr('data-src');
                if (chatUrl && data.preChatToUrl && (formData.name || formData.email || formData.phone)) {
                    const sep = chatUrl.indexOf('?') >= 0 ? '&' : '?';
                    const params = [];
                    if (formData.name) params.push('name=' + encodeURIComponent(formData.name));
                    if (formData.email) params.push('email=' + encodeURIComponent(formData.email));
                    if (formData.phone) params.push('phone=' + encodeURIComponent(formData.phone));
                    chatUrl += sep + params.join('&');
                }
                if (chatUrl) {
                    $loading.show();
                    $iframe.attr('src', chatUrl);
                }
                if (data.analyticsEnabled) {
                    trackEvent('widget_opened');
                    try { document.cookie = 'intelligizedigital_chatassist_opened=1;path=/;max-age=86400;samesite=lax'; } catch (e) {}
                }
                if (data.ajaxUrl && data.nonce) {
                    $.post(data.ajaxUrl, { action: 'intelligizedigital_chatassist_track', event_type: 'lead_captured', nonce: data.nonce, name: formData.name, email: formData.email, phone: formData.phone });
                }
            }
        });

        $(document).on('click', function(event) {
            if (isWidgetOpen && !$(event.target).closest($popup).length && !$(event.target).closest($button).length) {
                closeChatWidget();
            }
        });

        $popup.on('click', function(e) { e.stopPropagation(); });

        $(document).on('keydown', function(event) {
            if (isWidgetOpen && event.key === 'Escape') {
                closeChatWidget();
                event.preventDefault();
            }
        });

        $iframe.on('load', function() {
            hasLoaded = true;
            $loading.fadeOut(300);
            if (data.zoom) applyZoomToIframe(data.zoom);
        });

        window.addEventListener('message', function(e) {
            if (!e.data || !e.data.type) return;
            if (e.data.type === 'intelligizedigital_chatassist_unread' && typeof e.data.count === 'number') {
                unreadCount = e.data.count;
                if (data.unreadBadge) updateUnreadBadge();
            }
            if (e.data.type === 'intelligizedigital_chatassist_new_message' && data.soundEnabled) {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = 800;
                    gain.gain.value = 0.1;
                    osc.start();
                    setTimeout(function() { osc.stop(); }, 100);
                } catch (err) {}
                $(document).trigger('intelligizedigital_chatassist_new_message');
            }
            if (e.data.type === 'intelligizedigital_chatassist_message_sent') {
                if (data.analyticsEnabled) trackEvent('message_sent');
                $(document).trigger('intelligizedigital_chatassist_message_sent');
            }
        });

        if (data.heatmapEnabled) {
            let lastScroll = 0;
            $(window).on('scroll', function() {
                const h = $(document).height() - $(window).height();
                if (h <= 0) return;
                const pct = Math.round(($(window).scrollTop() / h) * 100);
                if (pct > lastScroll && [25, 50, 75, 100].indexOf(pct) >= 0) {
                    lastScroll = pct;
                    if (data.ajaxUrl && data.nonce) {
                        $.post(data.ajaxUrl, { action: 'intelligizedigital_chatassist_track', event_type: 'heatmap', nonce: data.nonce, heatmap_type: 'scroll', heatmap_value: pct });
                    }
                }
            });
            var clickThrottle = 0;
            $(document).on('click', function(e) {
                if ($(e.target).closest('#intelligizedigital-chatassist-container').length) return;
                if (Date.now() - clickThrottle < 500) return;
                clickThrottle = Date.now();
                var x = Math.round((e.clientX / window.innerWidth) * 100);
                var y = Math.round((e.clientY / window.innerHeight) * 100);
                var sel = (e.target.tagName + (e.target.id ? '#' + e.target.id : '') + (e.target.className && typeof e.target.className === 'string' ? '.' + e.target.className.split(' ')[0] : '')).substring(0, 50);
                var val = x + ',' + y + '|' + sel;
                if (data.ajaxUrl && data.nonce) {
                    $.post(data.ajaxUrl, { action: 'intelligizedigital_chatassist_track', event_type: 'heatmap', nonce: data.nonce, heatmap_type: 'click', heatmap_value: val });
                }
            });
        }

        if (data.layout === 'collapsible') {
            $container.addClass('intelligizedigital-chatassist-collapsible');
        }

        if (data.abVariant && data.abVariant !== 'control' && !document.cookie.match(/intelligizedigital_chatassist_variant=/)) {
            document.cookie = 'intelligizedigital_chatassist_variant=' + data.abVariant + ';path=/;max-age=2592000';
        }

        if (data.typingIndicator) {
            $popup.find('.intelligizedigital-chatassist-frame-container').prepend('<div class="intelligizedigital-chatassist-typing-indicator intelligizedigital-chatassist-hidden"><span></span><span></span><span></span></div>');
        }

        if (window.innerWidth < 480) {
            $container.addClass('intelligizedigital-chatassist-mobile');
        }
        $(window).on('resize', function() {
            $container.toggleClass('intelligizedigital-chatassist-mobile', window.innerWidth < 480);
        });

        // Trigger open from external (WooCommerce, CF7)
        $(document).on('intelligizedigital_chatassist_open', function() {
            showButton();
            setTimeout(function() {
                if (!$popup.hasClass('intelligizedigital-chatassist-popup-open')) {
                    openChatWidget();
                }
            }, 300);
        });

        // Conversion tracking - fires when user triggers jQuery(document).trigger('intelligizedigital_chatassist_conversion')
        $(document).on('intelligizedigital_chatassist_conversion', function() {
            if (getData().analyticsEnabled) trackEvent('conversion');
        });

        // Push notification subscription (when chat opens)
        $(document).on('intelligizedigital_chatassist_open', function() {
            if (window.intelligizedigitalChatAssistPush && window.intelligizedigitalChatAssistPush.reg && !localStorage.getItem('intelligizedigital_chatassist_push_asked')) {
                setTimeout(function() { trySubscribePush(); }, 2000);
            }
        });
    }

    function trySubscribePush() {
        if (!window.intelligizedigitalChatAssistPush || !window.intelligizedigitalChatAssistPush.vapid) return;
        var p = window.intelligizedigitalChatAssistPush;
        if (!('PushManager' in window) || !('Notification' in window)) return;
        if (Notification.permission === 'granted') return;
        if (Notification.permission === 'denied') return;
        localStorage.setItem('intelligizedigital_chatassist_push_asked', '1');
        Notification.requestPermission().then(function(perm) {
            if (perm !== 'granted') return;
            return p.reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(p.vapid) });
        }).then(function(sub) {
            if (sub) $.post(p.ajaxUrl, { action: 'intelligizedigital_chatassist_push_subscribe', subscription: JSON.stringify(sub.toJSON()), nonce: p.nonce });
        }).catch(function() {});
    }
    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }

    function showButton() {
        if (!$button) return;
        $button.fadeIn(300);
        widgetVisible = true;
        $container.removeClass('intelligizedigital-chatassist-delayed');
    }

    function showProactiveMessage(msg) {
        if (!$container.length) return;
        const $bubble = $('<div class="intelligizedigital-chatassist-proactive-bubble">' + msg + '</div>');
        $container.append($bubble);
        setTimeout(function() {
            $bubble.addClass('visible');
        }, 100);
        setTimeout(function() {
            $bubble.removeClass('visible');
            setTimeout(function() { $bubble.remove(); }, 300);
        }, 5000);
    }

    function toggleChatWidget() {
        if (isWidgetOpen) closeChatWidget();
        else openChatWidget();
    }

    function openChatWidget() {
        $popup.css('display', 'flex');
        $container.addClass('intelligizedigital-chatassist-open');
        isWidgetOpen = true;

        const data = getData();
        if (data.analyticsEnabled) {
            trackEvent('widget_opened');
            try { document.cookie = 'intelligizedigital_chatassist_opened=1;path=/;max-age=86400;samesite=lax'; } catch (e) {}
        }

        if (data.unreadBadge) {
            unreadCount = 0;
            updateUnreadBadge();
        }

        if (!hasLoaded) {
            const chatUrl = $iframe.attr('data-src');
            if (chatUrl) {
                $loading.show();
                $iframe.attr('src', chatUrl);
            }
        } else if (data.zoom) {
            applyZoomToIframe(data.zoom);
        }

        setTimeout(function() {
            $popup.addClass('intelligizedigital-chatassist-popup-open');
        }, 10);
    }

    function closeChatWidget() {
        $popup.removeClass('intelligizedigital-chatassist-popup-open');
        $container.removeClass('intelligizedigital-chatassist-open');

        const data = getData();
        if (data.analyticsEnabled) trackEvent('widget_closed');

        setTimeout(function() {
            $popup.css('display', 'none');
            isWidgetOpen = false;
        }, 300);
    }

    function trackEvent(eventType, extra) {
        const data = getData();
        if (!data.ajaxUrl || !data.nonce) return;
        const postData = {
            action: 'intelligizedigital_chatassist_track',
            event_type: eventType,
            nonce: data.nonce
        };
        if (data.abVariant && data.abVariant !== 'control') {
            postData.ab_variant = data.abVariant;
        }
        if (extra && typeof extra === 'object') {
            $.extend(postData, extra);
        }
        $.ajax({
            url: data.ajaxUrl,
            type: 'POST',
            data: postData,
            error: function() {}
        });
    }

    function updateUnreadBadge() {
        const $badge = $('.intelligizedigital-chatassist-unread-badge');
        if (unreadCount > 0) {
            $badge.text(unreadCount > 99 ? '99+' : unreadCount).removeClass('intelligizedigital-chatassist-hidden').show();
        } else {
            $badge.addClass('intelligizedigital-chatassist-hidden').hide();
        }
    }

    function applyZoomToIframe(zoomLevel) {
        try {
            const scale = parseInt(zoomLevel, 10) / 100;
            if (isNaN(scale) || scale <= 0) return;
            $iframe.css({
                'transform': 'scale(' + scale + ')',
                'transform-origin': 'top left',
                'width': (100/scale) + '%',
                'height': (100/scale) + '%'
            });
            $iframe.parent().css({ 'overflow': 'hidden', 'height': '100%' });
        } catch (e) {}
    }

    $(document).ready(function() {
        initChatWidget();
    });

})(jQuery);
