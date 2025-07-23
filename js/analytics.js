(function () {
    const ANALYTICS_ENDPOINT = "/Admin/api/track";
    const getCookie = (name) => document.cookie.split('; ').find(row => row.startsWith(name + '='))?.split('=')[1];
    const setCookie = (name, value, days = 365) => {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = `${name}=${value}; expires=${expires}; path=/`;
    };

    const uuid = () => '_' + Math.random().toString(36).substr(2, 9);

    const anonymous_id = getCookie("anon_id") || uuid();
    setCookie("anon_id", anonymous_id);

    const startTime = performance.now();
    const scrollDepth = { '25': false, '50': false, '75': false, '90': false };
    let clickedElements = [];

    // Record scroll depth
    window.addEventListener("scroll", () => {
        const scroll = window.scrollY + window.innerHeight;
        const height = document.body.scrollHeight;
        const percent = Math.floor((scroll / height) * 100);

        if (percent >= 25 && !scrollDepth["25"]) scrollDepth["25"] = true;
        if (percent >= 50 && !scrollDepth["50"]) scrollDepth["50"] = true;
        if (percent >= 75 && !scrollDepth["75"]) scrollDepth["75"] = true;
        if (percent >= 90 && !scrollDepth["90"]) scrollDepth["90"] = true;
    });

    // Capture click events
    document.addEventListener("click", (e) => {
        const tag = e.target.tagName.toLowerCase();
        const id = e.target.id || '';
        const text = e.target.innerText?.slice(0, 30) || '';
        clickedElements.push({ tag, id, text });
    });

    // Before unload, send data
    window.addEventListener("beforeunload", () => {
        const endTime = performance.now();
        const session_duration = Math.round((endTime - startTime) / 1000); // in seconds
        const source = new URLSearchParams(window.location.search).get("utm_source") || document.referrer || "direct";
        const medium = new URLSearchParams(window.location.search).get("utm_medium") || "none";

        navigator.sendBeacon(ANALYTICS_ENDPOINT, JSON.stringify({
            timestamp: new Date().toISOString(),
            url: window.location.pathname,
            referrer: document.referrer,
            utm_source: source,
            utm_medium: medium,
            page_load_time: Math.round(performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart),
            anonymous_id,
            user_id: null,
            session_duration,
            scroll_depth: scrollDepth,
            click_events: clickedElements
        }));
    });
})();
