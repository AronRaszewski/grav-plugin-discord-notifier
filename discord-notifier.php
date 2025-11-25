<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;

class DiscordNotifierPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'onAdminSave' => ['onAdminSave', 0]
        ];
    }

    public function onAdminSave($event)
    {
        $page = $event['page'];

        if (!$page || !$page instanceof \Grav\Common\Page\Page) {
            return;
        }

        // Jeśli strona nie jest opublikowana — nic nie rób
        if (!$page->published()) {
            return;
        }

        // Tylko jeśli ma status 'published' w tym ZAPISIE
        $header = $page->header();
        if (isset($header->published) && $header->published === false) {
            return;
        }

        $config = $this->config->get('plugins.discord-notifier');
        $webhook = $config['webhook_url'] ?? null;

        if (!$webhook) {
            return;
        }

        $title = $page->title();
        $url   = $page->url(true, true);

        $payload = [
            "content" => str_replace(['{title}', '{url}'], [$title, $url], $config['discord_message'])
        ];

        // Wyślij wiadomość
        $this->sendToDiscord($webhook, $payload);
    }

    private function sendToDiscord($url, $payload)
    {
        $json = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        curl_close($ch);
    }
}
