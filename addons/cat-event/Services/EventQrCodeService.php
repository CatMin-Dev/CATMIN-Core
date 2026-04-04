<?php

declare(strict_types=1);

namespace Addons\CatEvent\Services;

use Addons\CatEvent\Models\EventTicket;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class EventQrCodeService
{
    /**
     * @return array<string,mixed>
     */
    public function payloadForTicket(EventTicket $ticket): array
    {
        return [
            'v' => 1,
            'type' => 'catmin.event.ticket',
            'event_id' => $ticket->event_id,
            'ticket_code' => $ticket->code ?: $ticket->ticket_number,
            'token' => $ticket->token,
        ];
    }

    public function payloadJson(EventTicket $ticket): string
    {
        return (string) json_encode($this->payloadForTicket($ticket), JSON_UNESCAPED_SLASHES);
    }

    public function svgDataUri(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($payload);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
