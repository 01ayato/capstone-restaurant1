<?php

declare(strict_types=1);

use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web-triggered migrations (local only)
|--------------------------------------------------------------------------
|
| GET /migrate — terminal UI that streams progress from /migrate/stream.
| Disabled outside the local environment.
|
*/

Route::middleware('web')->group(function (): void {
    Route::get('/migrate', function () {
        if (! app()->isLocal()) {
            abort(404);
        }

        return response()->view('migrate-terminal');
    })->name('migrate.run');
});

Route::get('/migrate/stream', function () {
    if (! app()->isLocal()) {
        abort(404);
    }

    return response()->stream(function (): void {
        ignore_user_abort(true);

        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');

        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_implicit_flush(true);

        $emit = static function (array $payload): void {
            echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n\n";
            flush();
        };

        $humanizeSql = static function (string $sql): string {
            $sql = preg_replace('/\s+/', ' ', trim($sql)) ?? '';

            if (preg_match('/^alter\s+table\s+[`"]?(\w+)[`"]?\s+/i', $sql, $m)) {
                $table = $m[1];
                if (preg_match('/add\s+(?:column\s+)?[`"]?(\w+)[`"]?/i', $sql, $c)) {
                    return 'Column added    '.$table.'.'.$c[1];
                }
                if (preg_match('/change\s+[`"]?(\w+)[`"]?/i', $sql, $c)) {
                    return 'Column changed  '.$table.'.'.$c[1];
                }
                if (preg_match('/drop\s+(?:column\s+)?[`"]?(\w+)[`"]?/i', $sql, $c)) {
                    return 'Column dropped  '.$table.'.'.$c[1];
                }
                if (preg_match('/modify\s+(?:column\s+)?[`"]?(\w+)[`"]?/i', $sql, $c)) {
                    return 'Column modified '.$table.'.'.$c[1];
                }

                return 'Alter table '.$table.': '.Str::limit($sql, 140);
            }

            if (preg_match('/^create\s+table\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
                return 'Create table    '.$m[1];
            }

            if (preg_match('/^drop\s+table/i', $sql)) {
                return 'Drop table      '.Str::limit($sql, 120);
            }

            if (preg_match('/^rename\s+table/i', $sql)) {
                return 'Rename table    '.Str::limit($sql, 120);
            }

            return Str::limit($sql, 160);
        };

        $tracking = true;

        Event::listen(MigrationStarted::class, function (MigrationStarted $event) use (&$tracking, $emit): void {
            if (! $tracking) {
                return;
            }

            $path = (new ReflectionClass($event->migration))->getFileName();
            $emit(['kind' => 'file', 'text' => basename($path)]);
        });

        DB::listen(function ($query) use (&$tracking, $emit, $humanizeSql): void {
            if (! $tracking) {
                return;
            }

            $sql = $query->sql;
            if (! preg_match('/^\s*(alter|create|drop|rename)\s+/i', $sql)) {
                return;
            }

            $emit(['kind' => 'ddl', 'text' => $humanizeSql($sql)]);
        });

        $user = 'user';
        if (is_string($u = getenv('USERNAME')) && $u !== '') {
            $user = strtolower($u);
        } elseif (is_string($u = getenv('USER')) && $u !== '') {
            $user = strtolower($u);
        }

        $host = Str::slug((string) config('app.name', 'lending-system'), '-');

        $emit([
            'kind' => 'prompt',
            'user' => $user,
            'host' => $host,
            'cmd' => 'php artisan migrate --force',
        ]);

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            $emit(['kind' => 'error', 'text' => $e->getMessage()]);
        } finally {
            $tracking = false;
        }

        $output = trim(Artisan::output());
        if ($output !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $output) as $line) {
                if ($line !== '') {
                    $emit(['kind' => 'out', 'text' => $line]);
                }
            }
        }

        $emit(['kind' => 'meta', 'text' => 'Stream finished.']);
        $emit(['kind' => 'end', 'text' => '']);
    }, 200, [
        'Content-Type' => 'text/event-stream; charset=UTF-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
    ]);
})->name('migrate.stream');
