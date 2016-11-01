<?php namespace MaddHatter\SemverHelper\Console;

use MaddHatter\SemverHelper\Changelog;

class ChangesMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:add {message} {--for=develop : Specify the version the change occurred on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a new changelog entry.';

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
        $changelog = $this->changelog->load(config('semver-helper.changelog'));

        $version = $this->option('for');
        $changelog->add($this->argument('message'), $version)->save(config('semver-helper.changelog'));

        $this->info("Current changes for version [{$version}]:");
        foreach($changelog->changelog()[$version] as $change) {
            $this->info("\t{$change}");
        }
    }
}
