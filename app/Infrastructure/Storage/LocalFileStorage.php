<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Ports\Outbound\FileStorage;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

final class LocalFileStorage implements FileStorage
{
    private string $disk = 'public';

    public function store(string $contents, string $extension): string
    {
        $cleanExt = ltrim(strtolower($extension), '.');
        $key = Uuid::uuid4()->toString() . '.' . $cleanExt;
        Storage::disk($this->disk)->put("media/{$key}", $contents);
        return $key;
    }

    public function get(string $key): ?string
    {
        $path = "media/{$key}";
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }
        return Storage::disk($this->disk)->get($path);
    }

    public function delete(string $key): void
    {
        $path = "media/{$key}";
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }
}
