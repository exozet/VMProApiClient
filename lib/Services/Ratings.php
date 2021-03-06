<?php

namespace MovingImage\Client\VMPro\Services;

use MovingImage\Client\VMPro\Entity\Video;
use MovingImage\Client\VMPro\Entity\VideoRequestParameters;
use MovingImage\Client\VMPro\Interfaces\ApiClientInterface;

/**
 * Class Ratings.
 *
 * @author Robert Szeker <robert.szeker@movingimage.com>
 */
class Ratings
{
    const MINIMUM_RATING = 1;
    const MAXIMUM_RATING = 5;

    /** @var ApiClientInterface */
    private $client;

    /** @var Video [] */
    private $videos = [];

    /** @var int */
    private $vmId;

    /** @var string */
    private $metadataFieldAverage;

    /** @var string */
    private $metadataFieldCount;

    public function __construct(
        ApiClientInterface $client,
        $vmId,
        $metadataFieldAverage,
        $metadataFieldCount
    ) {
        $this->client = $client;
        $this->metadataFieldAverage = $metadataFieldAverage;
        $this->metadataFieldCount = $metadataFieldCount;
        $this->vmId = $vmId;
    }

    /**
     * Increases the count of all ratings by one and calculates a new average rating value.
     *
     * @param string $videoId
     * @param int    $rating
     *
     * @throws \InvalidArgumentException
     */
    public function addRating($videoId, $rating)
    {
        $this->validateRating($rating);
        $customMetaData = $this->getVideo($videoId)->getCustomMetadata();

        $average = $this->getRatingAverage($videoId);
        $count = $this->getRatingCount($videoId);
        $newCount = $count + 1;

        $customMetaData[$this->metadataFieldCount] = $newCount;
        $customMetaData[$this->metadataFieldAverage] = (($average * $count) + $rating) / $newCount;

        $this->storeCustomMetaData($customMetaData, $videoId);
    }

    /**
     * Modifies the average rating value. Count of all ratings stays the same (will not be increased).
     * The use case of this function is, when someone wants to change its rating (video was already rated by that person).
     *
     * @param string $videoId
     * @param int    $rating
     * @param int    $oldRating
     *
     * @throws \InvalidArgumentException
     */
    public function modifyRating($videoId, $rating, $oldRating)
    {
        $this->validateRating($rating);
        $customMetaData = $this->getVideo($videoId)->getCustomMetadata();

        $average = $this->getRatingAverage($videoId);
        $count = $this->getRatingCount($videoId);

        $customMetaData[$this->metadataFieldAverage] = (($average * $count) - $oldRating + $rating) / $count;

        $this->storeCustomMetaData($customMetaData, $videoId);
    }

    /**
     * Returns the average rating value from the custom meta data fields from a given video.
     *
     * @param string $videoId
     *
     * @return float|int
     */
    public function getRatingAverage($videoId)
    {
        return $this->getCustomMetaDataField($videoId, $this->metadataFieldAverage);
    }

    /**
     * Returns the count of all ratings from the custom meta data fields from a given video.
     *
     * @param string $videoId
     *
     * @return float|int
     */
    private function getRatingCount($videoId)
    {
        return $this->getCustomMetaDataField($videoId, $this->metadataFieldCount);
    }

    /**
     * Returns a meta data field of a video always as a number.
     *
     * @param $videoId
     * @param $customMetaDataField
     *
     * @return float|int
     */
    private function getCustomMetaDataField($videoId, $customMetaDataField)
    {
        $customMetaData = $this->getVideo($videoId)->getCustomMetadata();

        return array_key_exists($customMetaDataField, $customMetaData)
            ? (float) $customMetaData[$customMetaDataField]
            : 0;
    }

    /**
     * Stores the custom meta data fields with the api client.
     *
     * @param array  $customMetaData
     * @param string $videoId
     */
    private function storeCustomMetaData($customMetaData, $videoId)
    {
        // only update custom meta data fields related to rating
        $this->client->setCustomMetaData(
            $this->vmId,
            $videoId,
            $this->filterCustomMetaData($customMetaData)
        );

        // also store custom meta data fields locally, if video is fetched again by function $this->getVideo($videoId)
        $this->getVideo($videoId)->setCustomMetadata($customMetaData);
    }

    /**
     * Fetches and returns video from api client and stores it locally.
     * This way api client will be requested only once for every video.
     *
     * @param string $videoId
     *
     * @return Video
     */
    private function getVideo($videoId)
    {
        if (!array_key_exists($videoId, $this->videos)) {
            $options = new VideoRequestParameters();
            $options->setIncludeCustomMetadata(true);
            $this->videos[$videoId] = $this->client->getVideo($this->vmId, $videoId, $options);
        }

        return $this->videos[$videoId];
    }

    /**
     * Returns custom meta data fields which are related to rating.
     *
     * @param array $customMetaData
     *
     * @return array
     */
    private function filterCustomMetaData($customMetaData)
    {
        foreach ($customMetaData as $key => $data) {
            if (!in_array($key, [$this->metadataFieldCount, $this->metadataFieldAverage])) {
                unset($customMetaData[$key]);
            }
        }

        return $customMetaData;
    }

    /**
     * Checks the rating value if it is in range from 1 to 5.
     * Throws an exception if not.
     *
     * @param int $rating
     *
     * @throws \InvalidArgumentException
     */
    private function validateRating($rating)
    {
        if ($rating < self::MINIMUM_RATING || $rating > self::MAXIMUM_RATING) {
            throw new \InvalidArgumentException('rating value is not in expected range');
        }
    }
}
