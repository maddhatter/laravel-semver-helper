<?php namespace MaddHatter\SemverHelper\Console;

use MaddHatter\SemverHelper\Changelog;

class ChangesListCommand
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
        $changelog = $this->changelog->load(config('semver-helper.changelog'))->changelog();

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
