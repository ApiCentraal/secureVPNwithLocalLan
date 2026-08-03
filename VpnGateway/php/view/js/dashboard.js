// @since  2026-04-24 — AJAX dashboard controller; status polling (6 s), log polling (3.5 s), form submission
(function () {
    'use strict';

    var config = window.VPN_APP_CONFIG || {};

    var refs = {
        form: document.getElementById('vpnSwitchForm'),
        connectionList: document.getElementById('connectionList'),
        alert: document.getElementById('appAlert'),
        wanWarningBanner: document.getElementById('wanWarningBanner'),
        logOutput: document.getElementById('vpnLogOutput'),
        applyButton: document.getElementById('applySelectionButton'),
        refreshStatusButton: document.getElementById('refreshStatusButton'),
        refreshLogButton: document.getElementById('refreshLogButton'),
        activeState: document.getElementById('status-active-state'),
        routeMode: document.getElementById('status-route-mode'),
        currentConnection: document.getElementById('status-current-connection'),
        wanUplink: document.getElementById('status-wan-uplink'),
        ipForward: document.getElementById('status-ip-forward'),
        updatedAt: document.getElementById('status-updated-at'),
        cardActiveState: document.getElementById('card-active-state'),
        cardRouteMode: document.getElementById('card-route-mode'),
        cardWanUplink: document.getElementById('card-wan-uplink'),
        cardIpForward: document.getElementById('card-ip-forward')
    };

    var isStatusLoading = false;
    var isLogLoading = false;
    var alertTimer = null;

    function formatTitle(value) {
        if (!value) {
            return 'Unknown';
        }

        return String(value)
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
    }

    function formatRouteMode(value) {
        if (value === 'vpn') {
            return 'VPN Tunnel';
        }

        if (value === 'local') {
            return 'LAN Only';
        }

        return 'Stopped';
    }

    function formatWanState(isReachable, publicIpAddress) {
        if (!isReachable) {
            return 'Offline';
        }

        if (publicIpAddress) {
            return 'Online · ' + publicIpAddress;
        }

        return 'Online';
    }

    function formatDate(isoDate) {
        if (!isoDate) {
            return 'Unknown';
        }

        var parsed = new Date(isoDate);
        if (isNaN(parsed.getTime())) {
            return 'Unknown';
        }

        return parsed.toLocaleString();
    }

    function setCardTone(card, tone) {
        if (!card) {
            return;
        }

        card.setAttribute('data-tone', tone || 'neutral');
    }

    function hideAlert() {
        if (!refs.alert) {
            return;
        }

        refs.alert.className = 'app-alert is-hidden';
        refs.alert.textContent = '';
    }

    function showAlert(message, tone) {
        if (!refs.alert) {
            return;
        }

        refs.alert.className = 'app-alert';
        refs.alert.classList.add(tone === 'error' ? 'error' : (tone === 'success' ? 'success' : 'info'));
        refs.alert.textContent = message;

        if (alertTimer) {
            clearTimeout(alertTimer);
        }

        if (tone !== 'error') {
            alertTimer = setTimeout(hideAlert, 5000);
        }
    }

    function updateWanWarning(state) {
        if (!refs.wanWarningBanner) {
            return;
        }

        var isActive = String(state.activeState || '').toLowerCase() === 'active';
        var internetReachable = Boolean(state.internetReachable);

        if (isActive && !internetReachable) {
            refs.wanWarningBanner.classList.remove('is-hidden');
        } else {
            refs.wanWarningBanner.classList.add('is-hidden');
        }
    }

    function updateLogOutput(lines) {
        if (!refs.logOutput) {
            return;
        }

        var safeLines = Array.isArray(lines) ? lines : [];
        var text = safeLines.join('\n').trim();

        refs.logOutput.textContent = text || 'No log lines available.';
        refs.logOutput.scrollTop = refs.logOutput.scrollHeight;
    }

    function buildConnectionOption(option, selectedId) {
        var label = document.createElement('label');
        label.className = 'connection-item';

        if (option.active) {
            label.classList.add('is-active');
        }
        if (option.system) {
            label.classList.add('is-system');
        }

        var input = document.createElement('input');
        input.type = 'radio';
        input.name = 'selection';
        input.value = option.id;
        input.checked = selectedId === option.id;

        var copy = document.createElement('span');
        copy.className = 'connection-copy';

        var name = document.createElement('span');
        name.className = 'connection-name';
        name.textContent = option.label;

        var meta = document.createElement('span');
        meta.className = 'connection-meta';
        meta.textContent = option.meta;

        copy.appendChild(name);
        copy.appendChild(meta);
        label.appendChild(input);
        label.appendChild(copy);

        return label;
    }

    function renderConnectionList(state) {
        if (!refs.connectionList) {
            return;
        }

        var selectedId = state.selectedAction || 'stop';
        var dynamicConnections = Array.isArray(state.connections) ? state.connections : [];
        var options = [];

        dynamicConnections.forEach(function (connection) {
            options.push({
                id: String(connection.id || ''),
                label: String(connection.label || connection.id || 'Unknown profile'),
                meta: connection.active ? 'Currently active' : 'VPN profile',
                active: Boolean(connection.active),
                system: false
            });
        });

        options.push({
            id: 'stop',
            label: 'Stop VPN routing',
            meta: 'Disable tunnel and forwarding',
            active: false,
            system: true
        });

        options.push({
            id: 'stop-route-local',
            label: 'LAN only mode',
            meta: 'Stop tunnel but keep local forwarding when WAN is down',
            active: false,
            system: true
        });

        refs.connectionList.innerHTML = '';
        var fragment = document.createDocumentFragment();

        options.forEach(function (option) {
            fragment.appendChild(buildConnectionOption(option, selectedId));
        });

        refs.connectionList.appendChild(fragment);
    }

    function updateStatusView(state) {
        var activeState = String(state.activeState || 'unknown').toLowerCase();
        var routeMode = String(state.routeMode || 'stopped').toLowerCase();
        var currentConnection = state.currentConnection ? formatTitle(state.currentConnection) : 'None';
        var ipForwardEnabled = Boolean(state.ipForwardEnabled);
        var internetReachable = Boolean(state.internetReachable);
        var publicIpAddress = state.publicIpAddress ? String(state.publicIpAddress) : '';

        if (refs.activeState) {
            refs.activeState.textContent = formatTitle(activeState);
        }

        if (refs.routeMode) {
            refs.routeMode.textContent = routeMode === 'vpn' && !internetReachable ? 'VPN Tunnel (WAN down)' : formatRouteMode(routeMode);
        }

        if (refs.currentConnection) {
            refs.currentConnection.textContent = currentConnection;
        }

        if (refs.wanUplink) {
            refs.wanUplink.textContent = formatWanState(internetReachable, publicIpAddress);
        }

        if (refs.ipForward) {
            refs.ipForward.textContent = ipForwardEnabled ? 'Enabled' : 'Disabled';
        }

        if (refs.updatedAt) {
            refs.updatedAt.textContent = formatDate(state.updatedAt);
        }

        setCardTone(refs.cardActiveState, activeState === 'active' ? 'ok' : 'warn');
        setCardTone(refs.cardRouteMode, routeMode === 'vpn' ? (internetReachable ? 'ok' : 'warn') : (routeMode === 'local' ? 'warn' : 'neutral'));
        setCardTone(refs.cardWanUplink, internetReachable ? 'ok' : 'warn');
        setCardTone(refs.cardIpForward, ipForwardEnabled ? 'ok' : 'neutral');

        updateWanWarning(state);

        renderConnectionList(state);

        if (Array.isArray(state.logLines)) {
            updateLogOutput(state.logLines);
        }
    }

    function toRequestOptions(options) {
        var requestOptions = options || {};
        requestOptions.credentials = requestOptions.credentials || 'same-origin';
        requestOptions.headers = requestOptions.headers || {};

        if (!requestOptions.headers['X-Requested-With']) {
            requestOptions.headers['X-Requested-With'] = 'XMLHttpRequest';
        }

        return requestOptions;
    }

    function fetchJson(url, options) {
        return fetch(url, toRequestOptions(options)).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                if (!response.ok || !payload || payload.success === false) {
                    var message = payload && payload.error ? payload.error : 'Request failed.';
                    throw new Error(message);
                }

                return payload;
            });
        });
    }

    function setBusyState(isBusy) {
        if (refs.applyButton) {
            refs.applyButton.disabled = isBusy;
            refs.applyButton.textContent = isBusy ? 'Applying...' : 'Apply selection';
        }

        document.querySelectorAll('[data-selection]').forEach(function (button) {
            button.disabled = isBusy;
        });
    }

    function getSelectedSelection() {
        if (!refs.form) {
            return '';
        }

        var formData = new FormData(refs.form);
        return String(formData.get('selection') || '').trim();
    }

    function applySelection(selection) {
        if (!selection) {
            showAlert('Select a profile before applying changes.', 'info');
            return;
        }

        var body = new URLSearchParams();
        body.set('selection', selection);
        body.set('csrf_token', String(config.csrfToken || ''));

        setBusyState(true);

        fetchJson(config.actionEndpoint || './api/action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        }).then(function (payload) {
            updateStatusView(payload.data || {});
            showAlert(payload.message || 'VPN selection applied.', 'success');
            return refreshLogs(true);
        }).catch(function (error) {
            showAlert(error.message || 'Unable to apply VPN selection.', 'error');
        }).finally(function () {
            setBusyState(false);
        });
    }

    function refreshStatus(silent) {
        if (isStatusLoading) {
            return Promise.resolve();
        }

        isStatusLoading = true;

        return fetchJson(config.statusEndpoint || './api/status.php').then(function (payload) {
            updateStatusView(payload.data || {});
            if (!silent) {
                showAlert('Status refreshed.', 'success');
            }
        }).catch(function (error) {
            if (!silent) {
                showAlert(error.message || 'Unable to refresh status.', 'error');
            }
        }).finally(function () {
            isStatusLoading = false;
        });
    }

    function refreshLogs(silent) {
        if (isLogLoading) {
            return Promise.resolve();
        }

        isLogLoading = true;

        return fetchJson((config.logsEndpoint || './api/logs.php') + '?limit=120').then(function (payload) {
            var data = payload.data || {};
            updateLogOutput(data.lines || []);
            if (!silent) {
                showAlert('Log updated.', 'success');
            }
        }).catch(function (error) {
            if (!silent) {
                showAlert(error.message || 'Unable to refresh log.', 'error');
            }
        }).finally(function () {
            isLogLoading = false;
        });
    }

    function readInitialState() {
        var stateTag = document.getElementById('dashboardInitialState');
        if (!stateTag) {
            return null;
        }

        try {
            return JSON.parse(stateTag.textContent || '{}');
        } catch (error) {
            return null;
        }
    }

    function bindEvents() {
        if (refs.form) {
            refs.form.addEventListener('submit', function (event) {
                event.preventDefault();
                applySelection(getSelectedSelection());
            });
        }

        document.querySelectorAll('[data-selection]').forEach(function (button) {
            button.addEventListener('click', function () {
                applySelection(String(button.getAttribute('data-selection') || ''));
            });
        });

        if (refs.refreshStatusButton) {
            refs.refreshStatusButton.addEventListener('click', function () {
                refreshStatus(false);
            });
        }

        if (refs.refreshLogButton) {
            refs.refreshLogButton.addEventListener('click', function () {
                refreshLogs(false);
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshStatus(true);
                refreshLogs(true);
            }
        });
    }

    function startPolling() {
        setInterval(function () {
            refreshStatus(true);
        }, 6000);

        setInterval(function () {
            refreshLogs(true);
        }, 3500);
    }

    function init() {
        var initialState = readInitialState();
        if (initialState && initialState.data) {
            updateStatusView(initialState.data);
        }

        if (initialState && initialState.error) {
            showAlert(initialState.error, 'error');
        }

        bindEvents();
        startPolling();

        refreshStatus(true);
        refreshLogs(true);
    }

    init();
}());
