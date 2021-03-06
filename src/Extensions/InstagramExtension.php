<?php

namespace Internetrix\Instagram\Extensions;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;

class InstagramExtension extends DataExtension
{
    private static $db = [
        'InstagramUsername' => 'Varchar(255)',
        'InstagramLimit' => 'Varchar(255)',
    ];

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $this->setInstagramCacheContent();
    }

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Instagram', [
            TextField::create('InstagramUsername', 'Instagram Username'),
            NumericField::create('InstagramLimit', 'Instagram Limit')
                ->setDescription('If set to 0, no limit will be applied.')
        ]);
    }

    /**
     * Get posts from Instagram, then format them for use in the template
     * @return ArrayList
     */
    public function getInstagramPosts()
    {
        if ($this->owner->InstagramUsername) {
            $feed = $this->getInstagramCacheContent();
            $limit = $this->owner->InstagramLimit ? $this->owner->InstagramLimit : null;

            if ($feed['graphql']['user']['edge_owner_to_timeline_media']['edges']) {
                $posts = array_slice($feed['graphql']['user']['edge_owner_to_timeline_media']['edges'], 0, $limit, true);

                $data = ArrayList::create();

                foreach ($posts as $post) {
                    $data->push([
                        'ID' => $post['node']['id'],
                        'Shortcode' => $post['node']['shortcode'],
                        'Thumbnail' => 'data:image/jpg;base64,'.base64_encode(@file_get_contents($post['node']['thumbnail_src'])),
                        'Owner' => [
                            'ID' => $post['node']['owner']['id'],
                            'Username' => $post['node']['owner']['username']
                        ],
                        'Alt' => $post['node']['accessibility_caption'],
                        'Text' => $post['node']['edge_media_to_caption']['edges'][0]['node']['text'],
                        'Comments' => $post['node']['edge_media_to_comment']['count'],
                        'Likes' => $post['node']['edge_liked_by']['count'],
                        'Date' => date('d F Y', $post['node']['taken_at_timestamp']),
                        'Type' => 'Instagram'
                    ]);
                }

                return $data;
            }
        }

        return ArrayList::create();
    }

    public function getInstagramCacheFile()
    {
        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . '_socialCaches' . DIRECTORY_SEPARATOR . 'Instagram';
        $file = $path . DIRECTORY_SEPARATOR . $this->owner->InstagramUsername;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $file;
    }

    /**
     * Store cache content locally
     */
    public function setInstagramCacheContent()
    {
        $username = $this->owner->InstagramUsername;
        if ($username) {
            $url = "https://www.instagram.com/" . $username . "/channel/?__a=1";

            $request = Injector::inst()->get(HTTPRequest::class);
            $session = $request->getSession();
            $sessionID = $session->getAll()['SecurityID'];

            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Cookie: ds_user=' . $username . '; igfl=' . $username . '; sessionid=' . $sessionID,
                'User-Agent: Instagram 7.16.0 Android'
            ));
            header('Content-Type: application/json');
            $json = curl_exec($ch);

            if (curl_errno($ch)) {
                echo curl_error($ch);
                $json = null;
            } else {
                curl_close($ch);
            }

            if (!is_string($json) || !strlen($json)) {
                $json = null;
            }

//        $json = file_get_contents($url);
            $data = json_decode($json, true);

            if ($data) {
                $file = $this->getInstagramCacheFile();

                file_put_contents($file, serialize($data));
            }
        }
    }

    /**
     * Retrieve cached content from local storage
     * @return mixed|null
     */
    public function getInstagramCacheContent()
    {
        $file = $this->getInstagramCacheFile();

        if (!file_exists($file)) {
            file_put_contents($file, serialize([]));
        }

        $content = unserialize(file_get_contents($file));

        if ($content) {
            return json_decode(json_encode($content), true);
        }

        return null;
    }
}
