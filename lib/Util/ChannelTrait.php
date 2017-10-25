<?php

namespace MovingImage\Client\VMPro\Util;

use MovingImage\Client\VMPro\Entity\Channel;

/**
 * Helper methods for manipulating Channels.
 */
trait ChannelTrait
{
    /**
     * Configures the parent/child relationships between Channels.
     *
     * @param Channel[] $channels - any iterable collection of Channel
     *
     * @return array
     */
    private function setChannelRelations($channels)
    {
        $indexedChannels = [];

        /** @var Channel $channel */
        foreach ($channels as $channel) {
            $indexedChannels[$channel->getId()] = $channel;
        }

        foreach ($indexedChannels as $channel) {
            $parentId = $channel->getParentId();
            if ($parentId && array_key_exists($parentId, $indexedChannels)) {
                /** @var Channel $parent */
                $parent = $indexedChannels[$parentId];
                $channel->setParent($parent);
                $parent->addChild($channel);
            }
        }

        return array_values($indexedChannels);
    }
}
