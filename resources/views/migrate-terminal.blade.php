<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Migration runner</title>
    <style>
        :root {
            --bg: #000000;
            --fg: #33ff66;
            --fg-dim: #1a9940;
            --border: #33ff66;
            --user: #66ff99;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            background: var(--bg);
            color: var(--fg);
            font-family: ui-monospace, "Cascadia Code", "Consolas", "Liberation Mono", monospace;
            font-size: 14px;
            line-height: 1.45;
        }
        .shell {
            max-width: 960px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .term {
            border: 1px solid var(--border);
            background: var(--bg);
            min-height: 320px;
            display: flex;
            flex-direction: column;
        }
        .term-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border);
            color: var(--fg);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 11px;
        }
        .term-head span:last-child {
            text-transform: lowercase;
            letter-spacing: 0;
            color: var(--fg-dim);
        }
        .term-body {
            flex: 1;
            padding: 0.75rem 0.75rem 0.5rem;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .term-body .prompt-user { color: var(--user); }
        .term-foot {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-top: 1px solid var(--border);
            color: var(--fg-dim);
            font-size: 12px;
        }
        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--fg);
            animation: pulse 1.2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.35; transform: scale(0.85); }
        }
        .kind-prompt { color: var(--fg); }
        .kind-file { color: #7dffac; }
        .kind-ddl { color: var(--fg); }
        .kind-out { color: #9effb8; }
        .kind-meta { color: var(--fg-dim); }
        .kind-error { color: #ff6b6b; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="term" role="log" aria-live="polite" aria-relevant="additions">
            <div class="term-head">
                <span>{{ strtoupper(config('app.name', 'Laravel')) }} terminal</span>
                <span>migration session</span>
            </div>
            <div class="term-body" id="out"></div>
            <div class="term-foot">
                <span class="pulse" id="pulse" hidden aria-hidden="true"></span>
                <span id="status">Starting…</span>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const out = document.getElementById('out');
            const status = document.getElementById('status');
            const pulse = document.getElementById('pulse');

            function appendLine(text, className) {
                const line = document.createElement('div');
                if (className) line.className = className;
                line.textContent = text;
                out.appendChild(line);
                out.scrollTop = out.scrollHeight;
            }

            function appendPrompt(user, host, cmd) {
                const line = document.createElement('div');
                line.className = 'kind-prompt';
                const u = document.createElement('span');
                u.className = 'prompt-user';
                u.textContent = '[' + user + '@' + host + ' ~]# ';
                line.appendChild(u);
                line.appendChild(document.createTextNode(cmd));
                out.appendChild(line);
                out.scrollTop = out.scrollHeight;
            }

            function setRunning(running) {
                pulse.hidden = !running;
                status.textContent = running ? 'Running migrations…' : 'Watching for changes…';
            }

            const streamUrl = @json(url('/migrate/stream'));
            setRunning(true);

            const es = new EventSource(streamUrl);
            es.onmessage = function (e) {
                let payload;
                try {
                    payload = JSON.parse(e.data);
                } catch (_) {
                    appendLine(e.data, 'kind-error');
                    return;
                }

                if (payload.kind === 'end') {
                    setRunning(false);
                    es.close();
                    return;
                }

                if (payload.kind === 'prompt') {
                    appendPrompt(String(payload.user || 'user'), String(payload.host || 'app'), String(payload.cmd || ''));
                    return;
                }

                const cls = 'kind-' + (payload.kind || 'out');
                appendLine(String(payload.text || ''), cls);
            };

            es.onerror = function () {
                setRunning(false);
                appendLine('Stream interrupted. Refresh the page to retry.', 'kind-error');
                es.close();
            };
        })();
    </script>
</body>
</html>
