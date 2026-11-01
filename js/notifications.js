(() => {
  const widgets = Array.from(document.querySelectorAll('[data-notification-api]'));
  if (!widgets.length) return;

  const iconFor = (type) => {
    if (type.includes('TEST_DRIVE')) return 'fa-car-side';
    if (type.includes('STOCK') || type.includes('CAR_')) return 'fa-car';
    if (type.includes('ORDER')) return 'fa-box';
    if (type.includes('PAYMENT')) return 'fa-credit-card';
    return 'fa-bell';
  };

  const relativeTime = (value) => {
    const date = new Date(value.replace(' ', 'T'));
    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
    if (!Number.isFinite(seconds)) return value;
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
    if (seconds < 172800) return 'Yesterday';
    return date.toLocaleString(undefined, {day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit'});
  };

  const post = async (widget, data) => {
    const body = new URLSearchParams({...data, scope: widget.dataset.notificationScope || 'customer', csrf: widget.dataset.notificationCsrf || ''});
    const response = await fetch(widget.dataset.notificationApi, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest'},
      body
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Notification action failed.');
    return result;
  };

  const renderItems = (container, notifications, widget) => {
    container.replaceChildren();
    if (!notifications.length) {
      const empty = document.createElement('div');
      empty.className = 'notification-empty';
      const icon = document.createElement('i');
      icon.className = 'fa-regular fa-bell';
      icon.setAttribute('aria-hidden', 'true');
      const title = document.createElement('strong');
      title.textContent = 'No notifications yet';
      const message = document.createElement('p');
      message.textContent = 'You’re all caught up.';
      empty.append(icon, title, message);
      container.append(empty);
      return;
    }
    notifications.forEach((notification) => {
      const item = document.createElement('article');
      item.className = `notification-item${Number(notification.is_read) ? '' : ' is-unread'}`;
      const link = document.createElement('a');
      link.className = 'notification-item-link';
      link.href = notification.url;
      const icon = document.createElement('span');
      icon.className = 'notification-item-icon';
      const iconElement = document.createElement('i');
      iconElement.className = `fa-solid ${iconFor(notification.type)}`;
      icon.append(iconElement);
      const copy = document.createElement('span');
      copy.className = 'notification-item-copy';
      const title = document.createElement('strong');
      title.textContent = notification.title;
      const message = document.createElement('span');
      message.textContent = notification.message;
      const time = document.createElement('time');
      time.dateTime = notification.created_at;
      time.textContent = relativeTime(notification.created_at);
      copy.append(title, message, time);
      link.append(icon, copy);
      item.append(link);
      if (!Number(notification.is_read)) {
        const mark = document.createElement('button');
        mark.className = 'notification-mark-read';
        mark.type = 'button';
        mark.textContent = 'Mark read';
        mark.addEventListener('click', async () => {
          try {
            await post(widget, {action: 'mark_read', id: String(notification.id)});
            await load(widget);
          } catch (error) {
            showError(container, error.message);
          }
        });
        item.append(mark);
      }
      container.append(item);
    });
  };

  const showError = (container, message) => {
    container.replaceChildren();
    const error = document.createElement('p');
    error.className = 'notification-error';
    error.textContent = message;
    container.append(error);
  };

  const load = async (widget) => {
    const panel = widget.querySelector('.notification-popover');
    const items = widget.querySelector('.notification-items') || widget.querySelector('[data-notification-results]');
    if (!items) return;
    const search = widget.querySelector('[data-notification-search]')?.value.trim() || '';
    const unread = widget.querySelector('[data-notification-unread]')?.checked ? '&unread=1' : '';
    const url = `${widget.dataset.notificationApi}?scope=${encodeURIComponent(widget.dataset.notificationScope || 'customer')}&action=list&limit=${widget.dataset.notificationPage ? '50' : '8'}&q=${encodeURIComponent(search)}${unread}`;
    try {
      const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', headers: {'X-Requested-With': 'XMLHttpRequest'}});
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'Notifications are temporarily unavailable.');
      renderItems(items, result.notifications || [], widget);
      widget.dataset.unreadCount = String(result.unread || 0);
      const badge = widget.querySelector('.notification-badge');
      if (badge) {
        badge.textContent = result.unread > 99 ? '99+' : String(result.unread || '');
        badge.hidden = !result.unread;
        widget.querySelector('.notification-trigger')?.setAttribute('aria-label', result.unread ? `Notifications, ${result.unread} unread` : 'Notifications');
      }
    } catch (error) {
      showError(items, error.message);
    }
  };

  widgets.forEach((widget) => {
    const trigger = widget.querySelector('.notification-trigger');
    const panel = widget.querySelector('.notification-popover');
    const markAll = widget.querySelector('.notification-mark-all');
    trigger?.addEventListener('click', async () => {
      if (panel) {
        const opening = panel.hidden;
        panel.hidden = !opening;
        trigger.setAttribute('aria-expanded', String(opening));
      }
      await load(widget);
    });
    markAll?.addEventListener('click', async () => {
      try {
        await post(widget, {action: 'mark_all'});
        await load(widget);
      } catch (error) {
        const items = widget.querySelector('.notification-items');
        if (items) showError(items, error.message);
      }
    });
    widget.querySelectorAll('[data-notification-search], [data-notification-unread]').forEach((control) => {
      control.addEventListener(control.matches('input[type="search"]') ? 'input' : 'change', () => load(widget));
    });
    if (!widget.dataset.notificationPage) {
      load(widget);
      window.setInterval(() => {
        if (document.visibilityState === 'visible') load(widget);
      }, 60000);
      document.addEventListener('click', (event) => {
        if (panel && !widget.contains(event.target)) {
          panel.hidden = true;
          trigger?.setAttribute('aria-expanded', 'false');
        }
      });
    } else {
      load(widget);
    }
  });
})();
