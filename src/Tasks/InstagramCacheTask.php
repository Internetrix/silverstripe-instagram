<?php

namespace Internetrix\Instagram\Tasks;

use Internetrix\Instagram\Extensions\InstagramExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * Class InstagramCacheTask
 * @package Internetrix\Instagram\Tasks
 */
class InstagramCacheTask extends BuildTask
{
    protected $title = 'Set Instagram Caches';

    protected $description = 'Updates the cache of each instagram feed.';

    private static $segment = 'set-instagram-caches';

    public function run($request)
    {
        set_time_limit(0);

        $classes = Config::inst()->get(InstagramExtension::class, 'extended_classes');;

        if ($classes) {
            foreach ($classes as $class) {
                $instance = $class::get()->first();
                if ($instance && $instance->hasExtension(InstagramExtension::class)) {
                    $objects = $class::get();

                    foreach ($objects as $object) {
                        $object->setInstagramCacheContent();
                    }

                    DB::alteration_message($class . ' feeds successfully updated.');
                }
            }

            DB::alteration_message('Updates complete.');
        } else {
            DB::alteration_message('No feeds to update.');
        }
    }
}
