<?php

class FeedMergeBridge extends FeedExpander
{
    const MAINTAINER = 'dvikan';
    const NAME = 'FeedMerge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge';
    const DESCRIPTION = <<<'TEXT'
        This bridge merges two or more feeds into a single feed. <br>
        Max 10 latest items are fetched from each individual feed. <br>
        Items with identical url or title are considered duplicates (and are removed). <br>
    TEXT;

    const PARAMETERS = [
        [
            'feed_name' => [
                'name' => 'Feed name',
                'type' => 'text',
                'exampleValue' => 'FeedMerge',
            ],
            'feed_1' => [
                'name' => 'Feed url',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'https://lorem-rss.herokuapp.com/feed?unit=day'
            ],
            'feed_2' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_3' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_4' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_5' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_6' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_7' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_8' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_9' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_10' => ['name' => 'Feed url', 'type' => 'text'],
            'limit' => self::LIMIT,
        ]
    ];

    /**
     * TODO: Consider a strategy which produces a shorter feed url
     */
    public function collectData()
    {
        $limit = (int)($this->getInput('limit') ?: 99);
        $feeds = [
            $this->getInput('feed_1'),
            $this->getInput('feed_2'),
            $this->getInput('feed_3'),
            $this->getInput('feed_4'),
            $this->getInput('feed_5'),
            $this->getInput('feed_6'),
            $this->getInput('feed_7'),
            $this->getInput('feed_8'),
            $this->getInput('feed_9'),
            $this->getInput('feed_10'),
        ];

        // Remove empty values
        $feeds = array_filter($feeds);

        foreach ($feeds as $feed) {
            if (count($feeds) > 1) {
                // Allow one or more feeds to fail
                try {
                    $this->collectExpandableDatas($feed, 10);
                } catch (HttpException $e) {
                    $this->logger->warning(sprintf('Exception in FeedMergeBridge: %s', create_sane_exception_message($e)));
                    // This feed item might be spammy. Considering dropping it.
                    $this->items[] = [
                        'title' => 'RSS-Bridge: ' . $e->getMessage(),
                        // Give current time so it sorts to the top
                        'timestamp' => time(),
                    ];
                    continue;
                } catch (\Exception $e) {
                    if (str_starts_with($e->getMessage(), 'Failed to parse xml')) {
                        // Allow this particular exception from FeedExpander
                        $this->logger->warning(sprintf('Exception in FeedMergeBridge: %s', create_sane_exception_message($e)));
                        continue;
                    }
                    throw $e;
                }
            } else {
                $this->collectExpandableDatas($feed, 10);
            }
        }

        // If $this->items is empty we should consider throw exception here

        // Sort by timestamp, uri, title in descending order
        usort($this->items, function ($a, $b) {
            $t1 = $a['timestamp'] ?? $a['uri'] ?? $a['title'];
            $t2 = $b['timestamp'] ?? $b['uri'] ?? $b['title'];
            return $t2 <=> $t1;
        });

        // Remove duplicates by url
        $items = [];
        foreach ($this->items as $item) {
            $uri = $item['uri'] ?? null;
            if ($uri) {
                // Insert or override the existing duplicate
                $items[$uri] = $item;
            } else {
                // The item doesn't have a uri!
                $items[] = $item;
            }
        }
        $this->items = array_values($items);

        // Remove duplicates by title
        $items = [];
        foreach ($this->items as $item) {
            $title = $item['title'] ?? null;
            if ($title) {
                // Insert or override the existing duplicate
                $items[$title] = $item;
            } else {
                // The item doesn't have a title!
                $items[] = $item;
            }
        }
        $this->items = array_values($items);

        $this->items = array_slice($this->items, 0, $limit);
    }

    public function getIcon()
    {
        return 'https://cdn.jsdelivr.net/npm/famfamfam-silk@1.0.0/dist/png/folder_feed.png';
    }

    public function getName()
    {
        return $this->getInput('feed_name') ?: 'FeedMerge';
    }
}
