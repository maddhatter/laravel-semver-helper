<?php namespace MaddHatter\SemverHelper\Console;

use Composer\Semver\VersionParser;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Parser;

class TagVersionCommand extends Command
{

    const allowedTypes = [
        'major',
        'minor',
        'patch',
        'dev',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tag {type : The type of tag (major, minor, patch)}
                            {--no-changes : Don\'t prompt for changes}
                            {--pre= : Pre-release information}
                            {--meta= : Metadata information}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tag a new release.';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @var Changelog
     */
    private $changelog;

    /**
     * Create a new command instance.
     *
     * @param Parser        $parser
     * @param VersionParser $versionParser
     * @param Changelog     $changelog
     */
    public function __construct(
        Parser $parser,
        VersionParser $versionParser,
        Changelog $changelog
    ) {
        $this->parser        = $parser;
        $this->versionParser = $versionParser;
        $this->changelog     = $changelog;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->validateOptions();

        $version       = $this->targetVersion();
        $dottedVersion = $this->dottedVersion($version);

        $this->info("Setting dotted to {$dottedVersion}");

        $changelog = $this->changelog->load(config('semver-helper.changelog'));
        $changelog->tag($dottedVersion);

        if ( ! empty($changelog->changelog()[$dottedVersion])) {
            $this->info('The following changelog records will be tagged for this version:');
            foreach ($changelog->changelog()[$dottedVersion] as $change) {
                $this->info("\t{$change}");
            }
        } else {
            $this->info('The changelog contains no records for this version.');
        }

        if ( ! $this->option('no-changes')) {
            while ($this->confirm('Would you like to add additional changes?')) {
                $change = $this->ask('Enter change:');
                $changelog->add($change, $dottedVersion);
            }
        }

        $changelog->save(config('semver-helper.changelog'));
        $this->saveVersion($version);
    }

    private function targetVersion()
    {
        $currentVersion = $this->getCurrentVersion();

        switch ($this->argument('type')) {
            case 'major':
                return $this->makeVersion(
                    $currentVersion[':major'] + 1,
                    0,
                    0,
                    $this->option('pre'),
                    $this->option('meta')
                );

            case 'minor':
                return $this->makeVersion(
                    $currentVersion[':major'],
                    $currentVersion[':minor'] + 1,
                    0,
                    $this->option('pre'),
                    $this->option('meta')
                );

            case 'patch':
                return $this->makeVersion(
                    $currentVersion[':major'],
                    $currentVersion[':minor'],
                    $currentVersion[':patch'] + 1,
                    $this->option('pre'),
                    $this->option('meta')
                );

            case 'dev':
                return $this->makeVersion(
                    $currentVersion[':major'],
                    $currentVersion[':minor'],
                    $currentVersion[':patch'],
                    $this->option('pre'),
                    empty($this->option('meta')) ? 'develop' : $this->option('meta') . '.develop'
                );

            default:
                throw new \RuntimeException("Unknown tag type [{$this->argument('type')}]");
        }
    }

    private function saveVersion($version)
    {
        /*
         * Symfony's YAML dumper doesn't get along w/ the semver Ruby Gem,
         * so cheat and build the YAML file manually
         */
        $yaml = '---' . PHP_EOL;
        $yaml .= ":major: {$version[':major']}" . PHP_EOL;
        $yaml .= ":minor: {$version[':minor']}" . PHP_EOL;
        $yaml .= ":patch: {$version[':patch']}" . PHP_EOL;
        $yaml .= ":special: '{$version[':special']}'" . PHP_EOL;
        $yaml .= ":metadata: '{$version[':metadata']}'" . PHP_EOL;

        \File::put(base_path('.semver'), $yaml);

    }

    private function dottedVersion($version)
    {

        $dotted = "{$version[':major']}.{$version[':minor']}.{$version[':patch']}";

        if ( ! empty($version[':special'])) {
            $dotted .= "-{$version[':special']}";
        }

        if ( ! empty($version[':metadata'])) {
            $dotted .= "+{$version[':metadata']}";
        }

        return $dotted;
    }

    /**
     * Read the .semver file for current dotted
     *
     * @return mixed
     */
    private function getCurrentVersion()
    {
        $yaml = \File::get(base_path('.semver'));

        return $this->parser->parse($yaml);
    }

    /**
     * Validate options
     */
    private function validateOptions()
    {
        if ( ! in_array($this->argument('type'), self::allowedTypes)) {
            throw new \InvalidArgumentException("Invalid tag type [{$this->argument('type')}]. Valid types are " . implode(', ', self::allowedTypes));
        }
    }

    /**
     * Create a semver dotted array
     *
     * @param int   $major
     * @param int   $minor
     * @param int   $patch
     * @param mixed $special
     * @param mixed $metadata
     * @return array
     */
    private function makeVersion($major, $minor, $patch, $special = null, $metadata = null)
    {
        return [
            ':major'    => $major,
            ':minor'    => $minor,
            ':patch'    => $patch,
            ':special'  => is_null($special) ? "" : $special,
            ':metadata' => is_null($metadata) ? "" : $metadata,
        ];
    }
}
