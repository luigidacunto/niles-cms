<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Punto unico per salvare un'immagine: ridimensiona (lato più lungo `$max`, senza mai ingrandire) e la salva
 * sul disco `$disk` (default `public`; `local` per i contenuti riservati ai soci) sotto `$dir` con un nome leggibile (vedi `Filenames`). La usano i post (copertina e
 * galleria) e la Struttura Organizzativa (foto dei membri).
 *
 * ⚠️ intervention/image v4: il metodo del manager è `decode()` (accetta un percorso), NON `read()`,
 * che era della v3 e nella v4 non esiste.
 */
class ImageUpload
{
    public static function storeResized(UploadedFile $file, string $dir, string $nameBase, int $max = 1600, string $disk = 'public'): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return self::save($file->getRealPath(), $dir, Filenames::readable($nameBase, $ext), $max, $disk);
    }

    private static function save(string $sourcePath, string $dir, string $filename, int $max, string $disk = 'public'): string
    {
        $manager = new ImageManager(Driver::class);
        $image = $manager->decode($sourcePath)->scaleDown(width: $max, height: $max);

        $path = $dir.'/'.$filename;

        // ->save() di Intervention non crea la cartella (a differenza di Storage::putFile).
        $storage = Storage::disk($disk);
        $storage->makeDirectory($dir);
        $image->save($storage->path($path));

        return $path;
    }
}
