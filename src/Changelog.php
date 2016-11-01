<?php namespace MaddHatter\SemverHelper;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Illuminate\Filesystem\Filesystem;

class Changelog
{

    /**
     * @var Semver
     */
    protected $semver;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var VersionParser
     */
    protected $versionParser;

    /**
     * The changelog contents
     *
     * @var array
     */
    protected $changelog = [];

    const specialVersions = [
        'develop',
    ];


    /**
     * Changelog constructor.
     *
     * @param Semver        $semver
     * @param VersionParser $versionParser
     * @param Filesystem    $filesystem
     */
    public function __construct(Semver $semver, VersionParser $versionParser, Filesystem $filesystem)
    {
        $this->semver        = $semver;
        $this->versionParser = $versionParser;
        $this->filesystem    = $filesystem;
    }

    /**
     * Read a changelog from disk
     *
     * @param $path
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function load($path)
    {
        $contents        = $this->filesystem->get($path);
        $this->changelog = json_decode($contents, true);

        return $this;
    }

    /**
     * Write the changelog to a file
     *
     * @param $path
     */
    public function save($path)
    {
        $this->sort();
        $this->filesystem->put($path, json_encode($this->changelog, JSON_PRETTY_PRINT));
    }

    /**
     * Add a change to the changelog
     *
     * @param $change
     * @param $version
     * @return $this
     */
    public function add($change, $version = 'develop')
    {
        $this->addVersion($version);
        $this->changelog[$version][] = $change;

        return $this;
    }

    /**
     * Tag changes under version 'develop' to a version
     *
     * @param $version
     * @return $this
     */
    public function tag($version)
    {
        if ( ! $this->hasDevelop()) {
            \Log::warning("Failed to tag changelog at [{$version}], no develop changes to tag", [__CLASS__]);

            return $this;
        }

        $this->addVersion($version);
        $this->changelog[$version] += $this->changelog['develop'];
        unset($this->changelog['develop']);

        return $this;
    }

    /**
     * Get the changelog
     *
     * @return array
     */
    public function changelog()
    {
        $this->sort();

        return $this->changelog;
    }

    /**
     * Add a new version to the change log if it doesn't exit
     *
     * @param $version
     * @returns void
     */
    private function addVersion($version)
    {
        if ( ! array_key_exists($version, $this->changelog)) {
            if ( ! in_array($version, self::specialVersions)) {
                $this->versionParser->normalize($version);
            }
            $this->changelog[$version] = [];
        }
    }

    /**
     * Sort the changelog by version
     */
    private function sort()
    {
        $versions       = array_keys($this->changelog);
        $semverVersions = array_diff($versions, self::specialVersions);
        $order          = array_flip(array_merge(self::specialVersions, $this->semver->rsort($semverVersions)));

        uksort($this->changelog, function ($a, $b) use ($order) {
            if ($a == $b) {
                return 0;
            }

            return ($order[$a] < $order[$b]) ? -1 : 1;
        });

    }

    /**
     * @return bool
     */
    public function hasDevelop()
    {
        return array_key_exists('develop', $this->changelog);
    }
}
