<?php

namespace Syndicate\Inspector\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Symfony\Component\Finder\Finder;

class CheckFilter extends SelectFilter
{
    public static function make(?string $name = 'check'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->options($this->getChecks());
    }

    protected function getChecks(): array
    {
        $finder = new Finder();
        $checksPath = __DIR__.'/../../../Checks';

        $checkClasses = [];

        if (is_dir($checksPath)) {
            $finder->files()->in($checksPath)->name('*.php')->notName('BaseCheck.php');

            foreach ($finder as $file) {
                $filename = $file->getFilenameWithoutExtension();
                $className = 'Syndicate\\Inspector\\Checks\\'.$filename;

                if (class_exists($className)) {
                    $checkClasses[] = $className;
                }
            }
        }

        return collect($checkClasses)
            ->sort()
            ->mapWithKeys(function ($name) {
                $key = class_basename($name);
                $label = str($key)->replaceEnd('Check', '')->headline()->toString();
                return [$key => $label];
            })->toArray();
    }
}


