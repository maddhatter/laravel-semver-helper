<?php namespace MaddHatter\SemverHelper\Console;

use Illuminate\Console\Command;
use MaddHatter\SemverHelper\Changelog;

class ChangesListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:list {version?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the changelog';

    /**
     * @var Changelog
     */
    private $changelog;

    /**
     * Create a new command instance.
     *
     * @param Changelog $changelog
     */
    public function __construct(Changelog $changelog)
    {
        $this->changelog = $changelog;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = config('semver-helper.changelog');
        if ( ! \File::exists($path)) {
            $this->error("No changelog exists at {$path}, use change:add to create it.");
            return;
        }

        $changelog = $this->changelog->load($path)->changelog();

        if ($version = $this->argument('version')) {
            if (array_key_exists($version, $changelog)) {
                $this->info($version);
                foreach ($changelog[$version] as $change) {
                    $this->info("\t{$change}");
                }
            } else {
                $this->warn("No changes found with version [{$version}]");
            }
        } else {
            foreach($changelog as $version => $changes) {
                $this->info($version);
                foreach ($changes as $change) {
                    $this->info("\t{$change}");
                }
            }
        }

    }
}
