<?php

declare(strict_types=1);

namespace App\RemoteEvent;

use Psr\Log\LoggerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use TelegramBot\Api\BotApi;
use Throwable;

#[AsRemoteEventConsumer('github')]
final class GithubWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly BotApi          $api,
        private readonly LoggerInterface $logger
    ) {
    }

    public function consume(RemoteEvent $event): void
    {
        $name = $event->getName();

        $this->logger->info("debugging event ..... : $name");
        try {
            match (true) {
                $name === 'push' => $this->handlePushEvent($event),
                $name === 'ping' => $this->handlePingEvent($event),
                $name === 'star' => $this->handleStarEvent($event),
                default => null,
            };
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function handlePushEvent(RemoteEvent $event): void
    {
        $data = $event->getPayload();
        $project = $data['repository']['full_name'];
        $pusher = $data['pusher']['name'];
        $description = $data['head_commit']['message'];
        $ref = str_replace('refs/heads/', '', $data['ref']);
        $commit = substr(strval($data['after']), 0, 8);

        $message = vsprintf(
            format: $commit === '00000000' ?
                '🔥 %s deleted %s on %s' :
                '🔥 %s pushed %s on %s : %s',
            values: [$pusher, $ref, $project, $description]
        );

        $this->sendMessage($message);
    }

    private function sendMessage(?string $message = null): void
    {
        if ($message !== null) {
            $this->api->sendMessage(
                chatId: '7499578535',
                text: $message,
                parseMode: 'markdown',
                disablePreview: true,
            );
        }
    }

    private function handlePingEvent(RemoteEvent $event): void
    {
        $data = $event->getPayload();
        $message = sprintf('👉 Github ping : %s', $data['zen']);
        $this->sendMessage($message);
    }

    private function handleStarEvent(RemoteEvent $event): void
    {
        $data = $event->getPayload();
        $sender = $data['sender']['login'];
        $project = $data['repository']['name'];
        $stars = $data['repository']['stargazers_count'];
        $action = $data['action'];

        $message = match (true) {
            $action === 'created' => vsprintf('👍 %s starred %s (%d stars)', [$sender, $project, $stars]),
            $action === 'deleted' => vsprintf('👎 %s unstarred %s (%d stars)', [$sender, $project, $stars]),
            default => null,
        };
        $this->sendMessage($message);
    }
}
