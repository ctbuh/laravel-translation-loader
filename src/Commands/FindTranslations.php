<?php

namespace Spatie\TranslationLoader\Commands;

use Illuminate\Console\Command;
use Spatie\TranslationLoader\LanguageLine;
use Symfony\Component\Finder\Finder;

class FindTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trans:find';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark Language lines that are used and where they are being used.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function fetchByKey($key)
    {
        $parts = explode('.', $key, 2);

        if (count($parts) == 2) {
            return LanguageLine::where('group', $parts[0])->where('key', $parts[1])->first();
        }

        return null;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // got it from: https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php#L153

        $functions = array('trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
            '__',
            '$trans.get',
        );

        $path = base_path();

        $groupPattern =                          // See https://regex101.com/r/WEJqdL/6
            "[^\w|>]" .                          // Must not have an alphanum or _ or > before real method
            '(' . implode('|', $functions) . ')' .  // Must start with one of the functions
            "\(" .                               // Match opening parenthesis
            "[\'\"]" .                           // Match " or '
            '(' .                                // Start a new group to match:
            '[a-zA-Z0-9_-]+' .               // Must start with group
            "([.|\/](?! )[^\1)]+)+" .             // Be followed by one or more items/keys
            ')' .                                // Close group
            "[\'\"]" .                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter


        $stringPattern =
            "[^\w]" .                                     // Must not have an alphanum before real method
            '(' . implode('|', $functions) . ')' .             // Must start with one of the functions
            "\(" .                                          // Match opening parenthesis
            "(?P<quote>['\"])" .                            // Match " or ' and store in {quote}
            "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)" . // Match any string that can be {quote} escaped
            "\k{quote}" .                                   // Match " or ' previously matched
            "[\),]";                                       // Close parentheses or new parameter


        $exclude = array('bootstrap', 'config', 'database', 'public', 'storage', 'tests', 'vendor');

        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();
        $finder->in($path)->exclude($exclude)->name('*.php')->name('*.twig')->name('*.vue')->files();
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {

            // Search the current file for the pattern
            if (preg_match_all("/$groupPattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    $groupKeys[] = $key;

                    $line = $this->fetchByKey($key);
                    if ($line) {
                        $line->is_used = 1;
                        $line->used_in = $file->getRelativePathname();
                        $line->save();

                        dump($key);
                    }
                }
            }
        }

        return 'gdfg';


        return 'ok';

        /*
         * https://github.com/vsch/laravel-translation-manager/blob/389d76ba411b8e0df67d4c65cdf117e0036af1fe/src/Manager.php

add trans “used_in”: field
https://github.com/vsch/laravel-translation-manager/wiki/Web-Interface#source-references

https://github.com/vsch/laravel-translation-manager/blob/389d76ba411b8e0df67d4c65cdf117e0036af1fe/src/Manager.php#L1276
         */
        // https://github.com/PhiloNL/laravel-translate/blob/master/src/Philo/Translate/TranslateManager.php#L156

        $str = "groups.create";

        $ignore = array('bootstrap', 'config', 'database', 'public', 'storage', 'tests', 'vendor');
        $ret = (new Finder())->files()->name('*.php')->in(base_path())->exclude($ignore)->contains($str);

        foreach ($ret as $file) {
            dump($file);
        }

    }
}
