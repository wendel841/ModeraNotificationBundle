<?php

namespace Modera\NotificationBundle\Dispatching;

/**
 * Instance of this class is only meant to be manipulated from dispatch() method of notification channels.
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2016 Modera Foundation
 */
final class DeliveryReport
{
    /**
     * @var array
     */
    private $failedDeliveries = [];

    /**
     * @var array
     */
    private $successfulDeliveries = [];

    /**
     * @var NotificationBuilder
     */
    private $notificationBuilder;

    /**
     * @var callable
     */
    private $metaContributorCallback;

    /**
     * @var mixed
     */
    private $dispatchResult;

    /**
     * @internal
     *
     * @param NotificationBuilder $notificationBuilder
     * @param $dispatchResult
     * @param callable $metaContributorCallback
     */
    public function __construct(NotificationBuilder $notificationBuilder, $dispatchResult, callable $metaContributorCallback)
    {
        $this->notificationBuilder = $notificationBuilder;
        $this->dispatchResult = $dispatchResult;
        $this->metaContributorCallback = $metaContributorCallback;
    }

    /**
     * @return ChannelRegistryInterface
     */
    private function getChannelRegistry()
    {
        return $this->notificationBuilder->getNotificationCenter()->getChannelRegistry();
    }

    /**
     * @return NotificationBuilder
     */
    public function getNotificationBuilder()
    {
        return $this->notificationBuilder;
    }

    /**
     * @return mixed
     */
    public function getDispatchResult()
    {
        return $this->dispatchResult;
    }

    /**
     * @param ChannelInterface|ChannelInterface[]|string|string[] $channel
     * @param string $message
     * @param mixed $meta
     */
    public function markDelivered($channel, $message = null, $meta = null)
    {
        foreach ($this->resolveChannelArg($channel) as $channel) {
            $this->successfulDeliveries[] = array(
                'channel' => $channel,
                'message' => $message,
                'meta' => $meta,
            );
        }
    }

    /**
     * @param ChannelInterface|ChannelInterface[]|string|string[] $channel
     * @param mixed $error
     * @param mixed $meta
     */
    public function markFailed($channel, $error = null, $meta = null)
    {
        foreach ($this->resolveChannelArg($channel) as $channel) {
            $this->failedDeliveries[] = array(
                'channel' => $channel,
                'error' => $error,
                'meta' => $meta,
            );
        }
    }

    private function resolveChannelArg($channel)
    {
        $channels = is_array($channel) ? $channel : [$channel];

        $result = [];
        foreach ($channels as $iteratedChannel) {
            $channelInstance = is_string($iteratedChannel) ? $this->getChannelRegistry()->getById($iteratedChannel) : $iteratedChannel;

            $result[] = $channelInstance;

        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return count($this->failedDeliveries) == 0;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return count($this->failedDeliveries) > 0;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->failedDeliveries;
    }

    /**
     * @return array
     */
    public function getSuccessfulDeliveries()
    {
        return $this->successfulDeliveries;
    }

    /**
     * You can use this method to modify meta information which is persisted with a notification and
     * later can be fetched with notification center.
     *
     * @param array $meta
     */
    public function contributeMeta(array $meta)
    {
        call_user_func($this->metaContributorCallback, $meta);
    }

    /**
     * @param ChannelInterface|string $channel
     *
     * @return bool
     */
    public function isAlreadyHandled($channel)
    {
        $channelId = $channel instanceof ChannelInterface ? $channel->getId() : $channel;

        foreach (array_merge($this->successfulDeliveries, $this->failedDeliveries) as $entry) {
            if ($channelId == $entry['channel']->getId()) {
                return true;
            }
        }

        return false;
    }
}
