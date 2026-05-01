<?php

namespace Gigabait93\LaravelSettings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Cache\TaggableStore;
use Gigabait93\LaravelSettings\Models\Setting;
use Illuminate\Database\QueryException;

class SettingsService
{
    // ========== Public API ==========
    public function delete(string $key): bool
    {
        $ok = Setting::deleteByKey($key);
        if ($ok) $this->flushKey($key);
        return $ok;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        try {
            if ($this->ttl() <= 0) {
                return Setting::query()
                    ->get(['key','value'])
                    ->mapWithKeys(static fn (Setting $s): array => [$s->key => $s->value])
                    ->all();
            }

            $make = static function (): array {
                return Setting::query()
                    ->get(['key','value'])
                    ->mapWithKeys(static fn (Setting $s): array => [$s->key => $s->value])
                    ->all(); // ← масив
            };

            $store = Cache::getStore();

            return $store instanceof TaggableStore
                ? Cache::tags([$this->allTag()])->remember($this->allKey(), $this->ttl(), $make)
                : Cache::remember($this->allKey(), $this->ttl(), $make);
        } catch (QueryException $e) {
            if ($this->isMissingTableException($e)) {
                return [];
            }

            throw $e;
        }
    }

    /** @return Collection<int, Setting> */
    public function find(string $prefix): Collection
    {
        try {
            if ($this->ttl() <= 0) {
                return Setting::query()
                    ->where('key', 'like', $prefix.'%')
                    ->get(['key','value']);
            }

            $ck = $this->prefix().'__find__:'.$prefix;

            $cache = $this->cacheTags([$this->allTag()]);

            /** @var Collection $res */
            $res = $cache->remember($ck, $this->ttl(), function () use ($prefix) {
                return Setting::query()
                    ->where('key', 'like', $prefix.'%')
                    ->get(['key','value']);
            });

            return $res;
        } catch (QueryException $e) {
            if ($this->isMissingTableException($e)) {
                return collect();
            }

            throw $e;
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key, $default = null)
    {
        try {
            if ($this->ttl() <= 0) {
                return Setting::getByKey($key) ?? $default;
            }

            $cacheKey = $this->prefix().$key;
            $cb = fn () => Setting::getByKey($key);

            $store = Cache::getStore();
            $val = $store instanceof TaggableStore
                ? Cache::tags([$this->allTag(), $this->keyTag($key)])->remember($cacheKey, $this->ttl(), $cb)
                : Cache::remember($cacheKey, $this->ttl(), $cb);

            return $val ?? $default;
        } catch (QueryException $e) {
            if ($this->isMissingTableException($e)) {
                return $default;
            }

            throw $e;
        }
    }

    public function many(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) $out[$k] = $this->get($k);
        return $out;
    }

    public function set(string $key, $value): bool
    {
        $ok = Setting::setByKey($key, $value);
        if ($ok) $this->flushKey($key);
        return $ok;
    }

    public function setMany(array $settings): bool
    {
        $ok = true;
        foreach ($settings as $k => $v) {
            $res = Setting::setByKey($k, $v);
            if ($res) $this->flushKey($k);
            $ok = $res && $ok;
        }
        return $ok;
    }

    public function setIfNot(string $key, $value): bool
    {
        if (!$this->has($key)) return $this->set($key, $value);
        return false;
    }

    public function getOrSet(string $key, $default): mixed
    {
        $v = $this->get($key);
        if ($v === null) { $this->set($key, $default); return $default; }
        return $v;
    }

    public function getOrSetMany(array $settings): array
    {
        $out = [];
        foreach ($settings as $k => $d) $out[$k] = $this->getOrSet($k, $d);
        return $out;
    }

    public function inc(string $key, int $amount = 1): int
    {
        $cur = $this->get($key, 0);
        $new = (is_numeric($cur) ? $cur : 0) + $amount;
        $this->set($key, $new);
        return $new;
    }

    public function dec(string $key, int $amount = 1): int
    {
        return $this->inc($key, -$amount);
    }

    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    public function forgetMany(array $keys): int
    {
        $keys = array_values(array_unique(array_filter($keys, fn ($k) => is_string($k) && $k !== '')));
        if ($keys === []) {
            return 0;
        }

        $deleted = Setting::query()->whereIn('key', $keys)->delete();
        $store = Cache::getStore();
        if ($store instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags([$this->allTag()])->flush();
        } else {
            Cache::forget($this->allKey());
            foreach ($keys as $k) {
                Cache::forget($this->prefix().$k);
            }
        }
        return $deleted;
    }

    public function clearAllCache(): void
    {
        $store = Cache::getStore();
        if ($store instanceof TaggableStore) {
            Cache::tags([$this->allTag()])->flush();
        } else {
            Cache::forget($this->allKey());
            foreach (Setting::pluck('key') as $k) {
                Cache::forget($this->prefix().$k);
            }
        }
    }

    // ========== Private Methods ==========
    private function ttl(): int { return (int) config('settings.cache.ttl'); }
    private function prefix(): string { return config('settings.cache.prefix'); }
    private function allKey(): string { return config('settings.cache.all_key'); }
    private function allTag(): string { return config('settings.cache.tag_all'); }
    private function keyTag(string $k): string { return config('settings.cache.tag_key_prefix').$k; }
    private function cacheTags(?array $tags = null)
    {
        $store = Cache::getStore();
        return $store instanceof TaggableStore && $tags
            ? Cache::tags($tags)
            : Cache::store();
    }
    private function flushKey(string $key): void
    {
        $store = Cache::getStore();

        if ($store instanceof TaggableStore) {
            Cache::tags([$this->allTag(), $this->keyTag($key)])->flush();
        } else {
            Cache::forget($this->allKey());
            Cache::forget($this->prefix().$key);
        }
    }

    private function isMissingTableException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'no such table')
            || str_contains($message, 'base table or view not found')
            || str_contains($message, 'relation "settings" does not exist');
    }
}
