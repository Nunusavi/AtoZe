(function () {
  const endpoint = '/admin/events.php';

  function sendEvent(type, data = {}) {
    fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        event_type: type,
        event_data: data
      })
    }).catch(() => { });
  }

  // Example: track clicks on elements with data-track attribute
  document.addEventListener('click', function (e) {
    const target = e.target.closest('[data-track]');
    if (target) {
      sendEvent('click', {
        tag: target.tagName,
        id: target.id || null,
        class: target.className || null,
        text: target.innerText?.slice(0, 100) || null
      });
    }
  });

  // Optional: form submission tracking
  document.addEventListener('submit', function (e) {
    const form = e.target.closest('form');
    if (form && form.hasAttribute('data-track-form')) {
      sendEvent('form_submit', {
        action: form.action || window.location.href,
        method: form.method || 'GET'
      });
    }
  });
})();
